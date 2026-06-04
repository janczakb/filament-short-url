<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Console\Commands\SyncBufferedCountersCommand;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\ProxyDetectionService;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Bjanczak\FilamentShortUrl\Services\UserAgentParser;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentShortUrlServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-short-url';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('filament-short-url')
            ->hasViews('filament-short-url')
            ->hasTranslations()
            ->hasMigrations([
                '2024_01_01_000001_create_short_urls_table',
                '2024_01_01_000002_create_short_url_visits_table',
                '2026_06_01_000003_add_utm_city_referer_to_short_url_visits_table',
                '2026_06_01_000004_add_targeting_and_security_to_short_urls_table',
                '2026_06_01_000005_create_short_url_daily_stats_table',
                '2026_06_02_000006_add_max_visits_and_expiration_redirect_to_short_urls_table',
                '2026_06_02_000007_add_retargeting_pixels_and_webhooks_to_short_urls_table',
                '2026_06_02_000008_add_bot_and_proxy_to_short_url_visits_table',
                '2026_06_02_224250_add_qr_logo_to_short_urls_table',
                '2026_06_03_110000_add_qr_and_language_to_visits_and_daily_stats',
                '2026_06_03_120000_add_track_browser_language_to_short_urls_table',
                '2026_06_03_150000_create_short_url_pixels_table',
                '2026_06_03_160000_add_auto_open_app_mobile_to_short_urls_table',
                '2026_06_04_000000_create_short_url_settings_table',
            ])
            ->hasCommands([
                SyncBufferedCountersCommand::class,
                Console\Commands\AggregateAndPruneVisitsCommand::class,
            ])
            ->hasRoutes(['web']);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ShortUrlSettingsManager::class);
        $this->app->make(ShortUrlSettingsManager::class)->applyConfigOverrides();

        // Bind services as singletons for efficient reuse
        $this->app->singleton(UserAgentParser::class);
        $this->app->singleton(GeoIpService::class);
        $this->app->singleton(ShortUrlService::class);
        $this->app->singleton(ShortUrlTracker::class);
        $this->app->singleton(ProxyDetectionService::class);
        $this->app->singleton(SafeBrowsingService::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-short-url', __DIR__.'/../resources/dist/filament-short-url.css'),
        ], package: 'janczakb/filament-short-url');

        // Automatically register scheduled tasks in the application scheduler
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command('short-url:aggregate-and-prune')->dailyAt('02:00');

            if (config('filament-short-url.counter_buffering.enabled', false)) {
                $schedule->command('short-url:sync-counters')->everyMinute();
            }
        });
    }
}
