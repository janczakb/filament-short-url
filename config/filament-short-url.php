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
    | Custom Domains
    |--------------------------------------------------------------------------
    | enforce_dns_on_activate: when true, activating a domain (or changing its
    | hostname while active) requires a passing DNS check against app.url.
    */
    'custom_domains' => [
        'enforce_dns_on_activate' => env('SHORT_URL_CUSTOM_DOMAIN_ENFORCE_DNS', true),
    ],

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
    | Default is `sync` (no background worker required). Switch to `database` or
    | `redis` in Settings or via SHORT_URL_QUEUE when you run a queue worker.
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
    | Redis (Settings override when Queue Connection = redis)
    |--------------------------------------------------------------------------
    | Defaults from .env; overridden at runtime from Settings → General when
    | queue_connection is redis. Used for queue driver, visit counters, stats,
    | and live feed — independent of CACHE_STORE.
    */
    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => (int) env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD'),
        'database' => (int) env('REDIS_DB', 0),
        'prefix' => env('REDIS_PREFIX', ''),
    ],

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
    | Developer REST API
    |--------------------------------------------------------------------------
    | Enable programmatic access to short URLs via /api/short-url/* endpoints.
    | API keys are managed in the Filament settings panel.
    |
    */
    'api_enabled' => env('SHORT_URL_API_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | VPN & Proxy Detection
    |--------------------------------------------------------------------------
    | Detect VPN, proxy, and Tor connections on incoming visits.
    |
    */
    'vpn_detection' => [
        'enabled' => env('SHORT_URL_VPN_DETECTION', false),
        'driver' => env('SHORT_URL_VPN_DRIVER', 'ip-api'),
        'vpnapi_key' => env('SHORT_URL_VPNAPI_KEY'),
        'block_action' => env('SHORT_URL_VPN_BLOCK_ACTION', 'flag_only'),
        'cache_ttl' => 86400,
        'timeout' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Safe Browsing
    |--------------------------------------------------------------------------
    | Block phishing and malware URLs at creation time.
    |
    */
    'safe_browsing' => [
        'enabled' => env('SHORT_URL_SAFE_BROWSING', false),
        'api_key' => env('SHORT_URL_SAFE_BROWSING_KEY'),
        'check_on_redirect' => env('SHORT_URL_SAFE_BROWSING_ON_REDIRECT', true),
        'redirect_cache_ttl' => (int) env('SHORT_URL_SAFE_BROWSING_REDIRECT_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Click Deduplication
    |--------------------------------------------------------------------------
    | Ignore repeated clicks from the same IP within the configured window.
    |
    */
    'click_deduplication' => [
        'enabled' => env('SHORT_URL_CLICK_DEDUP', false),
        'hours' => (int) env('SHORT_URL_CLICK_DEDUP_HOURS', 1),
    ],

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
    | Middleware applied to the main short URL redirect route (/s/{key}).
    | Intentionally excludes the "web" group (sessions/cookies) for a lean
    | hot path. Password routes always load "web" separately (/s-auth/{key}).
    | Add "web" here only if your app requires session on every redirect.
    |
    */
    'middleware' => [
        'throttle:120,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Link ownership scoping
    |--------------------------------------------------------------------------
    | When true, authenticated Filament users only see links they created
    | (short_urls.user_id). Disable for shared admin panels.
    |
    */
    'scope_links_to_user' => env('SHORT_URL_SCOPE_TO_USER', true),

    /*
    |--------------------------------------------------------------------------
    | Max visits pessimistic locking
    |--------------------------------------------------------------------------
    | When remaining slots are above this threshold, use a lock-free UPDATE path.
    | Near the cap, a row lock is used to stay exact under concurrent traffic.
    */
    'max_visits_pessimistic_remaining' => (int) env('SHORT_URL_MAX_VISITS_PESSIMISTIC_REMAINING', 5),

    /*
    |--------------------------------------------------------------------------
    | Live feed (SSE)
    |--------------------------------------------------------------------------
    */
    'live_feed' => [
        'sse_interval_seconds' => (int) env('SHORT_URL_LIVE_FEED_SSE_INTERVAL', 3),
        'sse_max_duration_seconds' => (int) env('SHORT_URL_LIVE_FEED_SSE_MAX_DURATION', 120),
        // When the cache store is Redis, SSE blocks on pub/sub instead of sleep-polling.
        'use_redis_push' => env('SHORT_URL_LIVE_FEED_REDIS_PUSH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook signing
    |--------------------------------------------------------------------------
    | Require a signing secret before dispatching global webhooks.
    |
    */
    'webhook_signing_required' => env('SHORT_URL_WEBHOOK_SIGNING_REQUIRED', true),

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

    /*
    |--------------------------------------------------------------------------
    | Bot Detection
    |--------------------------------------------------------------------------
    | Patterns used to identify crawlers and link-preview bots. Specific tokens
    | avoid false positives from real browsers (e.g. Chrome never matches "googlebot").
    |
    */
    'bot_detection' => [
        'user_agent_contains' => [
            'facebookexternalhit',
            'meta-externalagent',
            'meta-externalfetcher',
            'meta-externalads',
            'meta-webindexer',
            'Googlebot',
            'Google-InspectionTool',
            'GoogleOther',
            'Storebot-Google',
            'bingbot',
            'BingPreview',
            'DuckDuckBot',
            'Slurp',
            'Baiduspider',
            'YandexBot',
            'Applebot',
            'Applebot-Extended',
            'Twitterbot',
            'LinkedInBot',
            'linkedinbot',
            'Slackbot',
            'Slack-ImgProxy',
            'Discordbot',
            'TelegramBot',
            'WhatsApp',
            'Pinterestbot',
            'Embedly',
            'Iframely',
            'SkypeUriPreview',
            'facebookcatalog',
            'MetaInspector',
            'vkShare',
            'Tumblr',
            'redditbot',
            'Snap URL Preview',
            'Snapchat',
            'ChatGPT-User',
            'Claude-Web',
            'anthropic-ai',
            'PerplexityBot',
            'Bytespider',
            'GPTBot',
            'OAI-SearchBot',
            'HeadlessChrome',
            'Go-http-client',
            'python-requests',
            'axios/',
            'GuzzleHttp',
            'PostmanRuntime',
            'Insomnia',
            'Scrapy',
            'FeedBurner',
            'W3C_Validator',
            'Validator.nu',
            'PocketParser',
            'BitlyBot',
            'rogerbot',
            'SemrushBot',
            'AhrefsBot',
            'MJ12bot',
            'DotBot',
            'PetalBot',
            'Sogou',
            'ia_archiver',
            'archive.org_bot',
            'Wayback',
            'UptimeRobot',
            'StatusCake',
            'Pingdom',
            'GTmetrix',
            'Bluesky',
            'thirdLandingPageFeInfra',
            'ShortLinkTranslate',
        ],
        'user_agent_regex' => [
            '/bot[\s\/;\)]/i',
            '/crawler[\s\/;\)]/i',
            '/spider[\s\/;\)]/i',
            '/\bscraper\b/i',
            '/\bslurp\b/i',
            '/\bpreview\b/i',
            '/^curl\//i',
            '/^wget\//i',
            '/\bPHP\/[\d.]+/i',
        ],
        'referer_contains' => [
            'url.emailprotection.link',
            'urlsand.com',
            'statics.teams.cdn.office.net',
            'security-za.m.mimecastprotect.com',
            'deref-mail.com',
            'deref-gmx.com',
        ],
        'verify_google_bot_ip' => env('SHORT_URL_VERIFY_GOOGLEBOT_IP', false),
        'debug_secret' => env('SHORT_URL_BOT_DEBUG_SECRET'),
    ],

];
