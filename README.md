# Filament Short URL

A professional, high-performance **Short URL Manager** plugin for [Filament v5](https://filamentphp.com). Built from scratch with cutting-edge practices, proxy resistance, offline Geo-IP engines, and zero external shortening API dependencies.

---

## Features

- 🔗 **Short URL Generation** — custom or auto-generated collision-free base62 keys.
- 🌍 **Multiple Geo-IP Drivers** — offline detection using local MaxMind databases, edge-provided CDN headers (Cloudflare, CloudFront), or fallback API integration.
- 📈 **Real-Time Statistics Dashboard** — sortable log of visits with cached aggregate performance metrics (browsers, devices, OS, referers, country maps, timeline charts).
- 🎨 **QR Code Designer** — live design canvas (sizes, dot styles, gradient configurations, and background transparency triggers) with instant SVG download.
- ⚡ **Ultra-Fast Redirects** — redirects resolve in milliseconds; analytical tasks and GA4 payloads are processed asynchronously via Laravel Queue jobs.
- 🎯 **Google Analytics 4 Measurement Protocol** — native server-side event tracking, bypassing browser-side AdBlockers completely.
- ⚙️ **Reactive UTM Campaign Builder** — double-way synchronized UTM builder panel inside the Filament form, syncing seamlessly with destination URLs in real-time.
- 🔒 **Single-Use & Expirable Links** — automatically deactivates links after a single visit or at a specified date/time.
- ➡️ **Query Parameter Forwarding** — dynamically appends incoming client query parameters (e.g. ad clicks, discount codes) to the final destination URL.
- 🛠️ **Dedicated Settings Interface** — full administrator control panel inside Filament to manage routing, Geo-IP, and GA4 credentials without editing `.env` files.
- 💻 **Fluent Developer Builder** — static Model builder patterns and trace tagging support.

---

## Requirements

- PHP 8.3+
- Laravel 11+
- Filament 5+

---

## Installation

Install the package via Composer:

```bash
composer require bjanczak/filament-short-url
```

Publish and run the database migrations:

```bash
php artisan vendor:publish --tag=filament-short-url-migrations
php artisan migrate
```

---

## Publishing Package Assets

The package is built with standard publishing tags to let developers easily customize and override the default assets within the host application directory:

### 1. Publish Config File
Copies the default config file to `config/filament-short-url.php` for code-level customization:
```bash
php artisan vendor:publish --tag=filament-short-url-config
```

### 2. Publish Translation Files
Copies localization files to `lang/vendor/filament-short-url/` so you can modify or add new languages (Polish and English included by default):
```bash
php artisan vendor:publish --tag=filament-short-url-translations
```

### 3. Publish Blade Views & Templates
Copies the dashboard components, charts, and QR designer templates to `resources/views/vendor/filament-short-url/` to completely override the styling:
```bash
php artisan vendor:publish --tag=filament-short-url-views
```

### 4. Publish Everything at Once
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
                ->navigationGroup('Tools')   // optional
                ->navigationSort(50),        // optional
        ]);
}
```

---

## Usage in Code

### 1. Programmatic Creation via Fluent Builder
You can easily create, configure, and append tracking tags to short URLs programmatically using the fluent builder:

```php
use Bjanczak\FilamentShortUrl\Models\ShortUrl;

$shortUrl = ShortUrl::destination('https://example.com/very/long/url')
    ->urlKey('promo2026')   // optional, auto-generated if empty
    ->notes('Spring campaign promo')
    ->singleUse()           // deactivates after first visit
    ->forwardQueryParams()  // forward incoming visitor query strings
    ->withTracing([         // dynamically filters and appends UTM parameters
        'utm_source'   => 'linkedin',
        'utm_medium'   => 'social',
        'utm_campaign' => 'spring_sale',
        'utm_content'  => null, // skipped automatically
    ])
    ->create();

// Output the public short URL string
echo $shortUrl->getShortUrl(); // https://yourapp.com/s/promo2026
```

### 2. Standard Service Injection
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

You can listen to short URL visits dynamically in your application (e.g. for real-time alerts or external webhooks):

```php
use Bjanczak\FilamentShortUrl\Events\ShortUrlVisited;
use Illuminate\Support\Facades\Event;

Event::listen(ShortUrlVisited::class, function (ShortUrlVisited $event) {
    $event->shortUrl; // The ShortUrl model instance
    $event->visit;    // The ShortUrlVisit model instance (contains resolved client IP, browser, OS, country, etc.)
});
```

---

## License

MIT
