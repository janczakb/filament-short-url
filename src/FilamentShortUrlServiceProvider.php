<?php

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Console\Commands\SyncBufferedCountersCommand;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
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
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-short-url', __DIR__.'/../resources/dist/filament-short-url.css'),
        ], package: 'janczakb/filament-short-url');

        // Automatically register scheduled tasks in the application scheduler
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);

            if (config('filament-short-url.pruning.enabled', true)) {
                $schedule->command('short-url:aggregate-and-prune')->dailyAt('02:00');
            }

            if (config('filament-short-url.counter_buffering.enabled', false)) {
                $schedule->command('short-url:sync-counters')->everyMinute();
            }
        });
    }
}
