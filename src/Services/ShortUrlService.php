<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShortUrlService
{
    public function __construct(
        private readonly UrlMetaScraper $metaScraper,
    ) {}

    /**
     * Start building a ShortUrl programmatically using a fluent interface.
     */
    public function destination(string $url): ShortUrlBuilder
    {
        return new ShortUrlBuilder($this, $url);
    }

    /**
     * Generate a unique, collision-free URL key.
     *
     * Uses base62 (a-z A-Z 0-9) for maximum URL friendliness.
     * Retries automatically on the rare collision chance.
     */
    public function generateKey(?int $length = null): string
    {
        $length ??= (int) config('filament-short-url.key_length', 6);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $maxIndex = strlen($characters) - 1;

        do {
            $key = '';
            for ($i = 0; $i < $length; $i++) {
                $key .= $characters[random_int(0, $maxIndex)];
            }
        } while ($this->keyExists($key));

        return $key;
    }

    public function keyExists(string $key): bool
    {
        return ShortUrl::where('url_key', $key)->exists();
    }

    /**
     * Create and persist a new ShortUrl.
     *
     * @param  array{
     *     destination_url: string,
     *     url_key?: string,
     *     notes?: string,
     *     is_enabled?: bool,
     *     redirect_status_code?: int,
     *     single_use?: bool,
     *     forward_query_params?: bool,
     *     expires_at?: Carbon|null,
     *     track_visits?: bool,
     *     track_ip_address?: bool,
     *     track_browser?: bool,
     *     track_browser_version?: bool,
     *     track_operating_system?: bool,
     *     track_operating_system_version?: bool,
     *     track_device_type?: bool,
     *     track_referer_url?: bool,
     *     qr_options?: array,
     *     ga_tracking_id?: string|null,
     * } $data
     */
    public function create(array $data): ShortUrl
    {
        $tracking = config('filament-short-url.tracking', []);
        $fields = $tracking['fields'] ?? [];

        $data = array_merge([
            'is_enabled' => true,
            'redirect_status_code' => config('filament-short-url.redirect_status_code', 302),
            'single_use' => false,
            'forward_query_params' => false,
            'track_visits' => $tracking['enabled'] ?? true,
            'track_ip_address' => $fields['ip_address'] ?? true,
            'track_browser' => $fields['browser'] ?? true,
            'track_browser_version' => $fields['browser_version'] ?? true,
            'track_operating_system' => $fields['operating_system'] ?? true,
            'track_operating_system_version' => $fields['operating_system_version'] ?? true,
            'track_device_type' => $fields['device_type'] ?? true,
            'track_referer_url' => $fields['referer_url'] ?? true,
        ], $data);

        if (! array_key_exists('custom_domain_id', $data) || $data['custom_domain_id'] === '') {
            $defaultDisabled = (bool) config('filament-short-url.disable_default_domain', false);
            if (! $defaultDisabled) {
                $data['custom_domain_id'] = null;
            } else {
                $domains = ShortUrlCustomDomain::where('is_active', true)
                    ->where('is_verified', true)
                    ->get();
                if ($domains->count() === 1) {
                    $data['custom_domain_id'] = $domains->first()->id;
                }
            }
        }

        if (empty($data['url_key'])) {
            $data['url_key'] = $this->generateKey();
        }

        return ShortUrl::create($data);
    }

    /**
     * Build the full publicly accessible short URL string.
     */
    public function buildShortUrl(ShortUrl $shortUrl): string
    {
        return $shortUrl->getShortUrl();
    }

    /**
     * Resolve the final redirect target URL, optionally forwarding query params.
     */
    public function resolveRedirectUrl(ShortUrl $shortUrl, Request $request): string
    {
        $destination = $shortUrl->resolveDestinationUrl($request);

        if (! $shortUrl->forward_query_params) {
            return $destination;
        }

        $queryParams = $request->query();
        // Remove routing/auth parameters so they don't leak to destination
        unset($queryParams['confirmed'], $queryParams['password']);

        if (empty($queryParams)) {
            return $destination;
        }

        $separator = str_contains($destination, '?') ? '&' : '?';

        return $destination.$separator.http_build_query($queryParams);
    }

    /**
     * Scrape Open Graph / Twitter Card metadata from the destination URL.
     *
     * @return array{title?: string, description?: string, image?: string}
     */
    public function scrapeMetaTags(string $url): array
    {
        return $this->metaScraper->scrape($url);
    }
}
