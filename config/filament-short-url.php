<?php

use App\Models\User;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;

return [

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    | The URL prefix for short URL redirects. Short URLs will be accessible at:
    | /{prefix}/{key}  e.g.  /s/abc123
    */
    'route_prefix' => env('SHORT_URL_PREFIX', 's'),

    /*
    |--------------------------------------------------------------------------
    | Site Name Override
    |--------------------------------------------------------------------------
    | The brand or site name displayed on the password prompt, redirect warnings,
    | and pixel loading screens. Falls back to config('app.name') if empty.
    |
    */
    'site_name' => env('SHORT_URL_SITE_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Default Redirect Status Code
    |--------------------------------------------------------------------------
    | Use 302 for better tracking accuracy. 301 redirects are cached by browsers,
    | which means subsequent visits won't trigger the tracking logic.
    */
    'redirect_status_code' => 302,

    /*
    |--------------------------------------------------------------------------
    | Key Generation
    |--------------------------------------------------------------------------
    | Length of auto-generated URL keys (base62: a-z, A-Z, 0-9).
    | 6 chars = 56 billion possible keys.
    */
    'key_length' => 6,

    /*
    |--------------------------------------------------------------------------
    | Lock URL Key
    |--------------------------------------------------------------------------
    | Globally disable changing the short key after a link is created.
    |
    */
    'lock_url_key' => env('SHORT_URL_LOCK_KEY', false),

    /*
    |--------------------------------------------------------------------------
    | Disable Default Domain
    |--------------------------------------------------------------------------
    | Disable the default app domain for short links.
    |
    */
    'disable_default_domain' => env('SHORT_URL_DISABLE_DEFAULT_DOMAIN', false),

    /*
    |--------------------------------------------------------------------------
    | Geo-IP Settings
    |--------------------------------------------------------------------------
    | Country detection from visitor IP addresses.
    | Uses ip-api.com (free, no key required, 45 req/min).
    | Results are cached per IP for cache_ttl seconds.
    */
    'geo_ip' => [
        'enabled' => env('SHORT_URL_GEO_IP', true),
        'cache_ttl' => 86400, // 24 hours
        'driver' => env('SHORT_URL_GEO_IP_DRIVER', 'headers'), // 'headers', 'maxmind', 'ip-api'
        'timeout' => 3, // seconds to wait for geo-ip response
        'maxmind' => [
            'database_path' => env('SHORT_URL_MAXMIND_DB', storage_path('geoip/GeoLite2-Country.mmdb')),
        ],
        'stats_cache_ttl' => env('SHORT_URL_STATS_CACHE_TTL', 300), // 5 minutes caching for stats page calculations
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tracking Configuration
    |--------------------------------------------------------------------------
    | These are the default tracking settings for newly created short URLs.
    | Each can be overridden per-URL in the Filament admin panel.
    */
    'tracking' => [
        'enabled' => true,
        'anonymize_ips' => env('SHORT_URL_ANONYMIZE_IPS', false),
        'fields' => [
            'ip_address' => true,
            'browser' => true,
            'browser_version' => true,
            'operating_system' => true,
            'operating_system_version' => true,
            'referer_url' => true,
            'device_type' => true,
            'browser_language' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Analytics 4 — Measurement Protocol
    |--------------------------------------------------------------------------
    | When a GA Tracking ID is set on a short URL, server-side events are sent
    | to GA4 via the Measurement Protocol API.
    | Get your API Secret from: GA4 → Admin → Data Streams → Measurement Protocol API secrets
    */
    'ga4' => [
        'api_secret' => env('GA4_API_SECRET'),
        'firebase_app_id' => env('FIREBASE_APP_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    | Visit tracking is dispatched to a queue for ultra-fast redirects.
    | Set to null to run synchronously (not recommended in production).
    */
    'queue_connection' => env('SHORT_URL_QUEUE', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    | The queue name to which visit tracking and counter jobs are dispatched.
    |
    */
    'queue_name' => env('SHORT_URL_QUEUE_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Redirect Cache TTL
    |--------------------------------------------------------------------------
    | Short URL records are cached so redirects never hit the database on hot
    | paths. Cache is automatically invalidated when a URL is saved or deleted.
    | Set to 0 to disable caching (useful for testing). Default: 3600 (1 hour).
    */
    'cache_ttl' => (int) env('SHORT_URL_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Override these to use your own custom model classes.
    */
    'models' => [
        'short_url' => ShortUrl::class,
        'short_url_visit' => ShortUrlVisit::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default QR Code Options
    |--------------------------------------------------------------------------
    | Default design settings for newly generated QR codes.
    */
    'qr_defaults' => [
        'size' => 300,
        'margin' => 1,
        'dot_style' => 'square',
        'foreground_color' => '#000000',
        'background_color' => '#ffffff',
        'gradient_enabled' => false,
        'gradient_from' => '#4f46e5',
        'gradient_to' => '#06b6d4',
        'gradient_type' => 'linear',
    ],

    /*
    |--------------------------------------------------------------------------
    | Counter Buffering (Write-back Caching)
    |--------------------------------------------------------------------------
    | For high-traffic applications, direct database writes on every visit
    | can cause locks. Enable this to buffer total/unique visit counts in
    | cache, then sync them to the database periodically via a scheduled task.
    */
    'counter_buffering' => [
        'enabled' => (bool) env('SHORT_URL_COUNTER_BUFFERING', false),
        'cache_key_prefix' => 'filament-short-url:buffer:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trust CDN/Proxy Headers
    |--------------------------------------------------------------------------
    | If your application sits behind a CDN (like Cloudflare, AWS CloudFront)
    | or a reverse proxy, set this to true to parse real visitor IP addresses
    | and country codes from proxy headers. Only enable this if you are
    | actually behind a proxy to prevent client IP spoofing!
    |
    */
    'trust_cdn_headers' => (bool) env('SHORT_URL_TRUST_CDN_HEADERS', false),

    /*
    |--------------------------------------------------------------------------
    | Data Pruning & Aggregation
    |--------------------------------------------------------------------------
    | To keep the database clean and fast, raw visit logs can be aggregated
    | into daily statistics and pruned after a retention period.
    |
    */
    'pruning' => [
        'enabled' => env('SHORT_URL_PRUNING_ENABLED', true),
        'retention_days' => env('SHORT_URL_PRUNING_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | Protect redirect routes from bot abuse and brute force by enabling
    | rate limiting.
    |
    */
    'rate_limiting' => [
        'enabled' => env('SHORT_URL_RATE_LIMITING', false),
        'max_attempts' => env('SHORT_URL_RATE_LIMIT_MAX', 60),
        'decay_seconds' => env('SHORT_URL_RATE_LIMIT_DECAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable Fallback Route
    |--------------------------------------------------------------------------
    | Register a fallback route to catch redirects for custom domains.
    | Set to false if you wish to define custom domain routing manually.
    */
    'enable_fallback_route' => env('SHORT_URL_ENABLE_FALLBACK', true),

    /*
    |--------------------------------------------------------------------------
    | Redirect Route Middleware
    |--------------------------------------------------------------------------
    | The middleware list applied to the short URL redirect route.
    | By default, standard web middleware and rate limiting are applied.
    |
    */
    'middleware' => [
        'web',
        'throttle:120,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Deep Linking v2.1
    |--------------------------------------------------------------------------
    | Universal Links (iOS) and App Links (Android) configuration.
    |
    */
    'deep_linking' => [
        'enabled' => env('SHORT_URL_DEEP_LINKING_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Integration Settings
    |--------------------------------------------------------------------------
    | Configure how the package interacts with the User model for showing avatars
    | and user details (name, email) in the Filament admin panel.
    |
    */
    'user' => [
        'model' => User::class,
        'name_column' => 'name',
        'email_column' => 'email',
        'avatar_column' => 'avatar_url', // can be attribute/method on model or null to auto-detect HasAvatar
    ],

];
