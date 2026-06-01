<?php

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Console\Commands\SyncBufferedCountersCommand;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Bjanczak\FilamentShortUrl\Services\UserAgentParser;
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
            ])
            ->hasCommand(SyncBufferedCountersCommand::class)
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
}
