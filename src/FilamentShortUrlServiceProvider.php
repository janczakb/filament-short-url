<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Assets\ShortUrlCss;
use Bjanczak\FilamentShortUrl\Assets\ShortUrlJs;
use Bjanczak\FilamentShortUrl\Console\Commands\StressRedirectCommand;
use Bjanczak\FilamentShortUrl\Console\Commands\SyncBufferedCountersCommand;
use Bjanczak\FilamentShortUrl\Console\Commands\VerifyCustomDomainsCommand;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Policies\ShortUrlPolicy;
use Bjanczak\FilamentShortUrl\Services\Ga4MeasurementProtocolService;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\OgImageImporter;
use Bjanczak\FilamentShortUrl\Services\OgImageProcessor;
use Bjanczak\FilamentShortUrl\Services\ProxyDetectionService;
use Bjanczak\FilamentShortUrl\Services\Queue\PluginQueueWorkerTester;
use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnectionTester;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsScalingProfile;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsVisitRecorder;
use Bjanczak\FilamentShortUrl\Services\Stats\TodayStatsBuffer;
use Bjanczak\FilamentShortUrl\Services\UrlMetaScraper;
use Bjanczak\FilamentShortUrl\Services\UserAgentParser;
use Bjanczak\FilamentShortUrl\Services\VisitCounterBuffer;
use BladeUI\Icons\Factory;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
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
                '2026_06_08_000001_add_api_and_utm_fields_to_short_urls_table',
                '2026_06_09_000001_audit_schema_and_performance_fixes',
                '2026_06_10_000001_add_security_counts_to_daily_stats',
                '2026_06_11_000001_add_cross_dimensional_stats_to_daily_stats',
            ])
            ->hasCommands([
                SyncBufferedCountersCommand::class,
                Console\Commands\AggregateAndPruneVisitsCommand::class,
                VerifyCustomDomainsCommand::class,
                StressRedirectCommand::class,
            ])
            ->hasRoutes(['web']);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ShortUrlSettingsManager::class);

        // Bind services as singletons for efficient reuse
        $this->app->singleton(UserAgentParser::class);
        $this->app->singleton(GeoIpService::class);
        $this->app->singleton(Ga4MeasurementProtocolService::class);
        $this->app->singleton(UrlMetaScraper::class);
        $this->app->singleton(OgImageProcessor::class);
        $this->app->singleton(OgImageImporter::class);
        $this->app->singleton(ShortUrlService::class);
        $this->app->singleton(ShortUrlTracker::class);
        $this->app->singleton(ProxyDetectionService::class);
        $this->app->singleton(SafeBrowsingService::class);
        $this->app->singleton(StatsScalingProfile::class);
        $this->app->singleton(PluginRedisConnection::class);
        $this->app->singleton(PluginRedisConnectionTester::class);
        $this->app->singleton(PluginQueueWorkerTester::class);
        $this->app->singleton(VisitCounterBuffer::class);
        $this->app->singleton(TodayStatsBuffer::class);
        $this->app->singleton(StatsVisitRecorder::class);

        $this->callAfterResolving(Factory::class, function (Factory $factory) {
            $factory->add('filament-short-url', [
                'path' => __DIR__.'/../resources/svg',
                'prefix' => 'fsu',
            ]);
        });
    }

    public function packageBooted(): void
    {
        $this->app->terminating(function (): void {
            GeoIpService::flush();
            ShortUrl::flushBufferedCounterMemory();
        });

        FilamentAsset::register([
            ShortUrlCss::make('filament-short-url', __DIR__.'/../resources/dist/filament-short-url.css'),
            ShortUrlJs::make('qr-code-styling', __DIR__.'/../resources/dist/qr-code-styling.js'),
            ShortUrlJs::make('filament-short-url-js', __DIR__.'/../resources/dist/filament-short-url.js'),
            ShortUrlJs::make('filament-short-url-meta-scraper', __DIR__.'/../resources/dist/meta-scraper.js'),
        ], package: 'janczakb/filament-short-url');

        // Automatically register scheduled tasks in the application scheduler
        $this->app->booted(function (): void {
            if (! Gate::getPolicyFor(ShortUrl::class)) {
                Gate::policy(ShortUrl::class, ShortUrlPolicy::class);
            }

            $schedule = $this->app->make(Schedule::class);

            $schedule->command('short-url:aggregate-and-prune')->dailyAt('02:00');
            $schedule->command('short-url:verify-custom-domains')->daily();

            if (config('filament-short-url.counter_buffering.enabled', false)) {
                $schedule->command('short-url:sync-counters')->everyMinute();
            }
        });
    }

    public function boot(): void
    {
        $this->app->make(ShortUrlSettingsManager::class)->applyConfigOverrides();

        parent::boot();
    }
}
