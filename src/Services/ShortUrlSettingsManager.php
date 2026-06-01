<?php

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Support\Facades\File;

class ShortUrlSettingsManager
{
    private ?array $cache = null;

    public function getSettingsPath(): string
    {
        return storage_path('app/filament-short-url-settings.json');
    }

    /**
     * Get all settings merged with configuration defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $path = $this->getSettingsPath();
        $stored = [];

        if (File::exists($path)) {
            try {
                $stored = json_decode(File::get($path), true) ?: [];
            } catch (\Throwable) {
                // Fallback to empty if json is corrupt
            }
        }

        // Merge stored settings with default values from config()
        $this->cache = array_merge([
            'route_prefix' => config('filament-short-url.route_prefix', 's'),
            'redirect_status_code' => config('filament-short-url.redirect_status_code', 302),
            'key_length' => config('filament-short-url.key_length', 6),
            'geo_ip_enabled' => config('filament-short-url.geo_ip.enabled', true),
            'geo_ip_driver' => config('filament-short-url.geo_ip.driver', 'headers'),
            'geo_ip_cache_ttl' => config('filament-short-url.geo_ip.cache_ttl', 86400),
            'geo_ip_timeout' => config('filament-short-url.geo_ip.timeout', 3),
            'maxmind_database_path' => config('filament-short-url.geo_ip.maxmind.database_path', storage_path('geoip/GeoLite2-Country.mmdb')),
            'ga4_api_secret' => config('filament-short-url.ga4.api_secret'),
            'ga4_firebase_app_id' => config('filament-short-url.ga4.firebase_app_id'),
            'queue_connection' => config('filament-short-url.queue_connection', 'sync'),
            'cache_ttl' => config('filament-short-url.cache_ttl', 3600),
            'counter_buffering_enabled' => config('filament-short-url.counter_buffering.enabled', false),
            'trust_cdn_headers' => config('filament-short-url.trust_cdn_headers', false),
            'pruning_enabled' => config('filament-short-url.pruning.enabled', true),
            'pruning_retention_days' => config('filament-short-url.pruning.retention_days', 90),
            'rate_limiting_enabled' => config('filament-short-url.rate_limiting.enabled', false),
            'rate_limiting_max_attempts' => config('filament-short-url.rate_limiting.max_attempts', 60),
            'rate_limiting_decay_seconds' => config('filament-short-url.rate_limiting.decay_seconds', 60),
        ], $stored);

        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Persist settings to JSON file.
     *
     * @param  array<string, mixed>  $data
     */
    public function set(array $data): void
    {
        $path = $this->getSettingsPath();
        $dir = dirname($path);

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        // Keep only supported settings keys to prevent bloat
        $keys = [
            'route_prefix',
            'redirect_status_code',
            'key_length',
            'geo_ip_enabled',
            'geo_ip_driver',
            'geo_ip_cache_ttl',
            'geo_ip_timeout',
            'maxmind_database_path',
            'ga4_api_secret',
            'ga4_firebase_app_id',
            'queue_connection',
            'cache_ttl',
            'counter_buffering_enabled',
            'trust_cdn_headers',
            'pruning_enabled',
            'pruning_retention_days',
            'rate_limiting_enabled',
            'rate_limiting_max_attempts',
            'rate_limiting_decay_seconds',
        ];

        $filtered = array_intersect_key($data, array_flip($keys));

        // Format datatypes properly
        if (isset($filtered['redirect_status_code'])) {
            $filtered['redirect_status_code'] = (int) $filtered['redirect_status_code'];
        }
        if (isset($filtered['key_length'])) {
            $filtered['key_length'] = (int) $filtered['key_length'];
        }
        if (isset($filtered['geo_ip_enabled'])) {
            $filtered['geo_ip_enabled'] = (bool) $filtered['geo_ip_enabled'];
        }
        if (isset($filtered['geo_ip_cache_ttl'])) {
            $filtered['geo_ip_cache_ttl'] = (int) $filtered['geo_ip_cache_ttl'];
        }
        if (isset($filtered['geo_ip_timeout'])) {
            $filtered['geo_ip_timeout'] = (int) $filtered['geo_ip_timeout'];
        }
        if (isset($filtered['cache_ttl'])) {
            $filtered['cache_ttl'] = (int) $filtered['cache_ttl'];
        }
        if (isset($filtered['counter_buffering_enabled'])) {
            $filtered['counter_buffering_enabled'] = (bool) $filtered['counter_buffering_enabled'];
        }
        if (isset($filtered['trust_cdn_headers'])) {
            $filtered['trust_cdn_headers'] = (bool) $filtered['trust_cdn_headers'];
        }
        if (isset($filtered['pruning_enabled'])) {
            $filtered['pruning_enabled'] = (bool) $filtered['pruning_enabled'];
        }
        if (isset($filtered['pruning_retention_days'])) {
            $filtered['pruning_retention_days'] = (int) $filtered['pruning_retention_days'];
        }
        if (isset($filtered['rate_limiting_enabled'])) {
            $filtered['rate_limiting_enabled'] = (bool) $filtered['rate_limiting_enabled'];
        }
        if (isset($filtered['rate_limiting_max_attempts'])) {
            $filtered['rate_limiting_max_attempts'] = (int) $filtered['rate_limiting_max_attempts'];
        }
        if (isset($filtered['rate_limiting_decay_seconds'])) {
            $filtered['rate_limiting_decay_seconds'] = (int) $filtered['rate_limiting_decay_seconds'];
        }

        File::put($path, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->cache = null;

        // Apply immediately to current request config
        $this->applyConfigOverrides();
    }

    /**
     * Override standard Laravel config with user-configured settings.
     */
    public function applyConfigOverrides(): void
    {
        $settings = $this->all();

        config([
            'filament-short-url.route_prefix' => $settings['route_prefix'],
            'filament-short-url.redirect_status_code' => $settings['redirect_status_code'],
            'filament-short-url.key_length' => $settings['key_length'],
            'filament-short-url.geo_ip.enabled' => $settings['geo_ip_enabled'],
            'filament-short-url.geo_ip.driver' => $settings['geo_ip_driver'],
            'filament-short-url.geo_ip.cache_ttl' => $settings['geo_ip_cache_ttl'],
            'filament-short-url.geo_ip.timeout' => $settings['geo_ip_timeout'],
            'filament-short-url.geo_ip.maxmind.database_path' => $settings['maxmind_database_path'],
            'filament-short-url.ga4.api_secret' => $settings['ga4_api_secret'],
            'filament-short-url.ga4.firebase_app_id' => $settings['ga4_firebase_app_id'],
            'filament-short-url.queue_connection' => $settings['queue_connection'],
            'filament-short-url.cache_ttl' => $settings['cache_ttl'],
            'filament-short-url.counter_buffering.enabled' => $settings['counter_buffering_enabled'],
            'filament-short-url.trust_cdn_headers' => $settings['trust_cdn_headers'],
            'filament-short-url.pruning.enabled' => $settings['pruning_enabled'],
            'filament-short-url.pruning.retention_days' => $settings['pruning_retention_days'],
            'filament-short-url.rate_limiting.enabled' => $settings['rate_limiting_enabled'],
            'filament-short-url.rate_limiting.max_attempts' => $settings['rate_limiting_max_attempts'],
            'filament-short-url.rate_limiting.decay_seconds' => $settings['rate_limiting_decay_seconds'],
        ]);
    }
}
