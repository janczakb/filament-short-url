<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Assets\ShortUrlCss;
use Bjanczak\FilamentShortUrl\Assets\ShortUrlJs;
use Bjanczak\FilamentShortUrl\Console\Commands\SyncBufferedCountersCommand;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\OgImageImporter;
use Bjanczak\FilamentShortUrl\Services\OgImageProcessor;
use Bjanczak\FilamentShortUrl\Services\ProxyDetectionService;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Bjanczak\FilamentShortUrl\Services\UrlMetaScraper;
use Bjanczak\FilamentShortUrl\Services\UserAgentParser;
use BladeUI\Icons\Factory;
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
                '2024_01_01_000001_create_short_url_settings_table',
                '2024_01_01_000002_create_short_url_pixels_table',
                '2024_01_01_000003_create_short_urls_table',
                '2024_01_01_000004_create_short_url_visits_table',
                '2024_01_01_000005_create_short_url_daily_stats_table',
                '2026_06_05_000001_add_user_id_to_short_urls_table',
                '2026_06_05_000002_create_short_url_custom_domains_table',
                '2026_06_05_000003_add_custom_domain_id_to_short_urls_table',
                '2026_06_06_000001_create_short_url_folders_and_tags_tables',
                '2026_06_06_000002_add_og_metadata_to_short_urls_table',
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

        // Bind services as singletons for efficient reuse
        $this->app->singleton(UserAgentParser::class);
        $this->app->singleton(GeoIpService::class);
        $this->app->singleton(UrlMetaScraper::class);
        $this->app->singleton(OgImageProcessor::class);
        $this->app->singleton(OgImageImporter::class);
        $this->app->singleton(ShortUrlService::class);
        $this->app->singleton(ShortUrlTracker::class);
        $this->app->singleton(ProxyDetectionService::class);
        $this->app->singleton(SafeBrowsingService::class);

        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add('filament-short-url', [
                'path' => __DIR__.'/../resources/svg',
                'prefix' => 'fsu',
            ]);
        });
    }

    public function packageBooted(): void
    {
        $this->app->make(ShortUrlSettingsManager::class)->applyConfigOverrides();

        FilamentAsset::register([
            ShortUrlCss::make('filament-short-url', __DIR__.'/../resources/dist/filament-short-url.css'),
            ShortUrlJs::make('qr-code-styling', __DIR__.'/../resources/dist/qr-code-styling.js'),
            ShortUrlJs::make('filament-short-url-js', __DIR__.'/../resources/dist/filament-short-url.js'),
            ShortUrlJs::make('filament-short-url-meta-scraper', __DIR__.'/../resources/dist/meta-scraper.js'),
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
