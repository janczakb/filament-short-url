<?php

/**
 * @package    janczakb/filament-short-url
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Illuminate\Http\Request;

/**
 * Records a visit to a ShortUrl, collecting all enabled tracking fields.
 *
 * @internal
 */
class ShortUrlTracker
{
    public function __construct(
        private readonly UserAgentParser $uaParser,
        private readonly GeoIpService $geoIp,
        private readonly ProxyDetectionService $proxyDetector,
    ) {}

    /**
     * Record a visit and return the created ShortUrlVisit model.
     *
     * Returns null if the visit was from a bot/crawler (we don't track those).
     */
    public function record(
        ShortUrl $shortUrl,
        Request $request,
        ?string $preResolvedCountryCode = null,
        ?string $preResolvedCity = null,
        ?string $utmSource = null,
        ?string $utmMedium = null,
        ?string $utmCampaign = null,
        ?string $utmTerm = null,
        ?string $utmContent = null,
        bool $isQrScan = false,
        ?string $browserLanguage = null,
    ): ?ShortUrlVisit {
        $ip = ClientIpExtractor::getIp($request);
        $ipHash = hash('sha256', $ip);
        $ua = $request->userAgent() ?? '';
        $parsed = $this->uaParser->parse($ua);

        // Run bot & proxy/VPN detection
        $isBot = $parsed['device_type'] === 'robot';
        $isProxy = false;

        if (! $isBot) {
            $detection = $this->proxyDetector->detect($ip);
            $isBot = (bool) $detection['is_bot'];
            $isProxy = (bool) $detection['is_proxy'];
        }

        $geo = config('filament-short-url.geo_ip.enabled', true)
            ? $this->geoIp->resolve($ip, $preResolvedCountryCode, $preResolvedCity)
            : ['country' => null, 'country_code' => null, 'city' => null];

        // Determine uniqueness: first time this IP hash visits this URL.
        // We check BEFORE insert to avoid a self-referential race.
        $isUnique = false;
        if (config('filament-short-url.counter_buffering.enabled', false)) {
            $uniqueCacheKey = "filament-short-url:unique-visit:{$shortUrl->id}:{$ipHash}";
            if (cache()->add($uniqueCacheKey, true, 86400)) {
                $isUnique = ! ShortUrlVisit::where('short_url_id', $shortUrl->id)
                    ->where('ip_hash', $ipHash)
                    ->exists();
            }
        } else {
            $isUnique = ! ShortUrlVisit::where('short_url_id', $shortUrl->id)
                ->where('ip_hash', $ipHash)
                ->exists();
        }

        $visit = new ShortUrlVisit;
        $visit->short_url_id = $shortUrl->id;
        $visit->visited_at = now();
        $visit->ip_hash = $ipHash;
        $visit->ip_address = $shortUrl->track_ip_address ? $ip : null;
        $visit->browser = $shortUrl->track_browser ? $parsed['browser'] : null;
        $visit->browser_version = $shortUrl->track_browser_version ? $parsed['browser_version'] : null;
        $visit->operating_system = $shortUrl->track_operating_system ? $parsed['operating_system'] : null;
        $visit->operating_system_version = $shortUrl->track_operating_system_version
            ? $parsed['operating_system_version'] : null;
        $visit->device_type = $shortUrl->track_device_type ? $parsed['device_type'] : null;
        $visit->country = $geo['country'];
        $visit->country_code = $geo['country_code'];
        $visit->city = $geo['city'] ?? null;
        $visit->is_bot = $isBot;
        $visit->is_proxy = $isProxy;
        $visit->is_qr_scan = $isQrScan;
        $visit->browser_language = $shortUrl->track_browser_language ? $browserLanguage : null;

        $visit->utm_source = $utmSource;
        $visit->utm_medium = $utmMedium;
        $visit->utm_campaign = $utmCampaign;
        $visit->utm_term = $utmTerm;
        $visit->utm_content = $utmContent;

        // Clean & normalize referer URL to host domain
        if ($shortUrl->track_referer_url && $referer = $request->header('Referer')) {
            $visit->referer_url = $referer;
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost) {
                $refererHost = preg_replace('/^www\./', '', strtolower($refererHost));
                if (str_contains($refererHost, 'facebook.com')) {
                    $refererHost = 'facebook.com';
                } elseif (str_contains($refererHost, 'linkedin.com')) {
                    $refererHost = 'linkedin.com';
                } elseif (str_contains($refererHost, 'twitter.com') || str_contains($refererHost, 't.co')) {
                    $refererHost = 'twitter.com';
                } elseif (str_contains($refererHost, 'google.')) {
                    $refererHost = 'google.com';
                } elseif (str_contains($refererHost, 'instagram.com')) {
                    $refererHost = 'instagram.com';
                } elseif (str_contains($refererHost, 'youtube.com')) {
                    $refererHost = 'youtube.com';
                }
                $visit->referer_host = $refererHost;
            } else {
                $visit->referer_host = 'Direct';
            }
        } else {
            $visit->referer_host = 'Direct';
        }

        $visit->save();

        // Increment stats only if it's NOT a bot or proxy/VPN to keep analytics clean
        if (! $isBot && ! $isProxy) {
            $shortUrl->incrementVisits($isUnique, $isQrScan);
        }

        return $visit;
    }
}
