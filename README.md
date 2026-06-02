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

## Screenshots

<p align="center">
  <table align="center" style="border-collapse: collapse; border: none;">
    <tr style="border: none;">
      <td width="50%" style="border: none; padding: 5px;"><img src="art/1.png" alt="Dashboard Stats" style="border-radius: 8px;"></td>
      <td width="50%" style="border: none; padding: 5px;"><img src="art/2.png" alt="Short URL Creation Form" style="border-radius: 8px;"></td>
    </tr>
    <tr style="border: none;">
      <td width="50%" style="border: none; padding: 5px;"><img src="art/3.png" alt="Targeting and Rules" style="border-radius: 8px;"></td>
      <td width="50%" style="border: none; padding: 5px;"><img src="art/4.png" alt="Interactive Settings Panel" style="border-radius: 8px;"></td>
    </tr>
  </table>
</p>

---

## Features

- 🔗 **Short URL Generation** — Create custom links or let the system auto-generate collision-free Base62 keys.
- 🌍 **Multiple Geo-IP Drivers** — High-speed offline detection using local MaxMind databases, edge-provided CDN headers (Cloudflare, CloudFront, generic), or fallback API integration.
- 📈 **Real-Time Statistics Dashboard** — Track visits in real-time with cached aggregate metrics (countries, devices, browsers, operating systems, referrers, traffic charts, and maps).
- 🎨 **SVG QR Code Designer** — Built-in interactive design canvas in Filament to customize dot styles, margins, gradient coloring, and background transparency with instant SVG download.
- ⚡ **Ultra-Fast Redirects** — Redirections resolve in milliseconds. Analytical tasks, event dispatching, and GA4 payloads are processed asynchronously via Laravel Queue jobs.
- 🎯 **Google Analytics 4 server-side tracking** — Native integration with the GA4 Measurement Protocol to bypass client-side AdBlockers completely.
- ⚙️ **Dual-way UTM Campaign Builder** — Built-in form builder synchronizes UTM parameters with your destination URLs in real-time (two-way binding).
- 🔒 **Link Validity Ranges & Expiry** — Set activation date ranges (From - To), custom visit limit counters (e.g. active for 3 clicks then expires), single-use restrictions, and custom fallback redirect URLs on expiration instead of static 410 Gone errors.
- ➡️ **Query Parameter Forwarding** — Dynamically forward client query parameters (e.g. ad tokens, discount codes) to the destination URL.
- 🛠️ **Dedicated Settings GUI** — Manage global configuration (routing, Geo-IP, GA4, cache, rate limiting, aggregation) directly inside the Filament panel without modifying files or `.env` files.
- 💻 **Fluent Developer Builder** — Native model query builder pattern and robust programmatic generation APIs.
- 🔑 **Password-Protected Links** — Require a password before redirecting visitors, with session-based unlock.
- ⚠️ **Redirect Warning Pages** — Show an interstitial security page before redirecting to external URLs (phishing/NSFW protection).
- 🎯 **Smart Link Targeting** — Route visitors to different destination URLs based on their device type, country, or via weighted A/B split rotation.
- 🛡️ **Rate Limiting / Bot Protection** — Configurable per-IP rate limits on redirects with automatic `429 Too Many Requests` responses.
- 📊 **Daily Stats Aggregation & Pruning** — Automatic daily summarization of raw visit logs into compact daily stats tables. Configurable retention window prevents unbounded database growth at scale.
- 🎯 **Social Retargeting Pixels** *(new in v1.5)* — Inject Meta Pixel, Google Tag, and LinkedIn Insight tracking scripts client-side via a premium glassmorphic interstitial page. Build remarketing audiences even when redirecting to external domains.
- 🔌 **Developer REST API** *(new in v1.5)* — Full REST API (`GET`, `POST`, `DELETE`) for external integrations. Secured with API Key authentication managed via the Settings panel.
- 📡 **Webhooks** *(new in v1.5)* — Real-time HTTP POST event notifications on every click, link creation, or expiration. Configure per-link or globally, dispatched asynchronously via the queue.

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

## Social Retargeting Pixels (new in v1.5.0)

The **Marketing & API** tab in the short URL form lets you attach client-side tracking pixels to any link. When a visitor clicks a link that has pixels configured, instead of an instant 302 redirect the plugin serves a lightweight HTML interstitial page (styled with glassmorphism) for ~250 ms. During that time the browser executes the pixel scripts — capturing cookies and building ad audiences — then the visitor is forwarded to the destination URL.

This unlocks remarketing to people who clicked your links **even when redirecting to external domains** (e.g. booking.com, amazon.com) where you cannot install your own tracking code.

### Supported Pixel Providers

| Field | Provider | Script loaded |
|---|---|---|
| **Meta Pixel ID** | Meta / Facebook Ads | `fbevents.js` via `fbq('init', ...)` |
| **Google Tag / GA4 ID** | Google Ads, GA4 | `gtag.js` via Google Tag Manager |
| **LinkedIn Partner ID** | LinkedIn Insight Tag | `insight.min.js` via LinkedIn |

