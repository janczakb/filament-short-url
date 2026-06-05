<?php

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlDeviceBreakdownWidget;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGeoBreakdownWidget;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlSecurityBreakdownWidget;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlSourcesBreakdownWidget;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlStatsOverview;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsChart;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

if (! function_exists('createStatsLink')) {
    function createStatsLink(array $attrs = []): ShortUrl
    {
        return app(ShortUrlService::class)->create(array_merge([
            'destination_url' => 'https://example.com',
        ], $attrs));
    }
}

it('calculates period over period trends in stats overview', function () {
    $link = createStatsLink();

    // Current period (today): 10 visits
    for ($i = 0; $i < 10; $i++) {
        $link->visits()->create([
            'ip_address' => '1.2.3.'.$i,
            'ip_hash' => hash('sha256', '1.2.3.'.$i),
            'visited_at' => now(),
        ]);
    }

    // Previous period (35 days ago): 5 visits
    for ($i = 0; $i < 5; $i++) {
        $link->visits()->create([
            'ip_address' => '5.6.7.'.$i,
            'ip_hash' => hash('sha256', '5.6.7.'.$i),
            'visited_at' => now()->subDays(35),
        ]);
    }

    $widget = new ShortUrlStatsOverview;
    $widget->record = $link;
    $widget->dateFrom = now()->subDays(29)->toDateString();
    $widget->dateTo = now()->toDateString();

    // Trigger the protected getStats() method reflection
    $ref = new ReflectionMethod($widget, 'getStats');
    $ref->setAccessible(true);
    $stats = $ref->invoke($widget);

    // Total clicks current = 10, previous = 5 -> +100.0% trend description
    $totalStat = $stats[0];
    expect($totalStat->getDescription())->toContain('+100.0%');
    expect($totalStat->getDescriptionColor())->toBe('success');

    // Unique visits current = 10, previous = 5 -> +100.0% trend description
    $uniqueStat = $stats[1];
    expect($uniqueStat->getDescription())->toContain('+100.0%');
});

it('groups chart data by weekly and monthly granularity', function () {
    $link = createStatsLink();

    // Create a visit 10 days ago and another today
    $link->visits()->create([
        'ip_address' => '1.1.1.1',
        'visited_at' => now()->subDays(10),
    ]);
    $link->visits()->create([
        'ip_address' => '2.2.2.2',
        'visited_at' => now(),
    ]);

    $widget = new ShortUrlVisitsChart;
    $widget->record = $link;
    $widget->dateFrom = now()->subDays(29)->toDateString();
    $widget->dateTo = now()->toDateString();

    // Test weekly
    $widget->filter = 'weekly';
    $ref = new ReflectionMethod($widget, 'getData');
    $ref->setAccessible(true);
    $weeklyData = $ref->invoke($widget);

    expect(count($weeklyData['datasets'][0]['data']))->toBeGreaterThan(0);

    // Test monthly
    $widget->filter = 'monthly';
    $monthlyData = $ref->invoke($widget);
    expect(count($monthlyData['datasets'][0]['data']))->toBeGreaterThan(0);
});

it('memoizes statistics in-memory and allows selective cache clearing', function () {
    $link = createStatsLink();
    $link->visits()->create([
        'ip_address' => '1.2.3.4',
        'visited_at' => now(),
    ]);

    // Retrieve stats (caches it)
    $stats1 = $link->getCachedStats();
    expect($stats1['totalVisits'])->toBe(1);

    // Add a new visit directly to DB (bypassing cache increment)
    $link->visits()->create([
        'ip_address' => '5.6.7.8',
        'visited_at' => now(),
    ]);

    // Retrieve stats again (should hit in-memory memoization / cache, returning 1)
    $stats2 = $link->getCachedStats();
    expect($stats2['totalVisits'])->toBe(1);

    // Clear stats cache
    $link->clearStatsCache();

    // Retrieve stats again (should compute fresh, returning 2)
    $stats3 = $link->getCachedStats();
    expect($stats3['totalVisits'])->toBe(2);
});

