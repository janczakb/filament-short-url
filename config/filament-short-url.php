<?php

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
            'database_path' => env('SHORT_URL_MAXMIND_DB', database_path('geoip/GeoLite2-Country.mmdb')),
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
        'fields' => [
            'ip_address' => true,
            'browser' => true,
            'browser_version' => true,
            'operating_system' => true,
            'operating_system_version' => true,
            'referer_url' => true,
            'device_type' => true,
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
        'gradient_from' => '#000000',
        'gradient_to' => '#ffffff',
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

];