> **Note:** These pixels fire **client-side** in the visitor's browser — completely separate from the server-side GA4 Measurement Protocol integration. Both systems work in parallel and do not interfere with each other.

### How to use

1. Open any short URL for editing.
2. Navigate to the **Marketing & API** tab.
3. Enter your pixel IDs in the **Retargeting Pixels** section.
4. Save. Done — every click will now trigger the configured tracking scripts.

```php
// Programmatically via model attributes
$shortUrl->update([
    'pixel_meta_id'     => '1234567890',
    'pixel_google_id'   => 'G-XXXXXXXXXX',
    'pixel_linkedin_id' => '1234567',
]);
```

> **Privacy/GDPR Note:** You are responsible for ensuring that firing these pixels complies with applicable privacy regulations and your cookie consent mechanism.

---

## Developer REST API (new in v1.5.0)

The plugin exposes a REST API that allows external systems (CRMs, Zapier, Make, custom integrations) to manage short URLs programmatically.

### Enabling the API

The API is **disabled by default**. Enable it in **Settings → API & Webhooks → REST API Access → Enable Developer REST API**.

### Authentication

All API endpoints are protected. Include your API key in every request using one of these headers:

```
X-Api-Key: sh_key_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```
or
```
Authorization: Bearer sh_key_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Managing API Keys:** Go to **Settings → API & Webhooks → Developer API Keys** and add named keys. Each key can be individually activated or deactivated without deleting it.

> If the API is disabled globally, all endpoints return `503 Service Unavailable` regardless of the key provided.

### Endpoints

#### `GET /api/short-url/links`
List all short URLs (paginated, 30 per page).

```bash
curl https://yourdomain.com/api/short-url/links \
  -H "X-Api-Key: sh_key_your_key_here"
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "destination_url": "https://example.com",
      "url_key": "abc123",
      "short_url": "https://yourdomain.com/s/abc123",
      "is_enabled": true,
      "redirect_status_code": 302,
      "total_visits": 47,
      "unique_visits": 31,
      "max_visits": null,
      "activated_at": null,
      "expires_at": null,
      "pixel_meta_id": null,
      "pixel_google_id": null,
      "pixel_linkedin_id": null,
      "webhook_url": null,
      "notes": null,
      "created_at": "2026-06-01T12:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 30,
    "total": 72
  }
}
```

#### `POST /api/short-url/links`
Create a new short URL.

```bash
curl -X POST https://yourdomain.com/api/short-url/links \
  -H "X-Api-Key: sh_key_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "destination_url": "https://example.com/product",
    "url_key": "promo26",
    "notes": "Summer campaign",
    "single_use": false,
    "max_visits": 1000,
    "pixel_meta_id": "1234567890",
    "webhook_url": "https://api.mycrm.com/clicks"
  }'
```

**Accepted fields:**

| Field | Type | Required | Description |
|---|---|---|---|
| `destination_url` | string (URL) | ✅ | Target URL |
| `url_key` | string | ❌ | Custom slug (auto-generated if omitted) |
| `notes` | string | ❌ | Internal notes |
| `is_enabled` | boolean | ❌ | Active status (default: `true`) |
| `redirect_status_code` | integer (301/302) | ❌ | HTTP redirect code |
| `single_use` | boolean | ❌ | Expire after first click |
| `forward_query_params` | boolean | ❌ | Forward query string to destination |
| `max_visits` | integer | ❌ | Click limit before expiry |
| `expiration_redirect_url` | string (URL) | ❌ | Fallback URL on expiry |
| `activated_at` | datetime | ❌ | Activation timestamp |
| `expires_at` | datetime | ❌ | Expiration timestamp |
| `pixel_meta_id` | string | ❌ | Meta Pixel ID |
| `pixel_google_id` | string | ❌ | Google Tag / GA4 ID |
| `pixel_linkedin_id` | string | ❌ | LinkedIn Partner ID |
| `webhook_url` | string (URL) | ❌ | Per-link webhook endpoint |

**Response:** `201 Created` with the created link object.

#### `DELETE /api/short-url/links/{id}`
Permanently delete a short URL by its ID.

```bash
curl -X DELETE https://yourdomain.com/api/short-url/links/42 \
  -H "X-Api-Key: sh_key_your_key_here"
