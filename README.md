<p align="center">
    <img src="https://cdn3d.iconscout.com/3d/premium/thumb/url-3d-icon-png-download-9292351.png" width="180" alt="Filament Short URL Logo">
</p>

<h1 align="center">Filament Short URL</h1>

<p align="center">
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/v/janczakb/filament-short-url.svg?style=flat-square" alt="Latest Version"></a>
    <a href="https://github.com/janczakb/filament-short-url/blob/main/LICENSE"><img src="https://img.shields.io/github/license/janczakb/filament-short-url.svg?style=flat-square" alt="License"></a>
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/dt/janczakb/filament-short-url.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/janczakb/filament-short-url/stargazers"><img src="https://img.shields.io/github/stars/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Stars"></a>
    <a href="https://github.com/janczakb/filament-short-url/issues"><img src="https://img.shields.io/github/issues/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Issues"></a>
    <a href="https://github.com/janczakb/filament-short-url/actions"><img src="https://img.shields.io/badge/tests-passing-success.svg?style=flat-square" alt="Tests"></a>
</p>

A professional, high-performance **Short URL Manager** plugin for [Filament v5](https://filamentphp.com). Built from scratch with cutting-edge practices, proxy resistance, offline Geo-IP engines, enterprise-grade smart targeting, and zero external shortening API dependencies.

---

## Features

- 🔗 **Short URL Generation** — Create custom links or let the system auto-generate collision-free Base62 keys.
- 🌍 **Multiple Geo-IP Drivers** — High-speed offline detection using local MaxMind databases, edge-provided CDN headers (Cloudflare, CloudFront, generic), or fallback API integration.
- 📈 **Real-Time Statistics Dashboard** — Track visits in real-time with cached aggregate metrics (countries, devices, browsers, operating systems, referrers, traffic charts, and maps).
- 🎨 **SVG QR Code Designer** — Built-in interactive design canvas in Filament to customize dot styles, margins, gradient coloring, and background transparency with instant SVG download.
- ⚡ **Ultra-Fast Redirects** — Redirections resolve in milliseconds. Analytical tasks, event dispatching, and GA4 payloads are processed asynchronously via Laravel Queue jobs.
- 🎯 **Google Analytics 4 server-side tracking** — Native integration with the GA4 Measurement Protocol to bypass client-side AdBlockers completely.
- ⚙️ **Dual-way UTM Campaign Builder** — Built-in form builder synchronizes UTM parameters with your destination URLs in real-time (two-way binding).
- 🔒 **Link Rules & Expiry** — Disable links manually, set expiration dates, or activate single-use links that deactivate automatically after the first click.
- ➡️ **Query Parameter Forwarding** — Dynamically forward client query parameters (e.g. ad tokens, discount codes) to the destination URL.
- 🛠️ **Dedicated Settings GUI** — Manage global configuration (routing, Geo-IP, GA4, cache, rate limiting, aggregation) directly inside the Filament panel without modifying files or `.env` files.
- 💻 **Fluent Developer Builder** — Native model query builder pattern and robust programmatic generation APIs.
- 🔑 **Password-Protected Links** — Require a password before redirecting visitors, with session-based unlock.
- ⚠️ **Redirect Warning Pages** — Show an interstitial security page before redirecting to external URLs (phishing/NSFW protection).
- 🎯 **Smart Link Targeting** — Route visitors to different destination URLs based on their device type, country, or via weighted A/B split rotation.
- 🛡️ **Rate Limiting / Bot Protection** — Configurable per-IP rate limits on redirects with automatic `429 Too Many Requests` responses.
- 📊 **Daily Stats Aggregation & Pruning** — Automatic daily summarization of raw visit logs into compact daily stats tables. Configurable retention window prevents unbounded database growth at scale.

---

## Requirements

- PHP 8.3+
- Laravel 11+
- Filament 5+

---

## Installation

Install the package via Composer:

```bash
composer require janczakb/filament-short-url
```

Publish and run the database migrations:

```bash
php artisan vendor:publish --tag=filament-short-url-migrations
php artisan migrate
```

---

## Publishing Package Assets

Customize and override views, translations, or configuration files by publishing the package assets:

### 1. Publish Config File
Copies the default config file to `config/filament-short-url.php`:
```bash
php artisan vendor:publish --tag=filament-short-url-config
```

### 2. Publish Translation Files
Copies localization files to `lang/vendor/filament-short-url/` (English and Polish included by default):
```bash
php artisan vendor:publish --tag=filament-short-url-translations
```

### 3. Publish Blade Views & Templates
Copies the dashboard components, charts, and QR designer templates to `resources/views/vendor/filament-short-url/`:
```bash
php artisan vendor:publish --tag=filament-short-url-views
```

### 4. Publish CSS Assets

The plugin ships with a pre-compiled stylesheet. Copy it to your application's public directory so the browser can load it:

```bash
php artisan filament:assets
```

That's all. The plugin's CSS will be served from `public/css/janczakb/filament-short-url/filament-short-url.css` and Filament registers it automatically.

> **You do not need to run Tailwind, Vite, or npm for the plugin styles.** The compiled file is included in the package.

**Tip — automate on every `composer install` / `composer update`:**

Add `filament:assets` to the `post-autoload-dump` scripts in your application's `composer.json` so the assets are always up to date without manual steps:

```json
"scripts": {
    "post-autoload-dump": [
        "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
        "@php artisan package:discover --ansi",
        "@php artisan filament:assets"
    ]
}
```

---

> #### 🛠 For Plugin Developers Only
>
> If you are modifying the plugin source and need to recompile its stylesheet after changing Blade/PHP files:
>
> ```bash
> # 1. Recompile the plugin CSS with Tailwind v4
> npx @tailwindcss/cli -i ./packages/filament-short-url/resources/css/plugin.css \
>     -o ./packages/filament-short-url/resources/dist/filament-short-url.css --minify
>
> # 2. Re-publish the compiled asset to public/
> php artisan filament:assets
> ```

---

## Setup

Register the plugin in your Filament Panel Provider (`app/Providers/Filament/AdminPanelProvider.php`):

```php
use Bjanczak\FilamentShortUrl\FilamentShortUrlPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentShortUrlPlugin::make()
                ->navigationGroup('Marketing')      // optional — sidebar group name
                ->navigationLabel('Short Links')    // optional — override menu item name
                ->navigationIcon('heroicon-o-link') // optional — override menu icon
                ->navigationSort(50),               // optional — sort order in sidebar
        ]);
}
```

### Navigation Configuration Options
All fluent methods on the plugin are optional. If not called, the plugin falls back to defaults or translation files:

| Method | Default | Description |
|--------|---------|-------------|
| `navigationGroup(string)` | `null` | Groups the resource menu item under a sidebar section. |
| `navigationLabel(string)` | `'Short URLs'` | Overrides the menu item display name. |
| `navigationIcon(string)` | `heroicon-o-link` | Overrides the Heroicon used in the sidebar. |
| `navigationSort(int)` | `50` | Controls the sort order within the navigation list. |

---

## Global Settings GUI

The package comes with a built-in admin settings dashboard. You can access it by clicking the **Settings** action button on the top-right header of the Short URLs resource.

Settings are stored dynamically in `storage/app/filament-short-url-settings.json` and immediately override config defaults.

The settings panel allows you to configure:

### 1. General Routing & Queueing
*   **Route Prefix**: The slug prepended to short URLs (e.g. `s` for `/s/{key}`).
*   **Default Redirect Status**: Choose `302 (Found / Temporary)` or `301 (Moved Permanently)`.
    *   *Note: `302` is highly recommended for analytics accuracy because browsers cache `301` redirects, skipping subsequent logs.*
*   **Key Length**: Default character count (base62) for auto-generated keys (default: `6`).
*   **Queue Connection**: Define the Laravel queue connection (e.g. `redis`, `database`, `sync`) used for processing visit analytics asynchronously.

### 2. Geo-IP Country Detection
Toggle country tracking and select from three drivers:
*   **Headers** (Edge Resolution): Automatically detects client country using standard edge headers (e.g. Cloudflare's `CF-IPCountry`, AWS CloudFront's `CloudFront-Viewer-Country`, or generic proxies).
*   **MaxMind** (Offline Resolution): Reads from a local GeoIP2 database (such as the free GeoLite2-Country database).
*   **IP-API** (Online Fallback): Makes an external API call to `ip-api.com` with configurable timeout.

### 3. Google Analytics 4 (GA4) Integration
Sends server-side `short_url_visit` hits using the **GA4 Measurement Protocol API**. This bypasses browser-side AdBlockers entirely.
*   **GA4 API Secret**: Create this secret in Google Analytics under `Admin -> Data Streams -> Measurement Protocol API secrets`.
*   **Firebase App ID / Measurement ID**: The target analytics stream identifier.

### 4. Counter Buffering (Write-back Caching)
For extremely high-traffic applications, direct database writes for click counts can cause row-locking bottlenecks.
*   **Buffer Click Counts**: Toggling this option buffers total and unique visit count increments in the application cache.
*   **Cron Synchronization**: When enabled, you must schedule the synchronization command to run periodically (e.g., every minute) to flush counts to the database:
    ```bash
    php artisan short-url:sync-counters
    ```
    In your scheduler (`routes/console.php` or `app/Console/Kernel.php`):
    ```php
    $schedule->command('short-url:sync-counters')->everyMinute();
    ```

### 5. Performance & Security Tab (new in v1.2.0)

#### High-Traffic Log Management (Aggregation & Pruning)
At scale, the `short_url_visits` table can grow to tens of gigabytes. The aggregation system solves this:
*   **Enable Daily Aggregation**: When enabled, the nightly `short-url:aggregate-and-prune` command summarizes the previous day's raw visit records into the compact `short_url_daily_stats` table.
*   **Prune Raw Logs After (days)**: Raw visit records older than this threshold are permanently deleted after aggregation. Set to `0` to disable pruning. Default: `90` days.

Schedule the command in your scheduler:
```php
$schedule->command('short-url:aggregate-and-prune')->dailyAt('02:00');
```

#### Rate Limiting / Bot Protection
Prevent redirect abuse and bot traffic flooding:
*   **Enable Rate Limiting**: Activates per-IP rate limiting on all redirect routes.
*   **Max Redirects Allowed**: Maximum number of redirect requests per IP within the decay window. Default: `60`.
*   **Decay Window (seconds)**: The rolling time window for the rate limiter. Default: `60` seconds.

When a client exceeds the limit, a `429 Too Many Requests` response is returned with a `Retry-After` header.

---

## Password-Protected Links (new in v1.2.0)

You can require visitors to enter a password before being redirected. Enable this in the **Targeting & Security** tab of the short URL form:

- Set a plain-text password in the **Access Password** field.
- Visitors will see a styled password prompt page before gaining access.
- The unlock state is stored in the PHP session — visitors only need to enter the password once per session.

```php
// Programmatically — set via fillable attributes
$shortUrl = ShortUrl::destination('https://secret.example.com')
    ->create();

$shortUrl->update(['password' => 'my-secret-pass']);
```

> **Note**: Passwords are currently stored as plain text. For sensitive use-cases, hash the password and compare with `Hash::check()` by overriding the redirect controller.

---

## Redirect Warning Pages (new in v1.2.0)

Enable the **Show Redirect Warning Page** toggle in the **Targeting & Security** tab to display a safety interstitial before redirecting.

The warning page:
- Shows the destination URL clearly so visitors can verify they trust it.
- Provides **Continue** and **Go Back** buttons.
- Is confirmed via a `?confirmed=1` query parameter — no additional session storage required.
- Is styled to match the password prompt page (glassmorphism, dark mode compatible).

This feature is useful for NSFW links, external partner links, or any URL that leaves a trusted domain.

---

## Smart Link Targeting (new in v1.2.0)

The **Targeting & Security** tab exposes a powerful rule engine that lets you route different visitors to different destinations — all from a single short URL.

### Available Strategies

#### 1. Device-Based Redirects
Route visitors to different URLs based on their device type (detected from User-Agent):

| Device | Detected by User-Agent containing |
|--------|-----------------------------------|
| iOS (Mobile) | `iphone`, `ipad`, `ipod` |
| Android | `android` |
| Desktop | Everything else |

```php
// Programmatic example
$shortUrl->update([
    'targeting_rules' => [
        'type' => 'device',
        'device' => [
            'ios'     => 'https://apps.apple.com/your-app',
            'android' => 'https://play.google.com/your-app',
            'desktop' => 'https://example.com/download',
        ],
    ],
]);
```

#### 2. Country-Based (Geo-IP) Redirects
Route visitors to country-specific URLs. Requires Geo-IP to be enabled in settings. Falls back to the default `destination_url` for unlisted countries.

```php
$shortUrl->update([
    'targeting_rules' => [
        'type' => 'geo',
        'geo' => [
            ['country_code' => 'PL', 'url' => 'https://pl.example.com'],
            ['country_code' => 'US', 'url' => 'https://us.example.com'],
            ['country_code' => 'DE', 'url' => 'https://de.example.com'],
        ],
    ],
]);
```

#### 3. A/B Split Rotation
Distribute traffic across multiple URLs using weighted random selection. Weights are proportional — they do not need to sum to 100.

```php
$shortUrl->update([
    'targeting_rules' => [
        'type' => 'rotation',
        'rotation' => [
            ['url' => 'https://variant-a.example.com', 'weight' => 70],
            ['url' => 'https://variant-b.example.com', 'weight' => 30],
        ],
    ],
]);
```

> All targeting strategies fall back gracefully to the link's primary `destination_url` if no rule matches.

---

## High-Traffic Optimizations (new in v1.2.0)

### Daily Stats Aggregation

The `short_url_daily_stats` table stores pre-aggregated daily summaries per short URL. Each row contains:

| Column | Description |
|--------|-------------|
| `date` | The calendar day |
| `visits_count` | Total visits |
| `unique_visits_count` | Unique visitors (by hashed IP) |
| `device_stats` | JSON — visit counts by device type |
| `browser_stats` | JSON — visit counts by browser |
| `os_stats` | JSON — visit counts by operating system |
| `country_stats` | JSON — visit counts by country |
| `city_stats` | JSON — visit counts by city |
| `referer_stats` | JSON — visit counts by referer domain |
| `utm_source_stats` | JSON — visit counts by UTM source |
| `utm_medium_stats` | JSON — visit counts by UTM medium |
| `utm_campaign_stats` | JSON — visit counts by UTM campaign |

The `getCachedStats()` model method **automatically merges** data from both tables: historical days come from `short_url_daily_stats`, while today's data comes directly from `short_url_visits` — completely transparent to the dashboard.

### Queue-Based Counter Fallback

When Redis is not available and counter buffering is enabled, the `IncrementVisitJob` is dispatched to the configured queue connection. This guarantees visit counts are not lost during cache evictions or restarts.

---

## Configuration Reference (.env)

You can also pre-configure all parameters via your `.env` file:

| Environment Variable | Config Path | Default | Description |
|---|---|---|---|
| `SHORT_URL_PREFIX` | `route_prefix` | `'s'` | URL prefix for short URL redirects. |
| `SHORT_URL_GEO_IP` | `geo_ip.enabled` | `true` | Globally enable/disable Geo-IP tracking. |
| `SHORT_URL_GEO_IP_DRIVER` | `geo_ip.driver` | `'headers'` | Geo-IP resolver driver (`headers`, `maxmind`, `ip-api`). |
| `SHORT_URL_MAXMIND_DB` | `geo_ip.maxmind.database_path` | `storage_path('geoip/GeoLite2-Country.mmdb')` | Path to local MaxMind db. |
| `SHORT_URL_STATS_CACHE_TTL` | `geo_ip.stats_cache_ttl` | `300` | Caching TTL in seconds for dashboard charts. |
| `SHORT_URL_QUEUE` | `queue_connection` | `'sync'` | Queue connection for recording visits. |
| `SHORT_URL_CACHE_TTL` | `cache_ttl` | `3600` | Redirection model caching TTL (set to `0` to disable). |
| `GA4_API_SECRET` | `ga4.api_secret` | `null` | Google Analytics 4 Measurement Protocol API Secret. |
| `FIREBASE_APP_ID` | `ga4.firebase_app_id` | `null` | Google Analytics 4 Firebase App ID (or uses Measurement ID). |
| `SHORT_URL_COUNTER_BUFFERING` | `counter_buffering.enabled` | `false` | Buffer click counts in cache (flushed via console command). |
| `SHORT_URL_TRUST_CDN_HEADERS` | `trust_cdn_headers` | `false` | Trust proxy/CDN headers to extract real client IP and country. |
| `SHORT_URL_PRUNING_ENABLED` | `pruning.enabled` | `true` | Enable daily aggregation and log pruning. |
| `SHORT_URL_PRUNING_DAYS` | `pruning.retention_days` | `90` | Number of days to retain raw visit logs. |
| `SHORT_URL_RATE_LIMITING` | `rate_limiting.enabled` | `false` | Enable per-IP redirect rate limiting. |
| `SHORT_URL_RATE_LIMIT_MAX` | `rate_limiting.max_attempts` | `60` | Max redirect requests within the decay window. |
| `SHORT_URL_RATE_LIMIT_DECAY` | `rate_limiting.decay_seconds` | `60` | Rate limiter rolling window in seconds. |

---

## Artisan Commands

| Command | Description |
|---------|-------------|
| `short-url:sync-counters` | Flushes buffered visit counts from cache to the database. Schedule every minute when counter buffering is enabled. |
| `short-url:aggregate-and-prune` | Aggregates previous days' raw visits into `short_url_daily_stats` and prunes raw records older than the configured retention period. Schedule daily (e.g. at `02:00`). |

### Automatic Scheduler Registration (v1.3.0)

As of version `1.3.0`, **you no longer need to manually copy scheduled commands into your host application code!** The plugin automatically registers the required tasks inside its ServiceProvider booted phase, dynamically respecting your Settings GUI toggles:

- **`short-url:aggregate-and-prune`** is scheduled **daily at 02:00** (runs automatically only if **Enable Daily Aggregation** is ON).
- **`short-url:sync-counters`** is scheduled **every minute** (runs automatically only if **Buffer Visit Counts in Cache** is ON).

If you prefer to configure the schedule manually, you can still define them in `routes/console.php` (ensure the corresponding toggles are turned OFF in your Settings panel to avoid redundant executions):

```php
// routes/console.php

use Illuminate\Support\Facades\Schedule;

// Flush buffered click counts every minute (only if counter buffering is enabled)
Schedule::command('short-url:sync-counters')->everyMinute();

// Aggregate stats and prune old raw visits nightly
Schedule::command('short-url:aggregate-and-prune')->dailyAt('02:00');
```

---

## Programmatic Usage (Fluent Builder)

You can programmatically generate, customize, and trace short URLs anywhere in your code using the static `destination` builder on the `ShortUrl` model:

```php
use Bjanczak\FilamentShortUrl\Models\ShortUrl;

$shortUrl = ShortUrl::destination('https://example.com/products/promo')
    ->urlKey('promo2026')   // Optional, auto-generated base62 key if omitted
    ->notes('Spring social promo')
    ->singleUse()           // Deactivates the link automatically after the first visit
    ->forwardQueryParams()  // Appends incoming URL query strings to destination
    ->expiresAt(now()->addDays(7)) // Automatic link expiration
    ->trackVisits(true)     // Enable or disable click logs
    ->withTracing([         // Dynamically filter and append UTM parameters
        'utm_source'   => 'linkedin',
        'utm_medium'   => 'social',
        'utm_campaign' => 'spring_sale',
        'utm_content'  => null, // skipped automatically
    ])
    ->create();

// Get the full URL string
echo $shortUrl->getShortUrl(); // https://yourdomain.com/s/promo2026
```

### Fluent Builder API Reference

| Method | Argument Type | Description |
|--------|---------------|-------------|
| `urlKey()` | `string` | Sets a custom redirection key (slug). |
| `notes()` | `string` | Appends internal notes for administrators. |
| `singleUse()` | `bool` (default `true`) | Makes the short URL expire immediately after the first successful click. |
| `forwardQueryParams()` | `bool` (default `true`) | Forwards client-side incoming parameters (e.g. `?gclid=xxx`) to the final target. |
| `expiresAt()` | `DateTimeInterface\|Carbon\|null` | Automatically sets a timestamp after which the URL returns a `410 Gone` error. |
| `trackVisits()` | `bool` (default `true`) | Toggles statistical logging for this link. |
| `withTracing()` | `array` | Appends non-empty tracking parameters (like UTM codes) directly to the target URL. |
| `create()` | `void` | Persists the model to the database and returns the `ShortUrl` instance. |

---

## Model Helpers & Service

### ShortUrl Model Methods
*   `isActive(): bool` — Checks if the short URL is enabled and within its activation/expiration timestamps.
*   `isExpired(): bool` — Checks if the URL has passed its expiration date.
*   `getShortUrl(): string` — Resolves the complete URL string.
*   `incrementVisits(bool $isUnique = false): void` — Atomically increments visit counts (with Redis buffering or queue fallback).
*   `getCachedStats(): array` — Returns cached key metrics for dashboard charts. Automatically merges daily aggregated stats with today's raw visits.
*   `resolveDestinationUrl(Request $request): string` — Evaluates active targeting rules (device, geo, rotation) and returns the correct destination URL for the current visitor.

### Injecting the Service
For standard creations, you can inject `ShortUrlService`:
```php
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;

$service = app(ShortUrlService::class);

$shortUrl = $service->create([
    'destination_url' => 'https://example.com',
    'track_visits'    => true,
]);
```

---

## Events

The package fires the `ShortUrlVisited` event inside the queue job. You can listen to it to trigger custom logic, Slack notifications, or syncing external CRMs:

```php
use Bjanczak\FilamentShortUrl\Events\ShortUrlVisited;
use Illuminate\Support\Facades\Event;

Event::listen(ShortUrlVisited::class, function (ShortUrlVisited $event) {
    $event->shortUrl; // The ShortUrl model instance
    $event->visit;    // The ShortUrlVisit model (contains resolved IP, country, device, browser, OS, referrer)
});
```

---

## Visitor Filtering (Bot Detection)
Visits from scrapers, search bots, and web crawlers are automatically ignored to keep stats clean. The system matches user agents against a custom list (including Googlebot, Bingbot, Applebot, Facebook, Twitter, and generic curl/http request clients).

---

## Database Compatibility

All migrations are compatible with **SQLite**, **MySQL**, and **PostgreSQL**:

- No MySQL-specific `ENUM` types — `device_type` uses `VARCHAR(20)` validated at PHP level.
- No `->after()` column ordering hints — fully cross-database.
- JSON columns work natively on MySQL 5.7.8+ and are stored as TEXT on SQLite.

---

## Changelog

### v1.3.0 (Latest)
- **Automatic Scheduler Registration** — Zero-configuration task registration within the ServiceProvider booted phase (dynamically honors Settings toggles).
- **Interactive Settings Validators** — Adds real-time "Test connection" verify action for GA4 Measurement Protocol and "Verify file" action for MaxMind database paths.
- **Robust Table Row Copy Action** — High-reliability, conflict-free click-to-copy in table rows with built-in fallback helper for non-HTTPS (secure context) browser environments.
- **Filament v5 Notification API** — Seamless integration of the new `FilamentNotification` class API inside client-side JS.
- **Asset Compilation Guide** — Explains Tailwind CLI CSS compilation and Filament asset publishing workflows for package extensions.

### v1.2.0
- **Password-protected links** — Session-based unlock flow with a styled prompt page.
- **Redirect warning pages** — Interstitial security screen before external redirects.
- **Smart targeting** — Device-based, Country/Geo-based, and A/B weighted rotation rules per link.
- **Rate limiting** — Configurable per-IP redirect throttling with 429 responses.
- **Daily stats aggregation** — Nightly `short-url:aggregate-and-prune` command for scalable log management.
- **Counter buffering fallback** — `IncrementVisitJob` as queue-based fallback when Redis is unavailable.
- **Database compatibility** — Replaced `ENUM` with `VARCHAR`, removed MySQL-only `->after()` calls.
- **Extended Settings GUI** — New "Performance & Security" tab for aggregation and rate limiting configuration.
- **Polish translations** — Full `pl` locale support for all new features.

---

## License

MIT
