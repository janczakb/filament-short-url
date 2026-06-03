<?php

namespace Bjanczak\FilamentShortUrl\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves country information from an IP address or pre-resolved headers.
 *
 * Supports local CDN/proxy headers, offline MaxMind databases, and online fallbacks.
 * Results are cached per IP hash to minimise overhead.
 *
 * @internal
 */
class GeoIpService
{
    private const API_URL = 'http://ip-api.com/json/%s?fields=status,country,countryCode,city';

    /** @var array<string, array{country: string|null, country_code: string|null, city: string|null}> */
    private static array $runtimeCache = [];

    /**
     * Resolve country and city data for the given IP address.
     *
     * @return array{country: string|null, country_code: string|null, city: string|null}
     */
    public function resolve(string $ip, ?string $preResolvedCountryCode = null, ?string $preResolvedCity = null): array
    {
        $empty = ['country' => null, 'country_code' => null, 'city' => null];

        if (! config('filament-short-url.geo_ip.enabled', true)) {
            return $empty;
        }

        // 1. Prioritise edge-provided CDN country code & city (extremely fast, zero latency)
        if ($preResolvedCountryCode) {
            $code = strtoupper(trim($preResolvedCountryCode));

            return [
                'country' => $this->getCountryName($code),
                'country_code' => $code,
                'city' => $preResolvedCity,
            ];
        }

        if ($this->isPrivateIp($ip)) {
            return $empty;
        }

        $cacheKey = 'short_url_geo_'.hash('sha256', $ip);

        // Check runtime cache first (avoids repeated DB/Redis reads in same request)
        if (isset(self::$runtimeCache[$cacheKey])) {
            return self::$runtimeCache[$cacheKey];
        }

        $ttl = (int) config('filament-short-url.geo_ip.cache_ttl', 86400);

        $result = Cache::remember($cacheKey, $ttl, function () use ($ip, $empty): array {
            $driver = config('filament-short-url.geo_ip.driver', 'headers');

            if ($driver === 'maxmind') {
                return $this->lookupMaxMind($ip) ?? $empty;
            }

            if ($driver === 'ip-api') {
                return $this->fetchFromApi($ip) ?? $empty;
            }

            return $empty;
        });

        self::$runtimeCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Resolve country name from a 2-letter ISO country code.
     */
    private function getCountryName(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $code = strtoupper(trim($code));

        if (class_exists(\Locale::class)) {
            try {
                $name = \Locale::getDisplayRegion('en-'.$code, 'en');
                if ($name && $name !== $code) {
                    return $name;
                }
            } catch (\Throwable $e) {
                // Ignore locale failures
            }
        }

        // Common country code fallback dictionary
        $countries = [
            'PL' => 'Poland',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'NL' => 'Netherlands',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'BR' => 'Brazil',
            'CN' => 'China',
            'IN' => 'India',
            'JP' => 'Japan',
            'RU' => 'Russia',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'FI' => 'Finland',
            'DK' => 'Denmark',
        ];

        return $countries[$code] ?? $code;
    }

    /**
     * Resolve country and city using local MaxMind GeoIP2 DB.
     *
     * @return array{country: string|null, country_code: string|null, city: string|null}|null
     */
    private function lookupMaxMind(string $ip): ?array
    {
        if (! class_exists(Reader::class)) {
            Log::warning('[FilamentShortUrl] MaxMind driver selected, but geoip2/geoip2 library is not installed.');

            return null;
        }

        $dbPath = config('filament-short-url.geo_ip.maxmind.database_path');

        if (! $dbPath || ! file_exists($dbPath)) {
            Log::warning('[FilamentShortUrl] MaxMind database file not found at: '.$dbPath);

            return null;
        }

        try {
            $reader = new Reader($dbPath);
            $dbType = $reader->metadata()->databaseType;

            if (str_contains($dbType, 'City')) {
                $record = $reader->city($ip);

                return [
                    'country' => $record->country->name ?? null,
                    'country_code' => $record->country->isoCode ?? null,
                    'city' => $record->city->name ?? null,
                ];
            }

            $record = $reader->country($ip);

            return [
                'country' => $record->country->name ?? null,
                'country_code' => $record->country->isoCode ?? null,
                'city' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[FilamentShortUrl] MaxMind GeoIP lookup failed', [
                'ip' => substr($ip, 0, 8).'***',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resolve country and city using free ip-api.com.
     *
     * @return array{country: string|null, country_code: string|null, city: string|null}|null
     */
    private function fetchFromApi(string $ip): ?array
    {
        $timeout = (int) config('filament-short-url.geo_ip.timeout', 3);

        try {
            $response = Http::timeout($timeout)
                ->get(sprintf(self::API_URL, urlencode($ip)));

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'success') {
                return null;
            }

            return [
                'country' => $data['country'] ?? null,
                'country_code' => $data['countryCode'] ?? null,
                'city' => $data['city'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('[FilamentShortUrl] GeoIP lookup failed', [
                'ip' => substr($ip, 0, 8).'***',
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * RFC 1918 + loopback + link-local ranges — skip API call for these.
     */
    private function isPrivateIp(string $ip): bool
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return true;
        }

        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
