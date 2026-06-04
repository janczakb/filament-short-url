<p align="center">
    <img src="https://cdn3d.iconscout.com/3d/premium/thumb/url-3d-icon-png-download-9292351.png" width="180" alt="Filament Short URL Logo">
</p>

<h1 align="center">Filament Short URL</h1>

<p align="center">
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/v/janczakb/filament-short-url.svg?style=flat-square" alt="Latest Version"></a>
    <a href="https://github.com/janczakb/filament-short-url/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-proprietary-7c3aed.svg?style=flat-square" alt="License"></a>
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/dt/janczakb/filament-short-url.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/janczakb/filament-short-url/stargazers"><img src="https://img.shields.io/github/stars/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Stars"></a>
    <a href="https://github.com/janczakb/filament-short-url/issues"><img src="https://img.shields.io/github/issues/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Issues"></a>
    <a href="https://github.com/janczakb/filament-short-url/actions"><img src="https://img.shields.io/badge/tests-passing-success.svg?style=flat-square" alt="Tests"></a>
</p>

A professional, high-performance **Short URL Manager, Redirect Engine & QR Code Generator** plugin for [Filament v5](https://filamentphp.com). 

Acting as a self-hosted, enterprise-grade alternative to Bitly and Rebrandly, this package provides advanced link shortening, mobile app deep linking (Universal Links & Android App Links), client-side retargeting pixels, offline Geo-IP country routing, real-time analytics, and webhooks—with zero external API dependencies.

### Why choose Filament Short URL?
* **Save on SaaS Costs**: Replace expensive Bitly or Rebrandly subscriptions with a self-hosted solution that has zero click or creation limits.
* **Server-Side GA4 Tracking**: Bypasses browser-side ad blockers completely to ensure 100% accurate traffic data.
* **Retarget on External Sites**: Inject tracking pixels (Meta, Google Ads, LinkedIn, TikTok, Pinterest) on a beautiful glassmorphic redirect page before forwarding visitors.
* **Seamless Mobile App Redirects**: Launch native mobile applications (Instagram, YouTube, Spotify, WhatsApp) automatically using deep links.
* **GDPR-Friendly Geo-IP**: Resolve visitor countries offline using local MaxMind databases or trust your CDN headers (Cloudflare, CloudFront).

## Screenshots

<p align="center">
  <table align="center" style="border-collapse: collapse; border: none;">
    <tr style="border: none;">
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_1.png" alt="Dashboard Stats" style="border-radius: 8px;"></td>
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_2.png" alt="Short URL Creation Form" style="border-radius: 8px;"></td>
    </tr>
    <tr style="border: none;">
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_3.png" alt="Targeting and Rules" style="border-radius: 8px;"></td>
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_4.png" alt="Interactive Settings Panel" style="border-radius: 8px;"></td>
    </tr>
    <tr style="border: none;">
      <td colspan="2" style="border: none; padding: 5px;"><img src="art/v2_5.png" alt="Visit Logs Table" style="border-radius: 8px; width: 100%;"></td>
    </tr>
  </table>
</p>

---

## Features

- 🔗 **Base62 Short Link Generation** — Create clean, custom short links or let the system auto-generate collision-free Base62 keys.
- 🌍 **Multiple Geo-IP Drivers** — Route and analyze traffic with offline MaxMind detection, CDN edge headers (Cloudflare's `CF-IPCountry`, CloudFront), or fallback APIs.
- 🗺️ **Interactive Visitor World Map** — Showcase geographic click distribution on a beautiful SVG world map widget with real-time hover details.
- 📈 **Comprehensive Analytics Dashboard** — Monitor total/unique visits, referrers, operating systems, devices, browsers, and top browser languages in real-time.
- 🛡️ **VPN, Proxy & Bot Filtering** — Exclude scrapers, crawlers, Tor exit nodes, and automated bot clicks to keep your analytics clean and accurate.
- 🔍 **Google Safe Browsing** — Automatically scan target URLs on creation/edit to block phishing, malware, and social engineering links.
- 🎨 **SVG QR Code Designer** — Customize dot styles, gradients, margins, and upload custom brand logos with auto-clear backing dots and high-quality SVG/PNG exports.
- 📊 **Dedicated QR Code Tracking** — Differentiate physical QR scans from direct web clicks with automatically appended query parameters (`?source=qr`).
- 🌐 **Browser Language Targeting** — Route visitors dynamically based on their browser language preferences (e.g., redirect Polish speakers to a Polish landing page).
- ⚡ **Ultra-Fast Redirections** — Redirections resolve in milliseconds. Logging, GA4 payloads, and webhooks are processed asynchronously in the background.
- 🎯 **Server-Side GA4 Integration** — Send server-side `short_url_visit` hits using the Google Analytics 4 Measurement Protocol to bypass ad-blockers.
- ⚙️ **Dual-way UTM Builder** — Build campaign URLs with a real-time synchronized UTM builder directly in the link creation form.
- 🔒 **Link Expiration & Fallbacks** — Set activation date ranges, click-limit caps, single-use restrictions, and custom redirection fallbacks on expiration.
- ➡️ **Query Parameter Forwarding** — Automatically forward client query strings (e.g., ad tokens, UTM parameters, discount codes) to the destination.
- 🛠️ **Central Settings Panel** — Manage routes, Geo-IP, GA4, caching, rate limiting, and retention directly inside your Filament panel.
- 🔑 **Password-Protected Links** — Secure sensitive links with a customizable, session-based password entry interstitial.
- ⚠️ **Redirect Warning Interstitials** — Show a security warning page to verify external links (phishing and NSFW protection).
- 🎯 **Advanced Smart Targeting** — Redirect visitors dynamically based on device type (iOS, Android, Desktop), country (Geo-IP), or browser language.
- ⚖️ **A/B Split Testing Rotation** — Distribute traffic randomly across multiple landing pages using custom weighted rotation rules.
- 🛡️ **Throttling & Rate Limiting** — Protect your redirection routes from flood attacks with configurable per-IP rate limits.
- 📊 **Log Aggregation & Pruning** — Compact millions of raw visit logs into daily summaries automatically to prevent database bloat.
- 🎯 **Central Retargeting Pixel Registry (new in v3.0.0)** — Register Meta Pixel, Google Tag, LinkedIn Insight, TikTok Pixel, and Pinterest Tag centrally and associate them with links via checkboxes.
- 🔌 **Developer REST API** — Full programmatical control with secure API Key authentication to create, list, and delete short links externally.
- 📡 **Real-Time Webhooks** — Asynchronous HTTP POST notifications on `visited`, `created`, `expired`, and `limit_reached` events with a built-in retry policy.
- 📱 **Mobile App Deep Linking (new in v3.0.0)** — Detect mobile visitors and open links directly in 24+ native apps (Instagram, YouTube, Spotify, TikTok, etc.) using custom URI schemes.
- 🔗 **Universal Links & App Links (new in v3.0.0)** — Host iOS `apple-app-site-association` and Android `assetlinks.json` domain configuration files directly from your root domain.
- 🎨 **Branded Expiry Pages (new in v3.0.0)** — Display a premium, dark-mode compatible custom expiry page when a link is deactivated or limit-reached, falling back to a clean site-name greeting instead of a generic browser error.

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
> 
> **Using a Custom Filament Theme (Tailwind CSS v4)?**
> If you are compiling your own Filament panel theme stylesheet, you can optionally tell Tailwind to scan the plugin's views by adding the `@source` directive to your theme's CSS file (e.g., `resources/css/filament/admin/theme.css`):
> 
> ```css
> @source './vendor/janczakb/filament-short-url/resources/views/**/*.blade.php';
> ```

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
| `authorizeSettingsUsing(Closure)` | `null` | Restricts access to the Settings page using a custom callback. See [Restricting Settings Access](#restricting-settings-access). |

---

## Restricting Settings Access

By default, any user who can view Short URLs can also access the Settings page. You can restrict this to specific roles or permissions using the `authorizeSettingsUsing()` method:

```php
FilamentShortUrlPlugin::make()
    ->authorizeSettingsUsing(fn () => auth()->user()->hasRole('admin'))
```

The callback can be any closure that returns a `bool`. When it returns `false`, the Settings page returns a 403 and the **Settings** button in the table header is automatically hidden.

### With `spatie/laravel-permission`

```php
FilamentShortUrlPlugin::make()
    ->authorizeSettingsUsing(fn () => auth()->user()->hasRole('admin'))
    // or permission-based:
    ->authorizeSettingsUsing(fn () => auth()->user()->can('manage short-url settings'))
```

### Via a Laravel Policy

Alternatively, define a `manageSettings` method in a Policy for the `ShortUrl` model — the plugin detects it automatically without any plugin configuration:

```php
// app/Policies/ShortUrlPolicy.php
public function manageSettings(User $user): bool
{
    return $user->is_admin;
}
```

Then register it in `AuthServiceProvider`:

```php
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use App\Policies\ShortUrlPolicy;

protected $policies = [
    ShortUrl::class => ShortUrlPolicy::class,
];
```

> **Priority order:** `authorizeSettingsUsing()` callback → `ShortUrlPolicy@manageSettings` → default `canViewAny()` fallback.

---

## Global Settings GUI

The package comes with a built-in admin settings dashboard. It is accessible directly from your sidebar menu under the same navigation group as your links.

Settings are stored dynamically in the database (`short_url_settings` table), cached indefinitely, and immediately override config defaults. Legacy settings from `filament-short-url-settings.json` are automatically imported on first load.

The settings panel allows you to configure:

### 1. General Routing & Queueing
*   **Route Prefix**: The slug prepended to short URLs (e.g. `s` for `/s/{key}`). Can be left empty to serve links directly from the root domain (e.g. `domain.com/{key}`).
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
*   **Cron Synchronization**: When enabled, the synchronization command flushes counts to the database. The package automatically registers this in the Laravel Scheduler to run every minute when counter buffering is active, so you only need to ensure the standard Laravel schedule runner (`* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`) is running on your server.
    *   Command: `php artisan short-url:sync-counters`

### 5. Performance & Security Tab (new in v1.2.0)

#### High-Traffic Log Management (Aggregation & Pruning)
At scale, the `short_url_visits` table can grow to tens of gigabytes. The aggregation system solves this:
*   **Enable Daily Aggregation**: When enabled, the stats are summarized. The package automatically registers the aggregation command in the Laravel Scheduler to run daily at 02:00, so you only need to ensure the standard Laravel schedule runner is running on your server.
    *   Command: `php artisan short-url:aggregate-and-prune`
*   **Prune Raw Logs After (days)**: Raw visit records older than this threshold are permanently deleted after aggregation. Set to `0` to disable pruning. Default: `90` days.

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

## Custom Branded Expiry Pages (new in v3.0.0)

When a short URL is expired, deactivated, or has reached its maximum visit limit, it needs to handle the redirect gracefully:
* **Custom Fallback URL**: If configured, visitors are immediately redirected to the `expiration_redirect_url` target.
* **Branded Expiry Page (Default)**: If no fallback URL is specified, the system displays a premium branded, fully localized, dark-mode compatible HTML page (`expired.blade.php`) instead of a generic browser `410 Gone` error.
  - Automatically displays your customized **Site Name** (and host application logo if the `setting('logo_path')` helper is defined).
  - Displays a clean visual alert state with details about the expired link.
  - Features a friendly back button pointing to your website's homepage.
  - Can be easily customized by publishing package views: `php artisan vendor:publish --tag=filament-short-url-views`.

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

#### 3. Browser Language-Based Redirects (new in v2.0.5)
Route visitors to language-specific URLs based on their browser's language preferences (detected from the `Accept-Language` header). Fully supports matching exact regional locales (e.g., `en-US`, `en-GB`) with automatic fallback to base language codes (e.g., `en`, `pl`).

```php
$shortUrl->update([
    'targeting_rules' => [
        'type' => 'language',
        'language' => [
            ['language_code' => 'pl', 'url' => 'https://pl.example.com'],
            ['language_code' => 'en-US', 'url' => 'https://us.example.com'],
            ['language_code' => 'de', 'url' => 'https://de.example.com'],
        ],
    ],
]);
```

#### 4. A/B Split Rotation
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

## Native App Linking & Deep Linking (new in v3.0.0)

This package supports two distinct levels of mobile app integration: **Per-Link App Linking** (client-side redirects using custom schemes) and **Global Deep Linking Files** (domain association files for OS-level native integration).

### 1. Per-Link App Linking (Mobile Auto-Open)

When creating or editing a short URL, the **App Linking** tab allows you to configure automatic redirects into native mobile applications.

* **How it works**: If a destination URL matches one of the 24+ pre-configured native applications (such as YouTube, TikTok, Instagram, Facebook, Spotify, WhatsApp, Messenger, etc.), the plugin can bypass standard web views for mobile visitors. 
* **The Interstitial Experience**: If **Auto open app on mobile** is enabled, mobile visitors are shown a premium glassmorphic redirect interstitial page that triggers the corresponding custom URL scheme (e.g. `whatsapp://`, `instagram://`, `youtube://`) to launch the native app directly, with fallback options to open in a web browser.
* **Interactive Panel Preview**: Inside the Filament resource edit form, a live preview widget demonstrates if the URL was matched, showing:
  - The matched app with its official favicon.
  - The calculated deep link scheme.
  - An interactive grid showing all supported native applications.

Supported apps include: YouTube, TikTok, Instagram, X (Twitter), Spotify, Facebook, Reddit, Snapchat, WhatsApp, LinkedIn, Pinterest, Twitch, Netflix, Google Docs/Sheets/Slides/Maps, Messenger, Apple Music, Airbnb, TripAdvisor, Amazon, StockX, Booking, AliExpress.

---

### 2. Global Deep Linking Files (Universal Links & App Links)

To support seamless OS-level integrations without browser intermediaries—such as iOS Universal Links and Android App Links—you can serve domain association files directly from your application's root domain.

> [!IMPORTANT]
> **Disabled by default**: This feature is turned **off** by default. You can enable it and customize the association JSON in your settings panel.

#### Enabling and Configuration
1. Open the **Settings** panel from your Filament sidebar.
2. Navigate to the **Deep Linking** tab.
3. Toggle **Enable Deep Linking Files** to ON.
4. Fill in your configurations:
   * **apple-app-site-association (iOS)**: The JSON configuration representing your iOS application IDs and supported paths (e.g., `/s/*`). This will be served at `/.well-known/apple-app-site-association` and `/apple-app-site-association` with the `application/json` content-type header.
   * **assetlinks.json (Android)**: The Digital Asset Links JSON array representing your Android application package names and SHA-256 certificate fingerprints. This will be served at `/.well-known/assetlinks.json`.

#### Example Configurations
##### iOS AASA Example:
```json
{
    "applinks": {
        "apps": [],
        "details": [
            {
                "appID": "YOUR_TEAM_ID.com.yourcompany.app",
                "paths": [
                    "/s/*"
                ]
            }
        ]
    }
}
```

##### Android AssetLinks Example:
```json
[
    {
        "relation": [
            "delegate_permission/common.handle_all_urls"
        ],
        "target": {
            "namespace": "android_app",
            "package_name": "com.yourcompany.app",
            "sha256_cert_fingerprints": [
                "14:6D:E9:57:3E:28:B6:58:91:..."
            ]
        }
    }
]
```

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
| `qr_visits_count` | Pre-aggregated daily QR code scans |
| `language_stats` | JSON — visit counts by browser language preferences |

The `getCachedStats()` model method **automatically merges** data from both tables: historical days come from `short_url_daily_stats`, while today's data comes directly from `short_url_visits` — completely transparent to the dashboard.

### Queue Worker vs. Synchronous Execution

The plugin is designed to be highly reliable and performant regardless of whether your hosting environment runs a background queue worker. 

#### 1. With a Queue Worker (Recommended)
If your application runs a queue worker (e.g. via supervisor running `php artisan queue:work`), you should configure the **Queue Connection** in the **Settings** panel (or via `SHORT_URL_QUEUE` env variable) to your primary queue driver (like `redis` or `database`).
- **Performance**: High-speed redirects are prioritized. Visitor clicks, Geo-IP lookups, GA4 hits, and Webhook dispatches are immediately deferred to background queue jobs (`TrackShortUrlVisitJob` and `SendWebhookJob`), keeping redirect response times under 20ms.
- **Worker Configuration**: Ensure your queue listener is running:
  ```bash
  php artisan queue:work --queue=default
  ```
  *(If you customized the queue name in Settings, replace `default` with your custom queue name).*

#### 2. Without a Queue Worker (Sync Driver Fallback)
If you do not run background queue workers, set the **Queue Connection** to `sync`.
- **Automatic Fallback**: The plugin dynamically executes all jobs synchronously on the request thread. The visitor is redirected as soon as the synchronous processes (log recording, webhook calls, etc.) complete.
- **Fault Tolerance (Safety Nets)**: Webhook failures (timeouts, 500 errors) or database tracking glitches can happen. The plugin wraps all synchronous execution points in strict error boundaries (`try/catch`). **A failed tracking job or a slow/offline webhook will NEVER crash the visitor's redirect or programmatic REST API responses.** They are gracefully logged in the background.

### Queue-Based Counter Fallback

When Redis is not available and counter buffering is enabled, the `IncrementVisitJob` is dispatched to the configured queue connection. This guarantees visit counts are not lost during cache evictions or restarts.

---

## Social Retargeting Pixels & Central Pixel Registry (new in v3.0.0)

Instead of manually copy-pasting tracking pixel IDs (Meta, Google Tag, LinkedIn, TikTok, Pinterest) every time you create a new link, the package features a centralized **Retargeting Pixel Registry** with a Many-to-Many relationship. You define your marketing pixels once in the new **Pixel Registry** resource, and then easily select them via checkbox/list options when creating or editing short links.

### The Interstitial Experience
- **Premium Design**: Built using the exact same modern glassmorphic look as the password protection and warning interstitial pages. Supports dark mode automatically.
- **Brand Customization**: Automatically renders the application logo (if defined via the `setting('logo_path')` helper) and the **Site Name Override** (configured globally in Settings, falling back to `config('app.name')`) to ensure the transition page looks professional and trust-instilling.
- **Micro-Animations & Smooth Redirect**: Displays a sleek animated loading spinner and a progress bar that smoothly fills from 0% to 100% in ~220-250ms. This short delay ensures browser execution time for the tracking scripts before performing a seamless `window.location.replace()` to the destination URL.

This unlocks remarketing to people who clicked your links **even when redirecting to external domains** (e.g. booking.com, amazon.com) where you cannot install your own tracking code.

### Supported Pixel Providers

| Type | Provider | Script loaded |
|---|---|---|
| `meta` | Meta / Facebook Ads | `fbevents.js` via `fbq('init', ...)` |
| `google` | Google Ads / GA4 | `gtag.js` via Google Tag Manager |
| `linkedin` | LinkedIn Insight Tag | `insight.min.js` via LinkedIn |
| `tiktok` | TikTok Pixel | TikTok Analytics pixel script |
| `pinterest` | Pinterest Tag | Pinterest Tag pixel script |

> **Note:** These pixels fire **client-side** in the visitor's browser — completely separate from the server-side GA4 Measurement Protocol integration. Both systems work in parallel and do not interfere with each other.

### How to use

1. Open **Pixel Registry** in your panel sidebar and register your pixels.
2. Open any short URL for editing.
3. Navigate to the **Marketing & API** tab.
4. Select the configured pixels from the list under **Retargeting Pixels**.
5. Save. Done — every click will now trigger the configured tracking scripts.

#### Programmatic Association

To associate centrally registered pixels programmatically, use standard Eloquent relationship syncing:

```php
$shortUrl = ShortUrl::find($id);

// Sync with specific pixel registry IDs
$shortUrl->pixels()->sync([$pixelId1, $pixelId2]);
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

For security, new API keys are hashed using SHA-256 and stored securely in the database. The plain key is displayed only once during generation via a persistent warning notification in the Filament UI. All keys are authenticated using constant-time string comparisons (`hash_equals()`) to prevent timing attacks.

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
      "webhook_url": null,
      "targeting_rules": null,
      "password": null,
      "show_warning_page": false,
      "track_visits": true,
      "track_browser_language": true,
      "pixels": [],
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
    "pixels": [1, 2],
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
| `pixels` | array of integers | ❌ | List of pixel registry IDs to associate with the link |
| `webhook_url` | string (URL) | ❌ | Per-link webhook endpoint |
| `targeting_rules` | array | ❌ | JSON targeting rules (device, geo, rotation, language) |
| `password` | string | ❌ | Access protection password |
| `show_warning_page` | boolean | ❌ | Show safety warning page before redirect |
| `track_visits` | boolean | ❌ | Track visitor clicks and logs |
| `track_browser_language` | boolean | ❌ | Track visitor browser language locale |

**Response:** `201 Created` with a wrapper message and the created link data:
```json
{
  "message": "Short URL created successfully.",
  "data": {
    "id": 2,
    "destination_url": "https://example.com/product",
    "url_key": "promo26",
    "short_url": "https://yourdomain.com/s/promo26",
    "is_enabled": true,
    "redirect_status_code": 302,
    "total_visits": 0,
    "unique_visits": 0,
    "max_visits": 1000,
    "activated_at": null,
    "expires_at": null,
    "webhook_url": "https://api.mycrm.com/clicks",
    "targeting_rules": null,
    "password": null,
    "show_warning_page": false,
    "track_visits": true,
    "track_browser_language": true,
    "pixels": [
      {
        "id": 1,
        "name": "Meta Pixel (1234567890)",
        "type": "meta",
        "pixel_id": "1234567890",
        "is_active": true
      },
      {
        "id": 2,
        "name": "Google Tag (G-XXXXXXXXXX)",
        "type": "google",
        "pixel_id": "G-XXXXXXXXXX",
        "is_active": true
      }
    ],
    "notes": "Summer campaign",
    "created_at": "2026-06-04T12:00:00+00:00"
  }
}
```

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

**1. Per-link Webhook (Dedicated Webhook URL)**:
*   **Where to configure**: Set the `Dedicated Webhook URL` in the **Marketing & API** tab of a specific short URL (or pass it via the `webhook_url` parameter in the REST API).
*   **How it works**: When a visitor clicks the short URL, or when the link is programmatically created via the REST API, a webhook notification is sent directly to this URL.
*   **Bypassing Global Filters**: Dedicated webhooks **always fire** for these events and are completely independent of the global *Monitored Webhook Events* settings. This is useful for third-party landing pages or integrations that need to track a specific link's traffic without routing all package events.

**2. Global Webhook**:
*   **Where to configure**: Set a **Global Webhook URL** in **Settings → API & Webhooks → Global Webhook Configuration**.
*   **How it works**: Fires for all links that *do not* have their own Dedicated Webhook URL configured. It will only fire for the specific event types selected in the **Monitored Webhook Events** setting.

### Monitored Events

| Event key | When fired |
|---|---|
| `visited` | A visitor clicks the short URL |
| `created` | A new short URL is created via the REST API |
| `expired` | A link reaches its expiration date |
| `limit_reached` | A link reaches its `max_visits` click limit |

Select which events to monitor in **Settings → API & Webhooks → Monitored Webhook Events**.

### Webhook Signature Verification

Outgoing webhooks can be cryptographically signed using an HMAC-SHA256 signature to verify that the request originated from your system:
1. Configure a **Webhook Signing Secret** in your Settings panel under **API & Webhooks**.
2. When configured, outgoing HTTP POST payloads will include the signature in the `X-ShortUrl-Signature` header, which is calculated as `hash_hmac('sha256', $payloadJson, $secret)`.
3. The receiver can verify the signature by re-calculating the HMAC of the raw request payload using the shared secret and comparing it using `hash_equals()`.

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

> [!IMPORTANT]
> **Cron Setup Required**:
> For the scheduler to fire these tasks automatically, your server must run the standard Laravel Scheduler cron job. Add this single cron entry to your server:
> ```bash
> * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
> ```
> *Note: These commands execute synchronously within the scheduling process. You do **not** need a queue worker (`php artisan queue:work`) to run them.*

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

### v3.1.0
- **Database-Backed & Cached Settings** — Relocated user configuration from local JSON files to the database (`short_url_settings` table) with automatic caching and zero-downtime migration of legacy settings.
- **High-Performance Aggregations** — Completely refactored the statistics aggregator command to run optimized GROUP BY queries directly in the database, reducing memory usage (OOM protection) to near zero.
- **Secure Hashed API Keys** — API keys are now stored securely as SHA-256 hashes, verified using constant-time comparisons (`hash_equals()`), and displayed only once upon generation.
- **HMAC Signed Webhooks** — Webhook payloads are now optionally signed with a configured secret key and verified via the `X-ShortUrl-Signature` header.
- **Privacy-Safe GA4 Client IDs** — Replaced random UUIDs with deterministic client IDs based on hashed visitor IP and User Agent, ensuring session integrity in Google Analytics without storing raw visitor data.
- **Browser Cache Prevention for Limited Links** — Force temporary `302` redirects automatically for single-use, max-visit, or expiring links to prevent browsers from caching redirects and bypassing tracking logic.
- **Proxy Detection Optimization** — Implemented an aggressive 800ms timeout for external proxy checkers and reduced cache time for transient rate-limit failures to 60 seconds.

### v3.0.0
- **Native App Linking (Mobile Auto-Open)** — Automatically match and redirect mobile visitors directly inside 24+ native mobile apps (such as WhatsApp, YouTube, TikTok, Instagram, Spotify, etc.) using custom schemes, complete with a glassmorphic redirect page and a live interactive matching preview widget.
- **Global Deep Linking (Universal Links & App Links)** — Easily serve iOS `apple-app-site-association` and Android `.well-known/assetlinks.json` configuration files directly from your root domain to support OS-level native integration (disabled by default, managed via Settings).
- **Central Retargeting Pixel Registry** — Introduced a premium Many-to-Many pixel management registry. Define pixels centrally (Meta Pixel, Google Tag, LinkedIn Insight, TikTok Pixel, Pinterest Tag) and easily associate them with short links via the Filament panel or the REST API.
- **Standalone Settings Page** — Relocated the Settings interface from a resource header sub-action to a standalone sidebar navigation page under the default plugin group.
- **Retargeting Pixel API** — The REST API now fully exposes the `pixels` relationship array parameter for registering and linking retargeting pixels programmatically.
- **Enhanced Browser Language Redirection** — Robust double-pass language targeting logic matching exact locales first (e.g. `en-US`, `zh-CN`) and falling back to base language codes (e.g. `en`, `zh`).
- **Full Localization & WhatsApp Favicon Fix** — Added friendly translation strings in English and Polish across the entire app-linking preview and redirect interfaces, and adjusted domains order to restore the WhatsApp favicon.
- **Custom Branded Expiry Pages** — Replaced raw 410 HTTP errors with a beautiful, fully localized, dark-mode compatible HTML expiry page displaying the Site Name, expired link details, and a homepage button.

### v2.0.0
- **Interactive QR Code Designer Branding Logo** — Upload custom brand logos inside the QR designer canvas in Filament. Configure logo sizing, margins, shapes (square/circle), and toggle dot backing removal to prevent dots overlapping with the logo.
- **Dedicated QR Code Scan Tracking** — Differentiates visitor clicks from physical QR code scans by dynamically appending source tags (`?source=qr`). Added a new database tracking column (`is_qr_scan` on visits, `qr_scans` on short URLs, and `qr_visits_count` on daily stats). Displays a dedicated scans counter badge in the Filament list table.
- **Browser Language Detection & Statistics** — Captures visitor browser preferred language headers (`browser_language` field) and aggregates them into the daily stats table. Displays a new "Top Languages" widget breakdown in the link statistics dashboard.
- **High-Traffic Performance Safeguards & Robust Rollbacks** — Atomic database transactions for buffered counter updates with fail-safe rollback that restores cache values in case of DB connection failures. Prevents N+1 queries by preloading request-wide counters in a single batch cache lookup.
- **Early Boot & Test Container Safety** — Safe settings caching that checks app container state before resolving `cache`, preventing early boot container exceptions during tests or artisan boots.
- **Atomic Duplicate Visit Caching** — Bypasses database exists checks for duplicate visitors by utilizing atomic `cache()->add` keys to prevent database bottlenecking.
- **Support for Empty Route Prefix (Root-Level URLs)** — Enhanced `getShortUrl()` to support empty route prefixes without generating double slashes (e.g. `domain.com/abc123` instead of `domain.com//abc123`). This allows clean root-level redirection domains. Added defensive slash trimming to standard prefixes.

### v1.7.0
- **Role-based Settings Access Control** — New `authorizeSettingsUsing(Closure)` method on the plugin to restrict who can access the Settings page. Supports any callable returning a `bool`. Also auto-detects a `manageSettings` method on a registered `ShortUrl` policy. The Settings button in the table header is hidden automatically when access is denied.

### v1.6.0
- **Google Safe Browsing Integration** — Automatic safety checks against Google's API during link creation or modification. Includes bypass settings, asynchronous checking option, and alert badges.
- **VPN / Proxy / Bot Filtering** — Detect and filter out VPN/proxy traffic and Tor nodes using external proxy detection APIs to keep traffic analytics clean.
- **Visitor World Map Widget** — Live interactive SVG world map showing clicks distribution per country, custom intensity highlighting, and hover details.
- **Enhanced Caching & Chart Formatting** — Improved analytics caching for high volumes, and clean `d.m` date formatting (removed year) on visitor stats charts.

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

Custom Source-Available License. Please see the [LICENSE](LICENSE) file for the full text.
