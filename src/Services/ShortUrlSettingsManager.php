<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsScalingProfile;
use Bjanczak\FilamentShortUrl\Services\Stats\TodayStatsBuffer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ShortUrlSettingsManager
{
    /** @var list<string> Internal keys written by commands — never purge on settings save. */
    private const SYSTEM_SETTING_KEYS = [
        'last_aggregation_date',
    ];

    /** @var list<string> Secret fields that keep their stored value when the form submits empty/masked placeholders. */
    private const SECRET_SETTING_KEYS = [
        'ga4_api_secret',
        'redis_password',
        'vpnapi_key',
        'google_safe_browsing_api_key',
        'webhook_signing_secret',
        'bot_debug_secret',
    ];

    private ?array $cache = null;

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

        $readFromDb = function () {
            try {
                // Ensure table exists before querying to avoid exception on unmigrated db
                if (! Schema::hasTable('short_url_settings')) {
                    return [];
                }

                $settings = DB::table('short_url_settings')
                    ->pluck('value', 'key')
                    ->toArray();

                $decoded = [];
                foreach ($settings as $key => $val) {
                    $decoded[$key] = json_decode($val, true);
                }

                return $decoded;
            } catch (\Throwable $e) {
                // Fallback to empty if DB query fails during boot/installation
                return [];
            }
        };

        // Cache settings briefly to keep cross-node changes fresh.
        $stored = app()->bound('cache')
            ? cache()->remember('filament-short-url:settings', 3600, $readFromDb)
            : $readFromDb();

        if (! is_array($stored)) {
            $stored = [];
        }

        // Merge stored settings with default values from config()
        $this->cache = array_merge([
            'route_prefix' => config('filament-short-url.route_prefix', 's'),
            'lock_url_key' => config('filament-short-url.lock_url_key', false),
            'disable_default_domain' => config('filament-short-url.disable_default_domain', false),
            'redirect_status_code' => config('filament-short-url.redirect_status_code', 302),
            'key_length' => config('filament-short-url.key_length', 6),
            'geo_ip_enabled' => config('filament-short-url.geo_ip.enabled', true),
            'geo_ip_driver' => config('filament-short-url.geo_ip.driver', 'headers'),
            'geo_ip_cache_ttl' => config('filament-short-url.geo_ip.cache_ttl', 86400),
            'geo_ip_timeout' => config('filament-short-url.geo_ip.timeout', 3),
            'maxmind_database_path' => config('filament-short-url.geo_ip.maxmind.database_path', storage_path('geoip/GeoLite2-Country.mmdb')),
            'geo_ip_stats_cache_ttl' => config('filament-short-url.geo_ip.stats_cache_ttl', 300),
            'ga4_api_secret' => config('filament-short-url.ga4.api_secret'),
            'ga4_firebase_app_id' => config('filament-short-url.ga4.firebase_app_id'),
            'queue_connection' => config('filament-short-url.queue_connection', 'sync'),
            'queue_name' => config('filament-short-url.queue_name', 'default'),
            'redis_host' => config('filament-short-url.redis.host', config('database.redis.default.host', '127.0.0.1')),
            'redis_port' => (int) config('filament-short-url.redis.port', config('database.redis.default.port', 6379)),
            'redis_password' => config('filament-short-url.redis.password', config('database.redis.default.password')),
            'redis_database' => (int) config('filament-short-url.redis.database', config('database.redis.default.database', 0)),
            'redis_key_prefix' => config('filament-short-url.redis.prefix', config('database.redis.options.prefix', '')),
            'cache_ttl' => config('filament-short-url.cache_ttl', 3600),
            'counter_buffering_enabled' => config('filament-short-url.counter_buffering.enabled', false),
            'trust_cdn_headers' => config('filament-short-url.trust_cdn_headers', false),
            'pruning_enabled' => config('filament-short-url.pruning.enabled', true),
            'pruning_retention_days' => config('filament-short-url.pruning.retention_days', 90),
            'rate_limiting_enabled' => config('filament-short-url.rate_limiting.enabled', false),
            'rate_limiting_max_attempts' => config('filament-short-url.rate_limiting.max_attempts', 60),
            'rate_limiting_decay_seconds' => config('filament-short-url.rate_limiting.decay_seconds', 60),
            'tracking_enabled' => config('filament-short-url.tracking.enabled', true),
            'tracking_anonymize_ips' => config('filament-short-url.tracking.anonymize_ips', false),
            'tracking_fields_ip_address' => config('filament-short-url.tracking.fields.ip_address', true),
            'tracking_fields_browser' => config('filament-short-url.tracking.fields.browser', true),
            'tracking_fields_browser_version' => config('filament-short-url.tracking.fields.browser_version', true),
            'tracking_fields_operating_system' => config('filament-short-url.tracking.fields.operating_system', true),
            'tracking_fields_operating_system_version' => config('filament-short-url.tracking.fields.operating_system_version', true),
            'tracking_fields_referer_url' => config('filament-short-url.tracking.fields.referer_url', true),
            'tracking_fields_device_type' => config('filament-short-url.tracking.fields.device_type', true),
            'tracking_fields_browser_language' => config('filament-short-url.tracking.fields.browser_language', true),
            'qr_dot_style' => config('filament-short-url.qr_defaults.dot_style', 'square'),
            'qr_foreground_color' => config('filament-short-url.qr_defaults.foreground_color', '#000000'),
            'qr_background_color' => config('filament-short-url.qr_defaults.background_color', '#ffffff'),
            'qr_gradient_enabled' => config('filament-short-url.qr_defaults.gradient_enabled', false),
            'qr_gradient_from' => config('filament-short-url.qr_defaults.gradient_from', '#4f46e5'),
            'qr_gradient_to' => config('filament-short-url.qr_defaults.gradient_to', '#06b6d4'),
            'qr_gradient_type' => config('filament-short-url.qr_defaults.gradient_type', 'linear'),
            'global_webhook_url' => null,
            'webhook_events' => ['visited'],
            'global_webhook_enabled' => false,
            'api_keys' => [],
            'api_enabled' => false,
            'site_name' => config('filament-short-url.site_name'),
            // Security v2.0
            'vpn_detection_enabled' => config('filament-short-url.vpn_detection.enabled', false),
            'vpn_detection_driver' => config('filament-short-url.vpn_detection.driver', 'ip-api'),
            'vpnapi_key' => config('filament-short-url.vpn_detection.vpnapi_key'),
            'vpn_block_action' => config('filament-short-url.vpn_detection.block_action', 'flag_only'),
            'vpn_detection_cache_ttl' => config('filament-short-url.vpn_detection.cache_ttl', 86400),
            'vpn_detection_timeout' => config('filament-short-url.vpn_detection.timeout', 2),
            'safe_browsing_enabled' => config('filament-short-url.safe_browsing.enabled', false),
            'google_safe_browsing_api_key' => config('filament-short-url.safe_browsing.api_key'),
            'click_deduplication_enabled' => config('filament-short-url.click_deduplication.enabled', false),
            'click_deduplication_hours' => config('filament-short-url.click_deduplication.hours', 1),
            'bot_verify_google_bot_ip' => config('filament-short-url.bot_detection.verify_google_bot_ip', false),
            'bot_debug_secret' => config('filament-short-url.bot_detection.debug_secret'),
            // Deep Linking v2.1
            'deep_linking_enabled' => config('filament-short-url.deep_linking.enabled', false),
            'aasa_json' => config('filament-short-url.deep_linking.aasa_json'),
            'assetlinks_json' => config('filament-short-url.deep_linking.assetlinks_json'),
            // Webhook signing secret
            'webhook_signing_secret' => null,
            'enable_fallback_route' => config('filament-short-url.enable_fallback_route', true),
        ], $stored);

        if (($this->cache['geo_ip_driver'] ?? '') === 'headers') {
            $this->cache['trust_cdn_headers'] = true;
        }

        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Persist settings to the database.
     *
     * @param  array<string, mixed>  $data
     */
    public function set(array $data): void
    {
        $oldPrefix = $this->get('route_prefix');

        // Keep only supported settings keys to prevent bloat
        // Canonical list of all settings that may be persisted to the database.
        // NOTE: 'enable_fallback_route' is intentionally excluded — it controls route
        // registration which happens at boot time, before DB settings are applied.
        // Configure it via config/filament-short-url.php or SHORT_URL_ENABLE_FALLBACK env variable.
        $keys = [
            'route_prefix',
            'lock_url_key',
            'disable_default_domain',
            'redirect_status_code',
            'key_length',
            'geo_ip_enabled',
            'geo_ip_driver',
            'geo_ip_cache_ttl',
            'geo_ip_timeout',
            'maxmind_database_path',
            'geo_ip_stats_cache_ttl',
            'ga4_api_secret',
            'ga4_firebase_app_id',
            'queue_connection',
            'queue_name',
            'redis_host',
            'redis_port',
            'redis_password',
            'redis_database',
            'redis_key_prefix',
            'cache_ttl',
            'counter_buffering_enabled',
            'trust_cdn_headers',
            'pruning_enabled',
            'pruning_retention_days',
            'rate_limiting_enabled',
            'rate_limiting_max_attempts',
            'rate_limiting_decay_seconds',
            'tracking_enabled',
            'tracking_anonymize_ips',
            'tracking_fields_ip_address',
            'tracking_fields_browser',
            'tracking_fields_browser_version',
            'tracking_fields_operating_system',
            'tracking_fields_operating_system_version',
            'tracking_fields_referer_url',
            'tracking_fields_device_type',
            'tracking_fields_browser_language',
            'qr_dot_style',
            'qr_foreground_color',
            'qr_background_color',
            'qr_gradient_enabled',
            'qr_gradient_from',
            'qr_gradient_to',
            'qr_gradient_type',
            'global_webhook_url',
            'webhook_events',
            'global_webhook_enabled',
            'api_keys',
            'api_enabled',
            'site_name',
            // Security v2.0
            'vpn_detection_enabled',
            'vpn_detection_driver',
            'vpnapi_key',
            'vpn_block_action',
            'vpn_detection_cache_ttl',
            'vpn_detection_timeout',
            'safe_browsing_enabled',
            'google_safe_browsing_api_key',
            'click_deduplication_enabled',
            'click_deduplication_hours',
            'bot_verify_google_bot_ip',
            'bot_debug_secret',
            // Deep Linking v2.1
            'deep_linking_enabled',
            'aasa_json',
            'assetlinks_json',
            // Webhook signing secret
            'webhook_signing_secret',
        ];

        $filtered = array_intersect_key($data, array_flip($keys));
        $filtered = $this->preserveUnchangedSecrets($filtered);
        $this->assertWebhookSigningConfigured($filtered);

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
        if (isset($filtered['geo_ip_stats_cache_ttl'])) {
            $filtered['geo_ip_stats_cache_ttl'] = (int) $filtered['geo_ip_stats_cache_ttl'];
        }
        if (isset($filtered['redis_port'])) {
            $filtered['redis_port'] = (int) $filtered['redis_port'];
        }
        if (isset($filtered['redis_database'])) {
            $filtered['redis_database'] = (int) $filtered['redis_database'];
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

        // Tracking defaults
        if (isset($filtered['tracking_enabled'])) {
            $filtered['tracking_enabled'] = (bool) $filtered['tracking_enabled'];
        }
        if (isset($filtered['tracking_anonymize_ips'])) {
            $filtered['tracking_anonymize_ips'] = (bool) $filtered['tracking_anonymize_ips'];
        }
        if (isset($filtered['tracking_fields_ip_address'])) {
            $filtered['tracking_fields_ip_address'] = (bool) $filtered['tracking_fields_ip_address'];
        }
        if (isset($filtered['tracking_fields_browser'])) {
            $filtered['tracking_fields_browser'] = (bool) $filtered['tracking_fields_browser'];
        }
        if (isset($filtered['tracking_fields_browser_version'])) {
            $filtered['tracking_fields_browser_version'] = (bool) $filtered['tracking_fields_browser_version'];
        }
        if (isset($filtered['tracking_fields_operating_system'])) {
            $filtered['tracking_fields_operating_system'] = (bool) $filtered['tracking_fields_operating_system'];
        }
        if (isset($filtered['tracking_fields_operating_system_version'])) {
            $filtered['tracking_fields_operating_system_version'] = (bool) $filtered['tracking_fields_operating_system_version'];
        }
        if (isset($filtered['tracking_fields_referer_url'])) {
            $filtered['tracking_fields_referer_url'] = (bool) $filtered['tracking_fields_referer_url'];
        }
        if (isset($filtered['tracking_fields_device_type'])) {
            $filtered['tracking_fields_device_type'] = (bool) $filtered['tracking_fields_device_type'];
        }
        if (isset($filtered['tracking_fields_browser_language'])) {
            $filtered['tracking_fields_browser_language'] = (bool) $filtered['tracking_fields_browser_language'];
        }

        if (isset($filtered['qr_gradient_enabled'])) {
            $filtered['qr_gradient_enabled'] = (bool) $filtered['qr_gradient_enabled'];
        }

        if (isset($filtered['global_webhook_enabled'])) {
            $filtered['global_webhook_enabled'] = (bool) $filtered['global_webhook_enabled'];
        }
        if (isset($filtered['api_enabled'])) {
            $filtered['api_enabled'] = (bool) $filtered['api_enabled'];
        }
        if (isset($filtered['lock_url_key'])) {
            $filtered['lock_url_key'] = (bool) $filtered['lock_url_key'];
        }
        if (isset($filtered['disable_default_domain'])) {
            $filtered['disable_default_domain'] = (bool) $filtered['disable_default_domain'];
        }

        // Security v2.0 casts
        if (isset($filtered['vpn_detection_enabled'])) {
            $filtered['vpn_detection_enabled'] = (bool) $filtered['vpn_detection_enabled'];
        }
        if (isset($filtered['safe_browsing_enabled'])) {
            $filtered['safe_browsing_enabled'] = (bool) $filtered['safe_browsing_enabled'];
        }
        if (isset($filtered['deep_linking_enabled'])) {
            $filtered['deep_linking_enabled'] = (bool) $filtered['deep_linking_enabled'];
        }
        if (isset($filtered['vpn_detection_cache_ttl'])) {
            $filtered['vpn_detection_cache_ttl'] = (int) $filtered['vpn_detection_cache_ttl'];
        }
        if (isset($filtered['vpn_detection_timeout'])) {
            $filtered['vpn_detection_timeout'] = (float) $filtered['vpn_detection_timeout'];
        }

        if (isset($filtered['click_deduplication_enabled'])) {
            $filtered['click_deduplication_enabled'] = (bool) $filtered['click_deduplication_enabled'];
        }

        if (isset($filtered['click_deduplication_hours'])) {
            $filtered['click_deduplication_hours'] = (int) $filtered['click_deduplication_hours'];
        }

        if (isset($filtered['bot_verify_google_bot_ip'])) {
            $filtered['bot_verify_google_bot_ip'] = (bool) $filtered['bot_verify_google_bot_ip'];
        }

        if (($filtered['geo_ip_driver'] ?? null) === 'headers') {
            $filtered['trust_cdn_headers'] = true;
        }

        $newKeys = [];
        if (isset($filtered['api_keys']) && is_array($filtered['api_keys'])) {
            foreach ($filtered['api_keys'] as &$keyObj) {
                $rawKey = $keyObj['key'] ?? '';

                // Only hash plain-text keys (prefix 'sh_key_' without masking characters).
                // Masked keys (containing '••••') are already stored hashed — re-hashing
                // the masked value would permanently break authentication for that key.
                $isPlainKey = str_starts_with($rawKey, 'sh_key_') && ! str_contains($rawKey, '••••');

                if ($isPlainKey) {
                    $hashed = hash('sha256', $rawKey);
                    $masked = substr($rawKey, 0, 11).'••••'.substr($rawKey, -4);

                    $keyObj['hashed_key'] = $hashed;
                    $keyObj['key'] = $masked;

                    $newKeys[] = [
                        'name' => $keyObj['name'] ?? 'API Key',
                        'plain' => $rawKey,
                    ];
                }
            }
            unset($keyObj); // Break reference from foreach

            if (! empty($newKeys)) {
                session()->flash('fsu_new_api_keys', $newKeys);
            }
        }

        try {
            if (Schema::hasTable('short_url_settings')) {
                DB::transaction(function () use ($filtered, $keys) {
                    // Update or insert each submitted key
                    foreach ($filtered as $key => $val) {
                        DB::table('short_url_settings')->updateOrInsert(
                            ['key' => $key],
                            ['value' => json_encode($val), 'updated_at' => now()]
                        );
                    }

                    // Remove keys that are no longer part of our canonical schema.
                    // We intentionally delete from the FULL $keys whitelist, NOT from
                    // array_keys($filtered) — otherwise a partial form submission
                    // (e.g. saving only the QR tab) would destroy unrelated settings
                    // such as API keys, webhook secrets, etc.
                    DB::table('short_url_settings')
                        ->whereNotIn('key', array_merge($keys, self::SYSTEM_SETTING_KEYS))
                        ->delete();
                });
            }
        } catch (\Throwable $e) {
            report($e);
            Log::error('[FilamentShortUrl] Failed to persist settings.', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('filament-short-url::default.error_failed_to_save_settings'));

            throw $e;
        }

        if (app()->bound('cache')) {
            cache()->forget('filament-short-url:settings');
        }

        // Detect counter_buffering mode switch — flush orphaned buffer caches to prevent
        // the two counting systems from diverging after a toggle.
        $oldBuffering = (bool) ($this->cache['counter_buffering_enabled'] ?? false);
        $this->cache = null;

        $newBuffering = (bool) ($filtered['counter_buffering_enabled'] ?? $oldBuffering);
        if ($oldBuffering !== $newBuffering && app()->bound('cache')) {
            $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
            cache()->forget("{$prefix}dirty_ids");
        }

        // Apply immediately to current request config
        $this->applyConfigOverrides();

        // Clear route cache if route prefix has changed
        $newPrefix = $filtered['route_prefix'] ?? null;
        if ($oldPrefix !== null && $newPrefix !== null && $oldPrefix !== $newPrefix) {
            try {
                Artisan::call('route:clear');
            } catch (\Throwable) {
                // Ignore route clear errors during boot/test
            }
        }
    }

    /**
     * Temporarily apply unsaved form values (e.g. Settings test buttons), then restore.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function withPreviewSettings(array $overrides, callable $callback): mixed
    {
        $current = $this->all();
        $preview = array_merge($current, $overrides);

        foreach (self::SECRET_SETTING_KEYS as $key) {
            if (! array_key_exists($key, $overrides)) {
                continue;
            }

            $value = $overrides[$key];

            if ($value === null || $value === '' || (is_string($value) && str_contains($value, '••••'))) {
                $preview[$key] = $current[$key] ?? null;
            }
        }

        $savedCache = $this->cache;
        $this->cache = $preview;

        try {
            $this->applyConfigOverrides();
            $this->purgeRedisConnections();

            return $callback($preview);
        } finally {
            $this->cache = $savedCache;
            $this->applyConfigOverrides();
            $this->purgeRedisConnections();
            $this->forgetRuntimeSingletons();
        }
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
            'filament-short-url.geo_ip.stats_cache_ttl' => $settings['geo_ip_stats_cache_ttl'],
            'filament-short-url.ga4.api_secret' => $settings['ga4_api_secret'],
            'filament-short-url.ga4.firebase_app_id' => $settings['ga4_firebase_app_id'],
            'filament-short-url.queue_connection' => $settings['queue_connection'],
            'filament-short-url.queue_name' => $settings['queue_name'],
            'filament-short-url.cache_ttl' => $settings['cache_ttl'],
            'filament-short-url.counter_buffering.enabled' => ($settings['queue_connection'] ?? 'sync') === 'redis'
                ? true
                : (bool) ($settings['counter_buffering_enabled'] ?? false),
            'filament-short-url.trust_cdn_headers' => ($settings['geo_ip_driver'] ?? '') === 'headers'
                ? true
                : (bool) $settings['trust_cdn_headers'],
            'filament-short-url.pruning.enabled' => $settings['pruning_enabled'],
            'filament-short-url.pruning.retention_days' => $settings['pruning_retention_days'],
            'filament-short-url.rate_limiting.enabled' => $settings['rate_limiting_enabled'],
            'filament-short-url.rate_limiting.max_attempts' => $settings['rate_limiting_max_attempts'],
            'filament-short-url.rate_limiting.decay_seconds' => $settings['rate_limiting_decay_seconds'],
            'filament-short-url.tracking.enabled' => $settings['tracking_enabled'],
            'filament-short-url.tracking.anonymize_ips' => $settings['tracking_anonymize_ips'],
            'filament-short-url.tracking.fields.ip_address' => $settings['tracking_fields_ip_address'],
            'filament-short-url.tracking.fields.browser' => $settings['tracking_fields_browser'],
            'filament-short-url.tracking.fields.browser_version' => $settings['tracking_fields_browser_version'],
            'filament-short-url.tracking.fields.operating_system' => $settings['tracking_fields_operating_system'],
            'filament-short-url.tracking.fields.operating_system_version' => $settings['tracking_fields_operating_system_version'],
            'filament-short-url.tracking.fields.referer_url' => $settings['tracking_fields_referer_url'],
            'filament-short-url.tracking.fields.device_type' => $settings['tracking_fields_device_type'],
            'filament-short-url.tracking.fields.browser_language' => $settings['tracking_fields_browser_language'],
            'filament-short-url.qr_defaults.dot_style' => $settings['qr_dot_style'],
            'filament-short-url.qr_defaults.foreground_color' => $settings['qr_foreground_color'],
            'filament-short-url.qr_defaults.background_color' => $settings['qr_background_color'],
            'filament-short-url.qr_defaults.gradient_enabled' => $settings['qr_gradient_enabled'],
            'filament-short-url.qr_defaults.gradient_from' => $settings['qr_gradient_from'],
            'filament-short-url.qr_defaults.gradient_to' => $settings['qr_gradient_to'],
            'filament-short-url.qr_defaults.gradient_type' => $settings['qr_gradient_type'],
            'filament-short-url.global_webhook_url' => $settings['global_webhook_url'] ?? null,
            'filament-short-url.global_webhook_enabled' => (bool) ($settings['global_webhook_enabled'] ?? false),
            'filament-short-url.webhook_events' => $settings['webhook_events'] ?? ['visited'],
            'filament-short-url.api_enabled' => (bool) ($settings['api_enabled'] ?? false),
            'filament-short-url.site_name' => $settings['site_name'] ?? null,
            'filament-short-url.lock_url_key' => (bool) ($settings['lock_url_key'] ?? false),
            'filament-short-url.disable_default_domain' => (bool) ($settings['disable_default_domain'] ?? false),
            // Security v2.0
            'filament-short-url.vpn_detection.enabled' => (bool) ($settings['vpn_detection_enabled'] ?? false),
            'filament-short-url.vpn_detection.driver' => $settings['vpn_detection_driver'] ?? 'ip-api',
            'filament-short-url.vpn_detection.vpnapi_key' => $settings['vpnapi_key'] ?? null,
            'filament-short-url.vpn_detection.block_action' => $settings['vpn_block_action'] ?? 'flag_only',
            'filament-short-url.vpn_detection.cache_ttl' => (int) ($settings['vpn_detection_cache_ttl'] ?? 86400),
            'filament-short-url.vpn_detection.timeout' => (float) ($settings['vpn_detection_timeout'] ?? 2),
            'filament-short-url.safe_browsing.enabled' => (bool) ($settings['safe_browsing_enabled'] ?? false),
            'filament-short-url.safe_browsing.api_key' => $settings['google_safe_browsing_api_key'] ?? null,
            'filament-short-url.safe_browsing.check_on_redirect' => (bool) config('filament-short-url.safe_browsing.check_on_redirect', true),
            'filament-short-url.safe_browsing.redirect_cache_ttl' => (int) config('filament-short-url.safe_browsing.redirect_cache_ttl', 3600),
            'filament-short-url.click_deduplication.enabled' => (bool) ($settings['click_deduplication_enabled'] ?? false),
            'filament-short-url.click_deduplication.hours' => (int) ($settings['click_deduplication_hours'] ?? 1),
            'filament-short-url.bot_detection.verify_google_bot_ip' => (bool) ($settings['bot_verify_google_bot_ip'] ?? false),
            'filament-short-url.bot_detection.debug_secret' => $settings['bot_debug_secret'] ?? null,
            // Deep Linking v2.1
            'filament-short-url.deep_linking.enabled' => (bool) ($settings['deep_linking_enabled'] ?? false),
            'filament-short-url.deep_linking.aasa_json' => $settings['aasa_json'] ?? null,
            'filament-short-url.deep_linking.assetlinks_json' => $settings['assetlinks_json'] ?? null,
            // Webhook signing secret
            'filament-short-url.webhook_signing_secret' => $settings['webhook_signing_secret'] ?? null,
            'filament-short-url.webhook_signing_required' => (bool) config('filament-short-url.webhook_signing_required', true),
            'filament-short-url.scope_links_to_user' => (bool) config('filament-short-url.scope_links_to_user', true),
            'filament-short-url.redis.host' => $settings['redis_host'] ?? config('database.redis.default.host', '127.0.0.1'),
            'filament-short-url.redis.port' => (int) ($settings['redis_port'] ?? config('database.redis.default.port', 6379)),
            'filament-short-url.redis.password' => $settings['redis_password'] ?? config('database.redis.default.password'),
            'filament-short-url.redis.database' => (int) ($settings['redis_database'] ?? config('database.redis.default.database', 0)),
            'filament-short-url.redis.prefix' => $settings['redis_key_prefix'] ?? config('database.redis.options.prefix', ''),
        ]);

        $this->applyRedisInfrastructureOverrides($settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applyRedisInfrastructureOverrides(array $settings): void
    {
        if (($settings['queue_connection'] ?? 'sync') !== 'redis') {
            return;
        }

        $redisConnectionName = (string) (config('queue.connections.redis.connection') ?? 'default');
        $baseRedis = config("database.redis.{$redisConnectionName}", config('database.redis.default', []));

        if (! is_array($baseRedis)) {
            $baseRedis = [];
        }

        $host = filled($settings['redis_host'] ?? null)
            ? (string) $settings['redis_host']
            : (string) ($baseRedis['host'] ?? '127.0.0.1');
        $port = (int) ($settings['redis_port'] ?? $baseRedis['port'] ?? 6379);
        $password = array_key_exists('redis_password', $settings)
            ? $settings['redis_password']
            : ($baseRedis['password'] ?? null);
        $database = (int) ($settings['redis_database'] ?? $baseRedis['database'] ?? 0);
        $prefix = array_key_exists('redis_key_prefix', $settings)
            ? (string) ($settings['redis_key_prefix'] ?? '')
            : (string) config('database.redis.options.prefix', '');

        config([
            "database.redis.{$redisConnectionName}" => array_merge($baseRedis, [
                'host' => $host,
                'port' => $port,
                'password' => $password,
                'database' => $database,
            ]),
            'database.redis.options.prefix' => $prefix,
            'queue.connections.redis' => array_merge(
                is_array(config('queue.connections.redis')) ? config('queue.connections.redis') : [],
                [
                    'driver' => 'redis',
                    'connection' => $redisConnectionName,
                    'queue' => (string) ($settings['queue_name'] ?? config('filament-short-url.queue_name', 'default')),
                    'retry_after' => (int) (config('queue.connections.redis.retry_after') ?? 90),
                    'block_for' => config('queue.connections.redis.block_for'),
                    'after_commit' => (bool) (config('queue.connections.redis.after_commit') ?? false),
                ],
            ),
        ]);
    }

    private function purgeRedisConnections(): void
    {
        try {
            $connectionName = (string) (config('queue.connections.redis.connection') ?? 'default');
            Redis::purge($connectionName);
        } catch (\Throwable) {
            // Ignore during boot/tests when Redis is unavailable.
        }
    }

    private function forgetRuntimeSingletons(): void
    {
        app()->forgetInstance(PluginRedisConnection::class);
        app()->forgetInstance(VisitCounterBuffer::class);
        app()->forgetInstance(TodayStatsBuffer::class);
        app()->forgetInstance(StatsScalingProfile::class);
    }

    /**
     * @param  array<string, mixed>  $filtered
     */
    private function assertWebhookSigningConfigured(array $filtered): void
    {
        if (! (bool) config('filament-short-url.webhook_signing_required', true)) {
            return;
        }

        $globalEnabled = (bool) ($filtered['global_webhook_enabled'] ?? $this->get('global_webhook_enabled', false));

        if (! $globalEnabled) {
            return;
        }

        $secret = $filtered['webhook_signing_secret'] ?? null;

        if (is_string($secret) && str_contains($secret, '••••')) {
            $secret = $this->get('webhook_signing_secret');
        }

        if (blank($secret)) {
            throw ValidationException::withMessages([
                'data.webhook_signing_secret' => __('filament-short-url::default.webhook_signing_secret_required'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $filtered
     * @return array<string, mixed>
     */
    private function preserveUnchangedSecrets(array $filtered): array
    {
        foreach (self::SECRET_SETTING_KEYS as $key) {
            if (! array_key_exists($key, $filtered)) {
                continue;
            }

            $value = $filtered[$key];

            if ($value === null || $value === '') {
                unset($filtered[$key]);

                continue;
            }

            if (is_string($value) && str_contains($value, '••••')) {
                unset($filtered[$key]);
            }
        }

        return $filtered;
    }
}
