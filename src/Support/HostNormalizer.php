<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Support;

class HostNormalizer
{
    /**
     * Normalize a hostname for consistent cache keys and DB lookups.
     */
    public static function normalize(?string $host): ?string
    {
        if ($host === null || $host === '') {
            return null;
        }

        $host = strtolower(trim($host));

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host !== '' ? $host : null;
    }

    /**
     * Reserved first-path segments that must not be treated as short URL keys on custom domains.
     *
     * @return list<string>
     */
    public static function reservedFallbackSegments(): array
    {
        return [
            'api',
            'admin',
            'auth',
            'login',
            'logout',
            'register',
            'horizon',
            'telescope',
            'vendor',
            'storage',
            'livewire',
            'filament',
            'short-url',
            'build',
            'favicon.ico',
            'robots.txt',
            '.well-known',
        ];
    }

    public static function isReservedFallbackKey(string $key): bool
    {
        $segment = strtolower(explode('/', $key, 2)[0]);

        return in_array($segment, self::reservedFallbackSegments(), true);
    }
}
