<p align="center">
    <img src="https://cdn3d.iconscout.com/3d/premium/thumb/url-3d-icon-png-download-9292351.png" width="180" alt="Filament Short URL Logo">
</p>

<h1 align="center">Filament Short URL</h1>

<p align="center">
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/v/janczakb/filament-short-url.svg?style=flat-square" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/l/janczakb/filament-short-url.svg?style=flat-square" alt="License"></a>
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/dt/janczakb/filament-short-url.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/janczakb/filament-short-url/stargazers"><img src="https://img.shields.io/github/stars/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Stars"></a>
    <a href="https://github.com/janczakb/filament-short-url/issues"><img src="https://img.shields.io/github/issues/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Issues"></a>
    <a href="https://github.com/janczakb/filament-short-url/actions"><img src="https://img.shields.io/badge/tests-passing-success.svg?style=flat-square" alt="Tests"></a>
</p>

A professional, high-performance **Short URL Manager** plugin for [Filament v5](https://filamentphp.com). Built from scratch with cutting-edge practices, proxy resistance, offline Geo-IP engines, and zero external shortening API dependencies.

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
- 🛠️ **Dedicated Settings GUI** — Manage global configuration (routing, Geo-IP, GA4, cache) directly inside the Filament panel without modifying files or `.env` files.
- 💻 **Fluent Developer Builder** — Native model query builder pattern and robust programmatic generation APIs.

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

### 4. Publish All Assets
```bash
php artisan vendor:publish --provider="Bjanczak\FilamentShortUrl\FilamentShortUrlServiceProvider"
```

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

---

## Configuration Reference (.env)

You can also pre-configure all parameters via your `.env` file:

| Environment Variable | Config Path | Default | Description |
|---|---|---|---|
| `SHORT_URL_PREFIX` | `route_prefix` | `'s'` | URL prefix for short URL redirects. |
| `SHORT_URL_GEO_IP` | `geo_ip.enabled` | `true` | Globally enable/disable Geo-IP tracking. |
| `SHORT_URL_GEO_IP_DRIVER` | `geo_ip.driver` | `'headers'` | Geo-IP resolver driver (`headers`, `maxmind`, `ip-api`). |
| `SHORT_URL_MAXMIND_DB` | `geo_ip.maxmind.database_path` | `database_path('geoip/GeoLite2-Country.mmdb')` | Path to local MaxMind db. |
| `SHORT_URL_STATS_CACHE_TTL` | `geo_ip.stats_cache_ttl` | `300` | Caching TTL in seconds for dashboard charts. |
| `SHORT_URL_QUEUE` | `queue_connection` | `'sync'` | Queue connection for recording visits. |
| `SHORT_URL_CACHE_TTL` | `cache_ttl` | `3600` | Redirection model caching TTL (set to `0` to disable). |
| `GA4_API_SECRET` | `ga4.api_secret` | `null` | Google Analytics 4 Measurement Protocol API Secret. |
| `FIREBASE_APP_ID` | `ga4.firebase_app_id` | `null` | Google Analytics 4 Firebase App ID (or uses Measurement ID). |
| `SHORT_URL_COUNTER_BUFFERING` | `counter_buffering.enabled` | `false` | Buffer click counts in cache (flushed via console command). |

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
*   `incrementVisits(bool $isUnique = false): void` — Atomically increments visit counts.
*   `getCachedStats(): array` — Returns cached key metrics for dashboard charts.

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

## License

MIT
