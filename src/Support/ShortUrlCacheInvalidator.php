<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Support;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGlobalOverview;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Illuminate\Support\Collection;

class ShortUrlCacheInvalidator
{
    /**
     * @param  Collection<int, ShortUrl>|array<int, ShortUrl>  $shortUrls
     */
    public static function forgetMany(Collection|array $shortUrls): void
    {
        foreach ($shortUrls as $shortUrl) {
            self::forget($shortUrl);
        }
    }

    public static function forget(ShortUrl $shortUrl): void
    {
        cache()->forget("filament-short-url:visits:{$shortUrl->id}");

        $appHost = HostNormalizer::normalize(parse_url(config('app.url'), PHP_URL_HOST));
        $hosts = ['default'];

        if ($appHost) {
            $hosts[] = $appHost;
        }

        if ($shortUrl->custom_domain_id) {
            $domain = $shortUrl->relationLoaded('customDomain')
                ? $shortUrl->customDomain
                : ShortUrlCustomDomain::find($shortUrl->custom_domain_id);

            if ($domain) {
                $hosts[] = HostNormalizer::normalize($domain->domain) ?? $domain->domain;
            }
        }

        foreach (array_unique($hosts) as $host) {
            cache()->forget("filament-short-url:{$shortUrl->url_key}:{$host}");
        }
    }

    public static function forgetGlobalOverview(): void
    {
        cache()->forget(ShortUrlGlobalOverview::LINKS_CACHE_KEY);
    }
}
