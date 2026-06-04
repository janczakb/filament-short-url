<?php

/**
 * @package    janczakb/filament-short-url
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Http\Request;

class ClientIpExtractor
{
    /**
     * Get a proxy-resistant client IP address.
     */
    public static function getIp(Request $request): string
    {
        if (config('filament-short-url.trust_cdn_headers', false)) {
            // 1. Cloudflare connecting IP header
            if ($cfIp = $request->header('CF-Connecting-IP')) {
                return trim($cfIp);
            }

            // 2. Akamai or other CDNs True-Client-IP header
            if ($trueIp = $request->header('True-Client-IP')) {
                return trim($trueIp);
            }

            // 3. General reverse proxy / Nginx X-Real-IP header
            if ($realIp = $request->header('X-Real-IP')) {
                return trim($realIp);
            }

            // 4. Standard X-Forwarded-For header chain (first IP is the client)
            if ($forwardedFor = $request->header('X-Forwarded-For')) {
                $ips = explode(',', $forwardedFor);

                return trim($ips[0]);
            }
        }

        // 5. Fallback to standard Laravel/Symfony IP resolver (which respects Trusted Proxies)
        return $request->ip() ?? '0.0.0.0';
    }

    /**
     * Extract the edge-provided 2-letter country code from CDN/proxy headers.
     */
    public static function getCountryCode(Request $request): ?string
    {
        if (config('filament-short-url.trust_cdn_headers', false)) {
            // 1. Cloudflare IP Country header
            if ($cfCountry = $request->header('CF-IPCountry')) {
                return strtoupper(trim($cfCountry));
            }

            // 2. AWS CloudFront country header
            if ($cfViewerCountry = $request->header('CloudFront-Viewer-Country')) {
                return strtoupper(trim($cfViewerCountry));
            }

            // 3. Generic CDN / Proxy country header
            if ($xCountry = $request->header('X-Country-Code')) {
                return strtoupper(trim($xCountry));
            }
        }

        return null;
    }

    /**
     * Extract the edge-provided city name from CDN/proxy headers.
     */
    public static function getCity(Request $request): ?string
    {
        if (config('filament-short-url.trust_cdn_headers', false)) {
            if ($cfCity = $request->header('CF-IPCity')) {
                return trim($cfCity);
            }

            if ($cfViewerCity = $request->header('CloudFront-Viewer-City')) {
                return trim($cfViewerCity);
            }
        }

        return null;
    }
}