```

**Response:** `200 OK`
```json
{ "message": "Short URL deleted successfully." }
```

### Error Responses

| HTTP Code | Reason |
|---|---|
| `401 Unauthorized` | Missing or invalid API key |
| `422 Unprocessable Entity` | Validation error (see `errors` field in response) |
| `503 Service Unavailable` | REST API is disabled in Settings |

---

## Webhooks (new in v1.5.0)

Webhooks allow external systems to receive real-time HTTP POST notifications when events occur on your short URLs. Payloads are dispatched **asynchronously** via the Laravel Queue — redirects are never blocked.

### Configuration

Webhooks can be configured at two levels:

**1. Per-link webhook** — Set a `Dedicated Webhook URL` in the **Marketing & API** tab of a specific short URL. Fires for every click on that link.

**2. Global webhook** — Set a **Global Webhook URL** in **Settings → API & Webhooks → Global Webhook Configuration**. Fires for all links that don't have their own webhook URL, for the event types you select.

### Monitored Events

| Event key | When fired |
|---|---|
| `visited` | A visitor clicks the short URL |
| `created` | A new short URL is created via the REST API |
| `expired` | A link reaches its expiration date |
| `limit_reached` | A link reaches its `max_visits` click limit |

Select which events to monitor in **Settings → API & Webhooks → Monitored Webhook Events**.

### Payload Format

All webhook requests are HTTP POST with `Content-Type: application/json` and the following payload structure:

```json
{
  "event": "visited",
  "timestamp": "2026-06-02T10:00:00+00:00",
  "short_url": {
    "id": 1,
    "destination_url": "https://example.com",
    "url_key": "abc123",
    "short_url": "https://yourdomain.com/s/abc123",
    "total_visits": 48,
    "unique_visits": 32
  },
  "visit": {
    "id": 101,
    "visited_at": "2026-06-02T10:00:00+00:00",
    "device_type": "desktop",
    "browser": "Chrome",
    "browser_version": "124.0",
    "operating_system": "Windows",
    "operating_system_version": "10",
    "country": "Poland",
    "country_code": "PL",
    "city": "Warsaw",
    "referer_url": "https://linkedin.com",
    "referer_host": "linkedin.com",
    "utm_source": "linkedin",
    "utm_medium": "social",
    "utm_campaign": "spring_sale",
    "utm_term": null,
    "utm_content": null
  }
}
```

### Retry Policy

If the webhook endpoint returns a non-2xx response or is unreachable, the `SendWebhookJob` will automatically retry up to **3 times** with a **10-second backoff** between attempts. Failed jobs land in your queue's failed jobs table after exhausting retries.

### Webhook Priority (per-link vs global)

The resolution order is:
1. If the short URL has its own `webhook_url` → use it (always fires, regardless of global event selection).
2. If no per-link URL is set, and a **Global Webhook URL** is configured, and the event type is in the selected **Monitored Events** list → use the global URL.
3. Otherwise no webhook is fired.

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
| `expiresAt()` | `DateTimeInterface\|Carbon\|null` | Automatically sets an expiration timestamp after which the URL is inactive. |
| `activatedAt()` | `DateTimeInterface\|Carbon\|null` | Sets the timestamp from which the URL is active. |
| `deactivatedAt()` | `DateTimeInterface\|Carbon\|null` | Sets the timestamp after which the URL is inactive. |
| `maxVisits()` | `int\|null` | Sets a custom limit of total visits allowed. |
| `expirationRedirectUrl()` | `string\|null` | Sets a custom URL to redirect to when expired/inactive. |
| `trackVisits()` | `bool` (default `true`) | Toggles statistical logging for this link. |
| `withTracing()` | `array` | Appends non-empty tracking parameters (like UTM codes) directly to the target URL. |
| `create()` | `ShortUrl` | Persists the model to the database and returns the `ShortUrl` instance. |

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

### v1.5.1
- **REST API On/Off Toggle** — Enable or disable the entire developer REST API from Settings → API & Webhooks without touching code. Returns `503 Service Unavailable` when disabled. Toggle takes effect immediately without route cache clearing.

### v1.5.0
- **Social Retargeting Pixels** — Attach Meta Pixel, Google Tag (GA4/Ads), and LinkedIn Insight Tag to any short URL. A premium glassmorphic interstitial page executes pixel scripts in the visitor's browser before forwarding them to the destination. Enables building remarketing audiences even on external domains.
- **Developer REST API** — Full `GET /api/short-url/links`, `POST /api/short-url/links`, and `DELETE /api/short-url/links/{id}` endpoints with API Key authentication (managed via Settings UI). Supports creating links with all available attributes including pixels and webhooks.
- **Webhook System** — Real-time HTTP POST notifications on `visited`, `created`, `expired`, and `limit_reached` events. Configurable per-link or globally. Dispatched asynchronously via `SendWebhookJob` with 3-attempt retry policy and 10-second backoff.
- **Settings: API & Webhooks Tab** — New settings tab to manage global webhook URL, monitored event types, and developer API key management with name labels and per-key activation toggles.

### v1.4.0
- **Validity Date Ranges (From-To)** — Set activation dates (`activated_at` and `expires_at`) to control exactly when a short link is active.
- **Custom Visit Limit Counters** — Define a custom maximum visit limit (`max_visits`) after which a link automatically expires (e.g., active for 3 hits).
- **Custom Expiration Fallbacks** — Redirect expired/inactive visitors to a custom `expiration_redirect_url` rather than showing a static 410 Gone error page.
- **Reactive Validity Controls** — Master switch to toggle date limits, including bidirectional datetime picker constraints (Active From cannot exceed Expires At and vice versa) and custom UI field visibility.
- **Smart Model Observers** — Automatic cleanup of unused parameters (like clearing `max_visits` if `single_use` is enabled, and clearing expiration fallbacks if date limits are off) to guarantee database consistency.
- **Fluent Builder APIs** — Fluent method additions (`activatedAt()`, `deactivatedAt()`, `maxVisits()`, `expirationRedirectUrl()`) in the developer query builder.

### v1.3.0
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
