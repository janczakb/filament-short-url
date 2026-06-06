<p align="center">
    <img src="art/logo-fism.png" width="180" alt="Filament Short URL — Laravel link shortener plugin for Filament">
</p>

<h1 align="center">Filament Short URL</h1>

<p align="center"><strong>The most complete open-source link management plugin for Laravel & Filament.</strong><br>Short URLs · QR codes · Analytics · Targeting · Deep links · Webhooks · REST API</p>

<p align="center">
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/v/janczakb/filament-short-url.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/janczakb/filament-short-url/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-proprietary-7c3aed.svg?style=flat-square" alt="License"></a>
    <a href="https://packagist.org/packages/janczakb/filament-short-url"><img src="https://img.shields.io/packagist/dt/janczakb/filament-short-url.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/janczakb/filament-short-url/stargazers"><img src="https://img.shields.io/github/stars/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Stars"></a>
    <a href="https://github.com/janczakb/filament-short-url/issues"><img src="https://img.shields.io/github/issues/janczakb/filament-short-url.svg?style=flat-square" alt="GitHub Issues"></a>
    <a href="https://github.com/janczakb/filament-short-url/actions"><img src="https://img.shields.io/badge/tests-passing-success.svg?style=flat-square" alt="Tests"></a>
</p>

A self-hosted **URL shortener, redirect engine, and QR code manager** built as a [Filament v5](https://filamentphp.com) plugin. Drop it into any Laravel 11+ application to replace Bitly, Dub.co, or Rebrandly subscriptions — with full data ownership, no click limits, and no monthly fees.

On top of basic link shortening it ships: multi-channel analytics with live activity feed, cross-filtering, and cross-domain retargeting pixels; advanced routing rules (device, country, language, A/B); mobile deep linking into 24+ native apps; a REST API with scoped keys; HMAC-signed webhooks; and offline GDPR-safe Geo-IP — all managed from a polished Filament admin panel.

## Video Walkthrough

<p align="center">
  <video src="https://github.com/user-attachments/assets/136a4f5a-a50c-47cc-9afd-19c1fb2d64ab" width="100%" autoplay loop muted controls style="max-width: 100%; border-radius: 8px;"></video>
</p>

---

## Screenshots


<p align="center">
  <table align="center" style="border-collapse: collapse; border: none;">
    <tr style="border: none;">
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_1.png" alt="Analytics dashboard" style="border-radius: 8px;"></td>
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_2.png" alt="Short URL creation form" style="border-radius: 8px;"></td>
    </tr>
    <tr style="border: none;">
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_3.png" alt="Smart targeting rules" style="border-radius: 8px;"></td>
      <td width="50%" style="border: none; padding: 5px;"><img src="art/v2_4.png" alt="Settings panel" style="border-radius: 8px;"></td>
    </tr>
    <tr style="border: none;">
      <td colspan="2" style="border: none; padding: 5px;"><img src="art/v2_5.png" alt="Visit logs table" style="border-radius: 8px; width: 100%;"></td>
    </tr>
  </table>
</p>

---

## Features

- 🔗 **Short Link Generation** — Auto-generate collision-free Base62 keys or set your own custom slugs.
- 🌐 **Custom Domain Branding** — Register your own domains, verify DNS in real-time (A/CNAME), and serve links directly from the domain root — no `/s/` prefix needed.
- 🌍 **Multiple Geo-IP Drivers** — Resolve visitor countries offline via MaxMind, from CDN headers (Cloudflare `CF-IPCountry`, CloudFront), or via ip-api.com fallback.
- 🗺️ **Visitor World Map** — Visualize click distribution on an interactive SVG world map with per-country hover stats.
- 📈 **Full Analytics Dashboard** — Track total/unique visits, referrers, devices, browsers, OSes, and browser languages. Fully cross-filterable (e.g., "only mobile visits from Poland in May").
- ⚡ **Live Activity Feed** — Standalone real-time visit stream at `/stats/live`. Uses `MAX(id)` polling — returns a ~100-byte response when nothing changed, full render only on new visits.
- 🗂️ **Folders & Tags** — Assign each link to a folder and up to 5 tags. Click any folder or tag to navigate to a filtered link list.
- 🗄️ **Link Archiving** — Soft-archive links instead of deleting them. Archived links are hidden by default and fully restorable.
- 🛡️ **VPN, Proxy & Bot Filtering** — Filter out Tor nodes, VPNs, anonymous proxies, and known bot user agents from visit stats.
- 🔍 **Google Safe Browsing** — All target URLs are checked against Google's threat database on create and edit.
- 🎨 **SVG QR Code Designer** — Full dot style, gradient, margin, and logo customization. Export as SVG or high-resolution PNG.
- 📊 **QR Scan Tracking** — QR scans are recorded separately from regular web clicks via `?source=qr`. Shown as a distinct metric in analytics.
- ✈️ **Browser Language Targeting** — Route visitors to different destinations based on their browser's `Accept-Language` header.
- 🚀 **Fast Redirects** — Sub-20ms redirect response time. All logging, Geo-IP lookups, GA4 hits, and webhooks run asynchronously via queued jobs.
- 🎯 **Server-Side GA4** — Send `short_url_visit` events via GA4 Measurement Protocol, completely bypassing browser ad-blockers.
- ⚙️ **UTM Builder** — Build and preview campaign URLs with a real-time UTM form that syncs bidirectionally with the destination URL field.
- 🔒 **Link Expiration & Caps** — Set `activated_at`, `expires_at`, `max_visits`, single-use mode, and a custom fallback URL for when any of these conditions are met.
- ➡️ **Query Parameter Forwarding** — Automatically append incoming query strings (UTM parameters, ad tokens, discount codes) to the destination.
- 🛠️ **Settings Panel** — All configuration in a dedicated Filament settings page — no `.env` editing required for day-to-day changes.
- 🔑 **Password-Protected Links** — Visitors enter a password before being redirected. Session-persisted, so they only see the prompt once.
- ⚠️ **Redirect Warning Pages** — Show a confirmation screen before sending visitors off to external domains.
- 🎲 **A/B Split Testing** — Distribute traffic across 2–5 weighted variants. Works at the root level or nested inside targeting rules.
- 📉 **Log Aggregation & Pruning** — A nightly command aggregates raw visits into daily summaries and prunes records older than the configured retention window. Keeps the database lean even at millions of visits.
- 🏷️ **Retargeting Pixel Registry** — Define Meta Pixel, Google Tag, LinkedIn Insight, TikTok, and Pinterest pixels once, then attach them to any link via a simple checkbox list.
- 🔌 **REST API with Scoped Keys** — Full CRUD API, SHA-256 hashed key storage, `links:read-only` / `links:read-write` scopes, and per-key rate limits.
- 📡 **Webhooks** — HMAC-SHA256 signed HTTP POST callbacks on `visited`, `created`, `expired`, and `limit_reached` events. Dispatched asynchronously with 3-attempt exponential retry.
- 📱 **Mobile Deep Linking** — Detect mobile visitors and launch the link directly inside 24+ native apps (Instagram, YouTube, Spotify, TikTok, WhatsApp, etc.).
- 🍎 **Universal Links & Android App Links** — Serve `apple-app-site-association` and `assetlinks.json` directly from your domain to enable OS-level native app integration.
- 💀 **Branded Expiry Pages** — When a link expires or hits its limit, visitors see a styled page with your site name instead of a bare 410 error.
- 👤 **User Attribution** — Each link records its creator. Avatar and name/email hover card visible in the table.
- 🕒 **Relative Time Badges** — Compact timestamps (`2h`, `5d`, `3mo`) with exact date on hover.
- ⌨️ **Keyboard Shortcuts** — `E` edit, `Q` QR, `I` copy, `S` stats, `X` delete — available on any row hover.
- ⋯ **Unified Action Menu** — All row actions in one 3-dot dropdown with shortcut badges.

---

## vs. Bitly, Dub.co & Rebrandly

| Feature | Filament Short URL | Bitly | Dub.co | Rebrandly |
|---|:---:|:---:|:---:|:---:|
| Self-hosted | ✅ | ❌ | partial¹ | ❌ |
| Unlimited links & clicks | ✅ | ❌ | ❌ | ❌ |
| Custom domains | ✅ | 💰 | 💰 | 💰 |
| QR code designer | ✅ | 💰 basic | 💰 basic | 💰 |
| A/B split testing | ✅ | ❌ | ✅ | ❌ |
| Retargeting pixels | ✅ 5 providers | ❌ | ❌ | 💰 |
| Mobile deep linking | ✅ 24+ apps | 💰 Enterprise | 💰 partial | 💰 |
| Server-side GA4 (ad-block bypass) | ✅ | ❌ | ❌ | ❌ |
| Live activity feed | ✅ | ❌ | ✅ | ❌ |
| Cross-filtering analytics | ✅ | 💰 basic | partial | 💰 basic |
| REST API | ✅ scoped keys | 💰 | ✅ | 💰 |
| Webhooks | ✅ HMAC-signed | 💰 Enterprise | ✅ | 💰 |
| Offline GDPR Geo-IP (MaxMind) | ✅ | ❌ | ❌ | ❌ |
| VPN & bot filtering | ✅ | ❌ | ❌ | ❌ |
| Google Safe Browsing on save | ✅ | ❌ | ❌ | ❌ |
| Filament/Laravel admin panel | ✅ | ❌ | ❌ | ❌ |
| Data stays on your server | ✅ | ❌ | partial¹ | ❌ |
| Monthly cost | **$0** | $0–$199+ | $0–$190+ | $0–$49+ |

> 💰 = paid plans only &nbsp;·&nbsp; ¹ Dub.co is open-source (AGPLv3) but self-hosting requires external managed services (Tinybird, PlanetScale, Upstash)

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

All assets are optional. Publish only what you need to customize:

```bash
# Config file → config/filament-short-url.php
php artisan vendor:publish --tag=filament-short-url-config

# Translations → lang/vendor/filament-short-url/ (EN + PL included)
php artisan vendor:publish --tag=filament-short-url-translations

# Blade views (dashboard, QR designer, interstitials)
php artisan vendor:publish --tag=filament-short-url-views

# Pre-compiled CSS → public/css/janczakb/filament-short-url/
php artisan filament:assets
```

The CSS file is pre-compiled and bundled in the package — you don't need to run `npm` or Tailwind for it.

**If you compile your own Filament theme** (Tailwind CSS v4), add an `@source` directive so Tailwind scans the plugin views:

```css
/* resources/css/filament/admin/theme.css */
@source './vendor/janczakb/filament-short-url/resources/views/**/*.blade.php';
```

**Tip:** Add `filament:assets` to `post-autoload-dump` in your `composer.json` so CSS stays up to date on every `composer install`:

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

> [!NOTE]
> **Modular Tab Architecture (New in v3.5.0)**: The settings form is split into 8 independent, single-responsibility tab classes (`LinkTab`, `TargetingTab`, `TrackingTab`, `SecurityTab`, `GeoIpTab`, `VpnDetectionTab`, `QrDesignTab`, `PixelsTab`) to reduce load times, improve code maintainability, and allow clean tab query parameters navigation (e.g. `?tab=qr`).

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

## Security & Anti-Fraud v2.0 (new in v1.6.0)

Protect your application redirection routes and visitor data from malicious activities and automated scrapers.

### 1. VPN & Proxy Detection
Filter out anonymous proxy, VPN, or Tor connections to ensure clean analytics and prevent abuse.
* **Driver Selection**: Choose between the free **IP-API** service (default) or the premium **VPNAPI.io** service (requires setting an API key).
* **Configurable Action**:
  * **Flag Only**: Flags VPN/Proxy visits in database statistics for inspection but allows the redirection to continue.
  * **Block Traffic**: Actively blocks the request, serving a `403 Forbidden` response to the client.
* **Verify Key**: An interactive "Verify connection" action is available in settings to check your API credentials.

### 2. Google Safe Browsing URL Verification
Scan and verify all user-provided target URLs against Google's safe browsing lookup API on creation and edit.
* **Protection**: Blocks malware, phishing, and social engineering domains.
* **Filament UI**: Displays a clean status badge and blocks form saving if the target URL is flagged as unsafe.
* **Verify Key**: Includes a "Test API Connection" action on the settings dashboard to validate your Safe Browsing API credentials.

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

## Smart Link Targeting (updated in v3.5.0)

The **Targeting & Security** tab exposes a powerful rule engine that lets you route different visitors to different destinations — all from a single short URL.

You can configure multiple rules evaluated sequentially from top to bottom. Each rule contains:
- A **Target URL** (the redirect destination if rule matches).
- A **Match Strategy**: `AND` (all filters must match) or `OR` (any filter can match).
- A list of **Filters**:
  - **Device**: Filter by `desktop`, `mobile`, `tablet`.
  - **Platform**: Filter by `windows`, `mac`, `linux`, `ios`, `android`, `fire_os`.
  - **Country**: Filter by country codes (e.g. `PL`, `US`, `DE`) with flags display.
  - **Language**: Filter by preferred browser language codes (e.g. `pl`, `en`, `de`).

### Multi-Filter JSON Schema (v3.3.0+)

For programmatic or REST API updates, pass an array of rules to `targeting_rules`:

```php
$shortUrl->update([
    'targeting_rules' => [
        [
            'match' => 'and',
            'url' => 'https://ios-pl.example.com',
            'filters' => [
                [
                    'type' => 'platform',
                    'data' => ['platforms' => ['ios']]
                ],
                [
                    'type' => 'language',
                    'data' => ['languages' => ['pl']]
                ]
            ]
        ],
        [
            'match' => 'or',
            'url' => 'https://fallback-mobile.example.com',
            'filters' => [
                [
                    'type' => 'device',
                    'data' => ['devices' => ['mobile', 'tablet']]
                ]
            ]
        ]
    ]
]);
```

### Supported Filter Options & Formats

| Filter Type | Data Key | Allowed Values |
|-------------|----------|----------------|
| `device` | `devices` | `desktop`, `mobile`, `tablet` |
| `platform` | `platforms` | `windows`, `mac`, `linux`, `ios`, `android`, `fire_os` |
| `country` | `countries` | ISO-3166 2-letter country codes (e.g. `PL`, `US`, `DE`), case-insensitive |
| `language` | `languages` | ISO-639-1 language codes (e.g. `pl`, `en`, `de`), case-insensitive |

### Legacy Strategies (v1.2.0 - v2.x)

If your database contains legacy single-strategy rules (e.g. `'type' => 'device'` or `'type' => 'geo'`), the plugin handles them automatically:
* **Redirection Engine**: The redirect system detects the legacy structure and processes it on-the-fly using the legacy strategy.
* **Filament UI**: When loading a link with legacy rules, the Filament Form automatically upgrades and hydrates them to equivalent new multi-filter rules.

---

## A/B Split Testing & Weighted Traffic Rotation (new in v3.5.0)

A single short URL can distribute traffic across 2–5 landing pages using configurable weights. This is useful for comparing conversion rates between different pages without changing the link you've already shared.

### Two ways to set it up

**Root-level split test** — Set the **Destination Type** to `A/B Split Test` directly on the short URL. All visitors hitting that link will be distributed across your variants.

**Nested inside a targeting rule** — Combine split testing with audience targeting. For example: send mobile visitors from Poland into a 50/50 test, while everyone else goes to a single fallback URL.

### Weights

Each variant gets a percentage weight. Weights must sum to exactly **100%** and can be adjusted in 1% increments. The admin form shows an interactive split bar — drag the handles or hit **"Balance weights"** to divide traffic evenly. When you add or remove a variant, weights are rebalanced automatically.

UTM parameters and other query strings on the original click are forwarded to whichever variant is selected.

### Analytics

Every visit records which variant was resolved (`selected_variant` column in the visit log). The link's statistics dashboard shows a **Variant Clicks Distribution** bar chart so you can compare actual click shares against your configured weights.

### REST API

For root-level split tests, pass `rotation_variants` in the request body:

```json
{
  "destination_type": "split",
  "rotation_variants": [
    { "label": "Variant A", "url": "https://example.com/page-a", "weight": 70 },
    { "label": "Variant B", "url": "https://example.com/page-b", "weight": 30 }
  ]
}
```

To nest a split test inside a targeting rule, use `variants` within the rule object:

```json
{
  "destination_type": "single",
  "destination_url": "https://example.com/default",
  "targeting_rules": [
    {
      "match": "or",
      "destination_type": "split",
      "variants": [
        { "label": "Mobile A", "url": "https://example.com/mob-a", "weight": 50 },
        { "label": "Mobile B", "url": "https://example.com/mob-b", "weight": 50 }
      ],
      "filters": [
        { "type": "device", "data": { "devices": ["mobile"] } }
      ]
    }
  ]
}
```

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

## Custom Domain Branding (new in v4.0.0)

Branded links (e.g. `go.company.com/abc123` instead of `yourdomain.com/s/abc123`) significantly increase click-through rates and build trust. This package includes a full-featured custom domain manager out-of-the-box.

### Key Features
1. **Dynamic CNAME/A Record Verification**: Resolves DNS records in real-time natively using PHP's `dns_get_record()` (with fallback to `dig`) and matches against the host server IP — supports both CNAME and A record setups.
2. **DNS Setup Instructions**: Step-by-step instructions for non-technical users to connect their domain in their registrar (GoDaddy, Cloudflare, Namecheap, etc.) directly in the Filament UI.
3. **Verified-Only Assignment**: Only domains that pass DNS verification can be associated with short URLs.
4. **Root-Level Redirections**: When a visitor hits a custom domain link (e.g. `go.company.com/abc123`), the request is matched directly at the root path — omitting the default `/s/` prefix automatically.

### How to use
1. Go to the **Custom Domains** page in the Filament admin panel (sidebar → Links → Custom Domains).
2. Click **Create** and enter your domain name (e.g. `go.company.com`).
3. Follow the DNS Setup instructions shown in the modal to point your DNS records to the application server.
4. Click **Setup DNS / Verify** inside the table actions. Once DNS resolves correctly, the domain status changes to **Active**.
5. In the Short URL creation form, select your custom domain from the **Custom Domain** dropdown. The generated short URL will use your branded domain automatically.

### DNS Setup (for users)

You need to add **one** of the following records at your DNS provider:

| Type  | Name               | Value                          |
|-------|--------------------|--------------------------------|
| CNAME | `go` (subdomain)   | `yourdomain.com.` (main host) |
| A     | `go` (subdomain)   | `123.45.67.89` (server IP)    |

The plugin dynamically resolves the host application's IP at verification time — no hardcoded IP configuration is required.

### Web Server Configuration (required)

Custom domains must be configured at the web server level to forward requests to your Laravel application. Below are example configurations:

#### nginx

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name go.company.com;

    # Proxy to your main Laravel app
    location / {
        proxy_pass http://127.0.0.1:80;  # or your app server socket
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Or, if the custom domain points directly to the same server running Laravel:

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name go.company.com;

    root /var/www/your-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

#### Caddy

```caddyfile
go.company.com {
    root * /var/www/your-app/public
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
}
```

#### Apache

```apache
<VirtualHost *:80>
    ServerName go.company.com
    DocumentRoot /var/www/your-app/public

    <Directory /var/www/your-app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

> **Note:** The Laravel application automatically detects the incoming `Host` header and routes the request to the correct short URL via the fallback route. No additional application configuration is needed beyond registering the domain in the Filament UI.

### Server Requirements

- DNS verification runs natively using PHP's built-in `dns_get_record()` function.
- If native DNS functions are disabled or fail, the plugin falls back to using the `dig` CLI tool (`apt install dnsutils` on Debian/Ubuntu, `yum install bind-utils` on CentOS/RHEL).
- If neither is available, the plugin falls back to `$_SERVER['SERVER_ADDR']` for A record matching.
- The DNS verification runs synchronously in the admin panel when the **Verify** button is clicked. For production use with many domains, consider wrapping it in a queued job.

### Database Schema

Custom domains are stored in the `short_url_custom_domains` table:

| Column       | Type      | Description                                       |
|--------------|-----------|---------------------------------------------------|
| `id`         | bigint    | Primary key                                       |
| `user_id`    | bigint    | Owner (nullable, references `users.id`)           |
| `domain`     | string    | The domain name (e.g. `go.company.com`)           |
| `is_verified`| boolean   | Whether DNS verification passed                   |
| `is_active`  | boolean   | Whether the domain is enabled for use             |
| `created_at` | timestamp | Creation timestamp                                |
| `updated_at` | timestamp | Last update timestamp                             |

Short URLs reference their custom domain via `custom_domain_id` (foreign key on `short_urls`).

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

### API Key Scopes

Each API key can be assigned a **scope** that restricts its access level:

| Scope | Allowed Methods | Use Case |
|---|---|---|
| `links:read-write` | `GET`, `POST`, `PUT`, `PATCH`, `DELETE` | Full control (default) |
| `links:read-only` | `GET` only | Integrations that only need to read link data (e.g., dashboards, reporting tools) |

A `links:read-only` key attempting a write operation (`POST`, `PUT`, `PATCH`, `DELETE`) will receive a `403 Forbidden` response.

### Per-Key Rate Limiting

Each API key can have its own **individual rate limit** (requests per minute), independent of the global route throttle. This allows high-trust integrations to use a higher limit while restricting untrusted keys more aggressively.

Configure the rate limit for each key in **Settings → API & Webhooks → Developer API Keys**. The default is **60 requests per minute**.

### Endpoints

All endpoints are prefix-grouped under `/api/short-url/` and are protected by the API Key middleware. The effective rate limit is the lower of the global route throttle and the per-key rate limit.

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
      "auto_open_app_mobile": false,
      "ga_tracking_id": null,
      "track_visits": true,
      "track_ip_address": true,
      "track_browser": true,
      "track_browser_version": true,
      "track_operating_system": true,
      "track_operating_system_version": true,
      "track_device_type": true,
      "track_referer_url": true,
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

#### `GET /api/short-url/links/{idOrKey}`
Retrieve details for a single short URL using either its database `id` or short `url_key`.

```bash
curl https://yourdomain.com/api/short-url/links/abc123 \
  -H "X-Api-Key: sh_key_your_key_here"
```

**Response:** `200 OK`
```json
{
  "data": {
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
    "auto_open_app_mobile": false,
    "ga_tracking_id": null,
    "track_visits": true,
    "track_ip_address": true,
    "track_browser": true,
    "track_browser_version": true,
    "track_operating_system": true,
    "track_operating_system_version": true,
    "track_device_type": true,
    "track_referer_url": true,
    "track_browser_language": true,
    "pixels": [],
    "notes": null,
    "created_at": "2026-06-01T12:00:00+00:00"
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

#### `PUT/PATCH /api/short-url/links/{idOrKey}`
Update an existing short URL (resolved dynamically by either database `id` or short `url_key`).

```bash
curl -X PATCH https://yourdomain.com/api/short-url/links/promo26 \
  -H "X-Api-Key: sh_key_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "destination_url": "https://example.com/new-product-page",
    "notes": "Updated summer campaign description",
    "is_enabled": true
  }'
```

**Accepted Fields (for POST & PUT/PATCH requests):**

| Field | Type | Required (POST) | Description |
|---|---|---|---|
| `destination_url` | string (URL) | ✅ | Target URL (max 2048 chars) |
| `custom_domain_id` | integer | ❌ | ID of a verified custom domain record |
| `url_key` | string | ❌ | Custom unique slug/key (max 32 chars, alpha-dash; auto-generated if omitted) |
| `notes` | string | ❌ | Internal admin notes (max 255 chars) |
| `is_enabled` | boolean | ❌ | Active status (default: `true`) |
| `redirect_status_code` | integer (301/302) | ❌ | HTTP redirect status code |
| `single_use` | boolean | ❌ | Expire the short link immediately after the first visit |
| `forward_query_params` | boolean | ❌ | Forward visitor query parameters to target destination |
| `max_visits` | integer | ❌ | Maximum click threshold limit |
| `expiration_redirect_url` | string (URL) | ❌ | Fallback URL to redirect to upon link expiration (max 255 chars) |
| `activated_at` | datetime | ❌ | Activation timestamp (must be after or equal to today) |
| `expires_at` | datetime | ❌ | Expiration timestamp (must be after or equal to `activated_at`) |
| `pixels` | array of integers | ❌ | List of registered retargeting pixel IDs to associate with the link |
| `webhook_url` | string (URL) | ❌ | Per-link webhook URL for immediate event notifications |
| `targeting_rules` | array | ❌ | Advanced Multi-Filter Targeting Rules JSON schema (see [Smart Link Targeting](#smart-link-targeting-updated-in-v340) for schema details) |
| `password` | string | ❌ | Password to protect the short URL |
| `show_warning_page` | boolean | ❌ | Toggle redirect warning interstitial page |
| `auto_open_app_mobile` | boolean | ❌ | Auto open deep link in native application on mobile devices |
| `ga_tracking_id` | string | ❌ | Custom Google Analytics 4 Measurement ID for this link (`G-XXXXXXXXXX`, max 50 chars) |
| `track_visits` | boolean | ❌ | Toggle tracking/analytics logging for this link (default: `true`) |
| `track_ip_address` | boolean | ❌ | Track client IP address (default: `true`) |
| `track_browser` | boolean | ❌ | Track client browser name (default: `true`) |
| `track_browser_version` | boolean | ❌ | Track client browser version (default: `true`) |
| `track_operating_system` | boolean | ❌ | Track client OS (default: `true`) |
| `track_operating_system_version` | boolean | ❌ | Track client OS version (default: `true`) |
| `track_device_type` | boolean | ❌ | Track client device type (default: `true`) |
| `track_referer_url` | boolean | ❌ | Track visitor referrer URL (default: `true`) |
| `track_browser_language` | boolean | ❌ | Track visitor preferred browser language (default: `true`) |

**Response (POST & PUT/PATCH success):** `200 OK` (or `201 Created` for POST) containing:
```json
{
  "message": "Short URL updated successfully.",
  "data": {
    "id": 2,
    "destination_url": "https://example.com/new-product-page",
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
    "auto_open_app_mobile": false,
    "ga_tracking_id": null,
    "track_visits": true,
    "track_ip_address": true,
    "track_browser": true,
    "track_browser_version": true,
    "track_operating_system": true,
    "track_operating_system_version": true,
    "track_device_type": true,
    "track_referer_url": true,
    "track_browser_language": true,
    "pixels": [],
    "notes": "Updated summer campaign description",
    "created_at": "2026-06-04T12:00:00+00:00"
  }
}
```

#### `GET /api/short-url/links/{idOrKey}/stats`
Retrieve visit analytics statistics for a single short URL.

```bash
curl https://yourdomain.com/api/short-url/links/promo26/stats \
  -H "X-Api-Key: sh_key_your_key_here"
```

**Optional Query Parameters:**
* `date_from` (string): Filter stats starting from date (e.g. `YYYY-MM-DD`).
* `date_to` (string): Filter stats up to date (e.g. `YYYY-MM-DD`).

**Response:** `200 OK`
```json
{
  "data": {
    "totalVisits": 156,
    "uniqueVisits": 98,
    "visitsToday": 14,
    "visitsThisWeek": 114,
    "visitsThisMonth": 156,
    "visitsByDay": {
      "2026-06-01": 42,
      "2026-06-02": 50
    },
    "visitsByCountry": {
      "Poland": 100,
      "United States": 50
    },
    "visitsByCity": {
      "Warsaw (PL)": 80,
      "Krakow (PL)": 20
    },
    "visitsByDevice": {
      "desktop": 90,
      "mobile": 66
    },
    "visitsByBrowser": {
      "Chrome": 110,
      "Safari": 46
    },
    "visitsByOs": {
      "Windows": 70,
      "macOS": 60
    },
    "visitsByReferer": {
      "linkedin.com": 80,
      "twitter.com": 40
    },
    "utmSources": {
      "linkedin": 80,
      "twitter": 40
    },
    "utmMediums": {
      "social": 120
    },
    "utmCampaigns": {
      "spring_sale": 120
    },
    "qrScans": 12,
    "visitsByLanguage": {
      "pl": 100,
      "en": 56
    }
  }
}
```

#### `DELETE /api/short-url/links/{idOrKey}`
Permanently delete a short URL by its database `id` or short `url_key`.

```bash
curl -X DELETE https://yourdomain.com/api/short-url/links/promo26 \
  -H "X-Api-Key: sh_key_your_key_here"
```

**Response:** `200 OK`
```json
{
  "message": "Short URL deleted successfully."
}
```

### Error Responses

| HTTP Code | Reason |
|---|---|
| `401 Unauthorized` | Missing or invalid API key |
| `404 Not Found` | Short URL not found |
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
| `SHORT_URL_SITE_NAME` | `site_name` | `null` | Brand/Site name override for warning/interstitial pages. |

### User Integration Configuration

The `user` configuration block (new in v3.5.0) connects the package to your application's `User` model so that creator avatars and details are displayed in the admin table:

```php
// config/filament-short-url.php
'user' => [
    'model'         => \App\Models\User::class,
    'name_column'   => 'name',
    'email_column'  => 'email',
    'avatar_column' => 'avatar_url', // attribute name, method name, or null to auto-detect
],
```

**Avatar resolution order:**
1. Call `$user->{avatar_column}()` if it is a method on the model.
2. Read `$user->{avatar_column}` as a direct attribute.
3. Call `$user->getFilamentAvatarUrl()` if the model implements `Filament\Models\Contracts\HasAvatar`.
4. Fall back to a Gravatar URL derived from the user's email.

Set `avatar_column` to `null` to skip steps 1–2 and start from the `HasAvatar` interface check.

The package automatically attaches the currently authenticated user's ID to every newly created short URL via a model `creating` event on `ShortUrl`.

### Full .env Reference

| Environment Variable | Config Path | Default | Description |
|---|---|---|---|
| `SHORT_URL_GEO_IP` | `geo_ip.enabled` | `true` | Globally enable/disable Geo-IP tracking. |
| `SHORT_URL_GEO_IP_DRIVER` | `geo_ip.driver` | `'headers'` | Geo-IP resolver driver (`headers`, `maxmind`, `ip-api`). |
| `SHORT_URL_MAXMIND_DB` | `geo_ip.maxmind.database_path` | `storage_path('geoip/GeoLite2-Country.mmdb')` | Path to local MaxMind db. |
| `SHORT_URL_STATS_CACHE_TTL` | `geo_ip.stats_cache_ttl` | `300` | Caching TTL in seconds for dashboard charts. |
| `SHORT_URL_QUEUE` | `queue_connection` | `'sync'` | Queue connection for recording visits. |
| `SHORT_URL_QUEUE_NAME` | `queue_name` | `'default'` | Queue name to which visit tracking jobs are dispatched. |
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
| `SHORT_URL_DEEP_LINKING_ENABLED` | `deep_linking.enabled` | `false` | Enable serving domain association files for deep linking. |
| `SHORT_URL_ENABLE_FALLBACK` | `enable_fallback_route` | `true` | Toggle registration of the global fallback redirection route. |

> [!TIP]
> **Database Configuration Preferred**: Avoid configuring large, multi-line JSON blocks (such as `apple-app-site-association` and `assetlinks.json`) via `.env` file environment variables as it is error-prone and can cause parsing issues. The recommended approach is to configure them dynamically via the **Filament Settings Panel**, which stores them securely in the database with real-time JSON validation.

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

### v5.0.0

#### Analytics — Live Activity Feed
- **Dedicated `/stats/live` page** — Live Feed is now a full standalone page at `/{record}/stats/live`, on par with Visit Logs at `/{record}/stats/logs`. Tab navigation is consistent across all three stat sub-pages (Statistics / Live Feed / Visit Logs).
- **Intelligent no-change skip** — Each poll tick now calls `checkForUpdates()`, which performs a single O(1) `MAX(id)` query against the composite index. If the highest visit ID hasn't changed since the last render, `skipRender()` is called and Livewire returns a ~100-byte "nothing changed" response — zero PHP rendering, zero DOM operations.
- **Correct cursor initialization** — `$latestVisitId` is initialized to `-1` (sentinel). After the first `getViewData()` call it is set to the actual `MAX(id)`, so the very first poll does not trigger an unnecessary re-render.
- **Cache key versioned by `latestVisitId`** — Fixed a bug where two concurrent users detecting the same new visit could share a thundering-herd cache entry that didn't include the visit that triggered their render, causing them to permanently miss that visit until the next new visit arrived.
- **3-second thundering-herd cache** — Collapses N concurrent admin users watching the same URL into one DB query per 3-second window. Cache key includes URL id, date range, active filters, and `latestVisitId` version.
- **Country flags via `flagcdn.com`** — Flag images are rendered as `<img src="https://flagcdn.com/h20/{cc}.webp">` instead of emoji. The URL is precomputed once in PHP (`getViewData()`), not per-row in Blade. Alpine.js `x-on:error` handles broken images without CSP-unsafe inline `onerror` handlers.
- **Blade performance** — `time_ago` is precomputed once in PHP using `$visit->visited_at->diffForHumans()` (no `Carbon::parse()` double-instantiation). All per-row presentation values are plain PHP arrays, not Eloquent model calls in Blade.
- **Optimized DB query** — Selects only 12 needed columns (`id`, `visited_at`, `country`, `country_code`, `city`, `browser`, `operating_system`, `device_type`, `referer_host`, `referer_url`, `ip_address`, `is_qr_scan`, `selected_variant`) instead of `SELECT *`. Limit reduced to 25 rows.
- **Polling stops when hidden** — `wire:poll.5s.visible` pauses polling completely when the widget is scrolled out of viewport.

#### Link Organization
- **Folders** — Each short URL can be assigned to one folder. Clicking a folder in the sidebar navigates to a filtered link list showing only that folder's links. Link count displayed per folder.
- **Tags** — Each short URL can have up to 5 tags. Clicking a tag shows a filtered link list. Link counts displayed per tag.
- **Archiving** — Short URLs can be archived instead of permanently deleted. Archived links are hidden from the default list view and can be restored at any time via the Filament panel.

#### REST API
- **Per-key API scopes** — Each API key now supports either `links:read-only` (GET only) or `links:read-write` (full CRUD) scope. Read-only keys return `403 Forbidden` on any write attempt.
- **Per-key rate limiting** — Each API key can have its own individual rate limit (requests per minute), independent of the global route throttle. Configure in **Settings → API & Webhooks → Developer API Keys**.

### v4.0.0
- **Custom Domains Branding** — Let users connect branded custom domains with real-time CNAME/A record DNS verification and automatic prefix-free root routing.
- **Cache Leak Fix** — Solved a critical caching bug where single-use links could be visited multiple times during the cache TTL by passing the host-specific key suffix to cache invalidations.
- **Aggregator Performance Tuning** — Restructured daily aggregation SQL queries to filter only active URL IDs, leveraging composite indexes and running native DB aggregations.
- **Logo Upload Authorization** — Secured the logo upload API with resource-level permission and policy checks.
- **DB-Free Registration Phase** — Deferred settings overrides to the service provider boot phase to prevent database errors during offline configuration caching.
- **Conditional Redirection Fallback** — Added configurations to optionally disable global fallback route registration.

### v3.5.0
- **User Attribution** — Short URLs now record the creator. Their avatar and hover card (name + email) are displayed in the list table.
- **Relative Date Badges** — Creation dates show compact relative strings (`2h`, `5d`, `3mo`). Hover to see the precise date and timezone.
- **Keyboard Shortcuts** — Hover any table row and press `E` (edit), `Q` (QR code), `I` (share), `S` (statistics), or `X` (delete).
- **Unified Action Dropdown** — All per-row actions consolidated under a single 3-dot button with keyboard shortcut badges.
- **Row Click → Statistics** — Clicking a table row navigates directly to the link's Statistics page.
- **A/B Split Testing** — Weighted traffic rotation in root-level links and nested targeting rules, with drag-based sliders and a one-click "Balance weights" action.
- **Variant Analytics** — Tracks which A/B variant was served and shows a "Variant Clicks Distribution" chart in the analytics dashboard.
- **Query Optimization** — Composite DB index on the visits table; queries filter by indexed `country_code`; Eloquent hydration bypassed with `toBase()->get()` for large sets.
- **GDPR IP Anonymization** — Opt-in IP masking (IPv4/IPv6) with SHA-256 hashing for unique visitor identification, managed via Settings.
- **Google Safe Browsing in REST API** — Safety checks now apply to all incoming URLs in the API (destination, A/B variants, targeting rules).
- **Settings URL Cleanup** — Tab query parameters are now human-readable (e.g. `?tab=qr` instead of `?tab=qr-defaults::data::tab`).
- **Analytics Enhancements** — QR Scan Conversion Rate card with period-over-period delta, browser/OS version subtexts, A/B click-share vs. weight comparison.

### v3.3.0
- **Advanced Multi-Filter Targeting Engine** — Replaced the legacy single-strategy selection panel with a highly flexible rule engine supporting custom `AND` / `OR` logic matching.
- **Granular Filter Categories** — Added support for filtering by devices (Desktop, Mobile, Tablet), platform operating systems (Windows, macOS, Linux, iOS, Android, Fire OS), countries (multiselect with search), and browser languages (multiselect with search).
- **Client-Side Flag Icons** — Integrated high-quality country flag icons dynamically loaded from a trusted CDN (`flagcdn.com`) inside both the Filament form targeting dropdown and the analytics country statistics widget.
- **Redirection Performance Boost** — Bypassed full user agent parsing (which involves regular expression scanning for versions) during redirections by introducing specialized fast-path `getDeviceType` and `getOs` helper functions.
- **Dynamic Legacy Data Adapter** — Added automatic on-the-fly hydration and upgrade of legacy database records to the new rule format when loaded in the Filament form.

### v3.2.0
- **Expanded Developer REST API** — Added new endpoints to inspect single short URLs (`GET /api/short-url/links/{idOrKey}`), fetch real-time click metrics (`GET /api/short-url/links/{idOrKey}/stats`), and fully update links programmatically (`PUT/PATCH /api/short-url/links/{idOrKey}`). Enabled flexible lookup dynamically by ID or URL key.
- **REST API Throttling & Rate Limiting** — Configured built-in route middleware to rate limit the Developer REST API to 60 requests per minute (`throttle:60,1`) to protect from abuse.
- **Unified Validation System** — Cleaned up and unified API and admin panel form validation, supporting advanced fields like granular logging parameters (`track_visits`, `track_browser_language`, etc.), custom GA4 tracking IDs (`ga_tracking_id`), and auto-open deep linking options (`auto_open_app_mobile`).
- **Media Controller Separation** — Refactored internal logo uploads and serving routes out of the public REST API controller into a dedicated `ShortUrlLogoController` (Single Responsibility compliance).
- **Alpine.js Webhook Payload Preview** — Replaced the Filament package CodeEditor component with a custom, high-reliability dark-mode HTML/CSS component featuring live code highlighting and copy-to-clipboard functionality powered by Alpine.js.

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
- **Custom Branded Expiry Pages** — Replaced raw 410 HTTP errors with a beautiful, fully localized, dark-mode compatible HTML expiry page displaying the Site Name, expired link details, and a homepage button.

### v2.0.0
- **Interactive QR Code Designer Branding Logo** — Upload custom brand logos inside the QR designer canvas in Filament. Configure logo sizing, margins, shapes (square/circle), and toggle dot backing removal to prevent dots overlapping with the logo.
- **Dedicated QR Code Scan Tracking** — Differentiates visitor clicks from physical QR code scans by dynamically appending source tags (`?source=qr`). Added a new database tracking column (`is_qr_scan` on visits, `qr_scans` on short URLs, and `qr_visits_count` on daily stats). Displays a dedicated scans counter badge in the Filament list table.
- **Browser Language Detection & Statistics** — Captures visitor browser preferred language headers (`browser_language` field) and aggregates them into the daily stats table. Displays a new "Top Languages" widget breakdown in the link statistics dashboard.
- **High-Traffic Performance Safeguards & Robust Rollbacks** — Atomic database transactions for buffered counter updates with fail-safe rollback that restores cache values in case of DB connection failures. Prevents N+1 queries by preloading request-wide counters in a single batch cache lookup.
- **Support for Empty Route Prefix (Root-Level URLs)** — Enhanced `getShortUrl()` to support empty route prefixes without generating double slashes (e.g. `domain.com/abc123` instead of `domain.com//abc123`).

### v1.7.0
- **Role-based Settings Access Control** — New `authorizeSettingsUsing(Closure)` method on the plugin to restrict who can access the Settings page. Supports any callable returning a `bool`. Also auto-detects a `manageSettings` method on a registered `ShortUrl` policy. The Settings button in the table header is hidden automatically when access is denied.

### v1.6.0
- **Google Safe Browsing Integration** — Automatic safety checks against Google's API during link creation or modification. Includes bypass settings, asynchronous checking option, and alert badges.
- **VPN / Proxy / Bot Filtering** — Detect and filter out VPN/proxy traffic and Tor nodes using external proxy detection APIs to keep traffic analytics clean.
- **Visitor World Map Widget** — Live interactive SVG world map showing clicks distribution per country, custom intensity highlighting, and hover details.

### v1.5.1
- **REST API On/Off Toggle** — Enable or disable the entire developer REST API from Settings → API & Webhooks without touching code. Returns `503 Service Unavailable` when disabled.

### v1.5.0
- **Social Retargeting Pixels** — Attach Meta Pixel, Google Tag (GA4/Ads), and LinkedIn Insight Tag to any short URL. A premium glassmorphic interstitial page executes pixel scripts in the visitor's browser before forwarding them to the destination. Enables building remarketing audiences even on external domains.
- **Developer REST API** — Full `GET /api/short-url/links`, `POST /api/short-url/links`, and `DELETE /api/short-url/links/{id}` endpoints with API Key authentication (managed via Settings UI). Supports creating links with all available attributes including pixels and webhooks.
- **Webhook System** — Real-time HTTP POST notifications on `visited`, `created`, `expired`, and `limit_reached` events. Configurable per-link or globally. Dispatched asynchronously via `SendWebhookJob` with 3-attempt retry policy and 10-second backoff.
- **Settings: API & Webhooks Tab** — New settings tab to manage global webhook URL, monitored event types, and developer API key management with name labels and per-key activation toggles.

### v1.4.0
- **Validity Date Ranges (From-To)** — Set activation dates (`activated_at` and `expires_at`) to control exactly when a short link is active.
- **Custom Visit Limit Counters** — Define a custom maximum visit limit (`max_visits`) after which a link automatically expires (e.g., active for 3 hits).
- **Custom Expiration Fallbacks** — Redirect expired/inactive visitors to a custom `expiration_redirect_url` rather than showing a static 410 Gone error page.
- **Fluent Builder APIs** — Fluent method additions (`activatedAt()`, `deactivatedAt()`, `maxVisits()`, `expirationRedirectUrl()`) in the developer query builder.

### v1.3.0
- **Automatic Scheduler Registration** — Zero-configuration task registration within the ServiceProvider booted phase (dynamically honors Settings toggles).
- **Interactive Settings Validators** — Adds real-time "Test connection" verify action for GA4 Measurement Protocol and "Verify file" action for MaxMind database paths.
- **Robust Table Row Copy Action** — High-reliability, conflict-free click-to-copy in table rows with built-in fallback helper for non-HTTPS (secure context) browser environments.

### v1.2.0
- **Password-protected links** — Session-based unlock flow with a styled prompt page.
- **Redirect warning pages** — Interstitial security screen before external redirects.
- **Smart targeting** — Device-based, Country/Geo-based, and A/B weighted rotation rules per link.
- **Rate limiting** — Configurable per-IP redirect throttling with 429 responses.
- **Daily stats aggregation** — Nightly `short-url:aggregate-and-prune` command for scalable log management.
- **Extended Settings GUI** — New "Performance & Security" tab for aggregation and rate limiting configuration.
- **Polish translations** — Full `pl` locale support for all new features.

---

## License

Custom Source-Available License. Please see the [LICENSE](LICENSE) file for the full text.
