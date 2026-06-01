<?php

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
    ) {}

    /**
     * Record a visit and return the created ShortUrlVisit model.
     *
     * Returns null if the visit was from a bot/crawler (we don't track those).
     */
    public function record(ShortUrl $shortUrl, Request $request, ?string $preResolvedCountryCode = null): ?ShortUrlVisit
    {
        $ip = ClientIpExtractor::getIp($request);
        $ipHash = hash('sha256', $ip);
        $ua = $request->userAgent() ?? '';
        $parsed = $this->uaParser->parse($ua);

        // Don't track bots — they inflate stats and waste API calls
        if ($parsed['device_type'] === 'robot') {
            return null;
        }

        $geo = config('filament-short-url.geo_ip.enabled', true)
            ? $this->geoIp->resolve($ip, $preResolvedCountryCode)
            : ['country' => null, 'country_code' => null];

        // Determine uniqueness: first time this IP hash visits this URL.
        // We check BEFORE insert to avoid a self-referential race.
        $isUnique = ! ShortUrlVisit::where('short_url_id', $shortUrl->id)
            ->where('ip_hash', $ipHash)
            ->exists();

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
        $visit->referer_url = $shortUrl->track_referer_url ? $request->header('Referer') : null;
        $visit->country = $geo['country'];
        $visit->country_code = $geo['country_code'];

        $visit->save();

        $shortUrl->incrementVisits($isUnique);

        return $visit;
    }
}
