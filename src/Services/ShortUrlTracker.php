<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsVisitRecorder;
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
        private readonly BotDetector $botDetector,
    ) {}

    /**
     * Record a visit and return the created ShortUrlVisit model.
     *
     * Returns null when the visit should not be tracked (bots, deduplicated clicks).
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
        ?string $selectedVariant = null,
        ?array $precomputedProxyDetection = null,
        bool $skipTotalIncrement = false,
    ): ?ShortUrlVisit {
        $ip = ClientIpExtractor::getIp($request);

        if ($this->botDetector->isBot($request)) {
            return null;
        }

        // HMAC-SHA256 keyed with app.key — plain SHA-256 is trivially reversible for
        // IPv4 (only ~4.3B unique values). The app.key salt makes this per-installation
        // and computationally infeasible to reverse without the secret.
        $ipHash = hash_hmac('sha256', $ip, config('app.key', ''));
        $ua = $request->userAgent() ?? '';

        if ($this->isDuplicateClick($shortUrl->id, $ipHash)) {
            return null;
        }

        $parsed = $this->uaParser->parse($ua);

        $isBot = false;
        $isProxy = false;

        $detection = $precomputedProxyDetection ?? $this->proxyDetector->detect($ip);
        $isProxy = (bool) $detection['is_proxy'];
        $isBot = (bool) ($detection['is_bot'] ?? false);

        $geo = config('filament-short-url.geo_ip.enabled', true)
            ? $this->geoIp->resolve($ip, $preResolvedCountryCode, $preResolvedCity)
            : ['country' => null, 'country_code' => null, 'city' => null];

        // Determine uniqueness: first time this IP hash visits this URL.
        // We check BEFORE insert to avoid a self-referential race.
        $isUnique = false;
        if (config('filament-short-url.counter_buffering.enabled', false)) {
            $existsInDatabase = ShortUrlVisit::where('short_url_id', $shortUrl->id)
                ->where('ip_hash', $ipHash)
                ->exists();

            if (! $existsInDatabase) {
                $buffer = app(VisitCounterBuffer::class);

                if ($buffer->tryReserveUniqueVisit($shortUrl->id, $ipHash)) {
                    $isUnique = true;
                } else {
                    $isUnique = ! ShortUrlVisit::where('short_url_id', $shortUrl->id)
                        ->where('ip_hash', $ipHash)
                        ->exists();
                }
            }
        } else {
            $isUnique = ! ShortUrlVisit::where('short_url_id', $shortUrl->id)
                ->where('ip_hash', $ipHash)
                ->exists();
        }

        $storedIp = $ip;
        if ($storedIp && config('filament-short-url.tracking.anonymize_ips', false)) {
            $storedIp = static::anonymizeIp($storedIp);
        }

        $visit = new ShortUrlVisit;
        $visit->short_url_id = $shortUrl->id;
        $visit->visited_at = now();
        $visit->ip_hash = $ipHash;
        $visit->ip_address = $shortUrl->track_ip_address ? $storedIp : null;
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
        $visit->selected_variant = $selectedVariant;

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
            if ($skipTotalIncrement) {
                $shortUrl->incrementVisits($isUnique, $isQrScan, incrementTotal: false);
            } else {
                $shortUrl->incrementVisits($isUnique, $isQrScan);
            }

            app(StatsVisitRecorder::class)->record($shortUrl, $visit, $isUnique);
        }

        return $visit;
    }

    private function isDuplicateClick(int $shortUrlId, string $ipHash): bool
    {
        if (! config('filament-short-url.click_deduplication.enabled', false)) {
            return false;
        }

        $hours = max(1, (int) config('filament-short-url.click_deduplication.hours', 1));
        $dedupKey = "filament-short-url:click-dedup:{$shortUrlId}:{$ipHash}";

        return ! cache()->add($dedupKey, true, $hours * 3600);
    }

    /**
     * Whether this request should be treated as a duplicate click for counter purposes.
     */
    public function isDuplicateRequest(int $shortUrlId, Request $request): bool
    {
        if (! config('filament-short-url.click_deduplication.enabled', false)) {
            return false;
        }

        $ip = ClientIpExtractor::getIp($request);
        $ipHash = hash_hmac('sha256', $ip, config('app.key', ''));
        $dedupKey = "filament-short-url:click-dedup:{$shortUrlId}:{$ipHash}";

        return cache()->has($dedupKey);
    }

    /**
     * Anonymize IP address by masking the last octet (IPv4) or last 80 bits (IPv6) with zeros.
     */
    public static function anonymizeIp(?string $ip): ?string
    {
        if (empty($ip)) {
            return null;
        }

        $packed = @inet_pton($ip);
        if ($packed === false) {
            return $ip; // Fallback if parsing fails
        }

        if (strlen($packed) === 4) {
            // IPv4: mask last byte (8 bits)
            $packed[3] = "\x00";
        } elseif (strlen($packed) === 16) {
            // IPv6: mask last 10 bytes (80 bits)
            for ($i = 6; $i < 16; $i++) {
                $packed[$i] = "\x00";
            }
        }

        return inet_ntop($packed);
    }
}