it('dynamically defaults chart granularity based on date range', function () {
    $link = createStatsLink();

    $widget = new ShortUrlVisitsChart;
    $widget->record = $link;

    // <= 1 day -> hourly
    $widget->dateFrom = now()->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->mount();
    expect($widget->filter)->toBe('hourly');

    // <= 30 days -> daily
    $widget->dateFrom = now()->subDays(29)->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->mount();
    expect($widget->filter)->toBe('daily');

    // <= 90 days -> weekly
    $widget->dateFrom = now()->subDays(89)->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->mount();
    expect($widget->filter)->toBe('weekly');

    // > 90 days -> monthly
    $widget->dateFrom = now()->subDays(120)->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->mount();
    expect($widget->filter)->toBe('monthly');
});

it('calculates data for geo breakdown widget', function () {
    $link = createStatsLink();
    $link->visits()->create([
        'ip_address' => '1.2.3.4',
        'city' => 'Warsaw',
        'country_code' => 'PL',
        'browser_language' => 'pl',
        'visited_at' => now(),
    ]);

    $widget = new ShortUrlGeoBreakdownWidget;
    $widget->record = $link;
    $widget->dateFrom = now()->subDays(2)->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->loadedTabs = ['countries' => true, 'cities' => true, 'languages' => true];

    $ref = new ReflectionMethod($widget, 'getViewData');
    $ref->setAccessible(true);
    $data = $ref->invoke($widget);

    expect($data['visitsByCountry'])->toBe(['PL' => 1])
        ->and($data['visitsByCity'])->toBe(['Warsaw (PL)' => 1])
        ->and($data['visitsByLanguage'])->toBe(['pl' => 1]);
});

it('calculates data for device breakdown widget', function () {
    $link = createStatsLink();
    $link->visits()->create([
        'ip_address' => '1.2.3.4',
        'device_type' => 'mobile',
        'browser' => 'Chrome',
        'operating_system' => 'Android',
        'visited_at' => now(),
    ]);

    $widget = new ShortUrlDeviceBreakdownWidget;
    $widget->record = $link;
    $widget->dateFrom = now()->subDays(2)->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->loadedTabs = ['devices' => true, 'browsers' => true, 'os' => true];

    $ref = new ReflectionMethod($widget, 'getViewData');
    $ref->setAccessible(true);
    $data = $ref->invoke($widget);

    expect($data['visitsByDevice'])->toBe(['mobile' => 1])
        ->and($data['visitsByBrowser'])->toBe(['Chrome' => 1])
        ->and($data['visitsByOs'])->toBe(['Android' => 1]);
});

it('calculates data for traffic sources breakdown widget', function () {
    $link = createStatsLink();
    $link->visits()->create([
        'ip_address' => '1.2.3.4',
        'referer_host' => 'facebook.com',
        'utm_source' => 'fb',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'summer',
        'utm_term' => 'yacht',
        'utm_content' => 'ad1',
        'visited_at' => now(),
    ]);

    $widget = new ShortUrlSourcesBreakdownWidget;
    $widget->record = $link;
    $widget->dateFrom = now()->subDays(2)->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->loadedTabs = ['referrers' => true, 'utm_sources' => true, 'utm_mediums' => true, 'utm_campaigns' => true, 'utm_terms' => true, 'utm_contents' => true];

    $ref = new ReflectionMethod($widget, 'getViewData');
    $ref->setAccessible(true);
    $data = $ref->invoke($widget);

    expect($data['visitsByReferer'])->toBe(['Facebook' => 1])
        ->and($data['utmSources'])->toBe(['fb' => 1])
        ->and($data['utmMediums'])->toBe(['cpc' => 1])
        ->and($data['utmCampaigns'])->toBe(['summer' => 1])
        ->and($data['utmTerms'])->toBe(['yacht' => 1])
        ->and($data['utmContents'])->toBe(['ad1' => 1]);
});

it('calculates data for security breakdown widget', function () {
    $link = createStatsLink();
    $link->visits()->create([
        'ip_address' => '1.2.3.4',
        'is_bot' => true,
        'is_proxy' => true,
        'visited_at' => now(),
    ]);

    $widget = new ShortUrlSecurityBreakdownWidget;
    $widget->record = $link;
    $widget->dateFrom = now()->subDays(2)->toDateString();
    $widget->dateTo = now()->toDateString();
    $widget->loadedTabs = ['bots' => true, 'vpn' => true];

    $ref = new ReflectionMethod($widget, 'getViewData');
    $ref->setAccessible(true);
    $data = $ref->invoke($widget);

    expect($data['totalClicks'])->toBe(1)
        ->and($data['botClicks'])->toBe(1)
        ->and($data['proxyClicks'])->toBe(1);
});
