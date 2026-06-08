<?php

use Bjanczak\FilamentShortUrl\Console\Commands\AggregateAndPruneVisitsCommand;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\Stats\CrossDimensionalStatsEngine;
use Bjanczak\FilamentShortUrl\Services\Stats\FilteredStatsCollector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

if (! function_exists('createCrossDimLink')) {
    function createCrossDimLink(array $attrs = []): ShortUrl
    {
        return app(ShortUrlService::class)->create(array_merge([
            'destination_url' => 'https://example.com',
        ], $attrs));
    }
}

it('accumulates cross-dimensional slices during daily aggregation cursor pass', function () {
    $link = createCrossDimLink();

    $date = Carbon::yesterday()->toDateString();

    $link->visits()->create([
        'ip_address' => '1.1.1.1',
        'ip_hash' => hash('sha256', '1.1.1.1'),
        'country_code' => 'PL',
        'browser' => 'Chrome',
        'device_type' => 'desktop',
        'visited_at' => $date.' 12:00:00',
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $link->visits()->create([
        'ip_address' => '2.2.2.2',
        'ip_hash' => hash('sha256', '2.2.2.2'),
        'country_code' => 'DE',
        'browser' => 'Safari',
        'device_type' => 'mobile',
        'visited_at' => $date.' 13:00:00',
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $this->artisan(AggregateAndPruneVisitsCommand::class)->assertSuccessful();

    $daily = ShortUrlDailyStats::query()
        ->where('short_url_id', $link->id)
        ->whereDate('date', $date)
        ->first();

    expect($daily)->not->toBeNull()
        ->and($daily->cross_dimensional_stats['country_code']['PL']['browser']['Chrome'] ?? null)->toBe(1)
        ->and($daily->cross_filter_pairs['browser:Chrome|country_code:PL']['_total'] ?? null)->toBe(1);
});

it('serves filtered browser breakdown for a country filter from daily cross rollups', function () {
    $link = createCrossDimLink();
    $date = Carbon::yesterday()->toDateString();

    ShortUrlDailyStats::query()->create([
        'short_url_id' => $link->id,
        'date' => $date,
        'visits_count' => 2,
        'unique_visits_count' => 2,
        'all_visits_count' => 2,
        'bot_visits_count' => 0,
        'proxy_visits_count' => 0,
        'qr_visits_count' => 0,
        'country_stats' => ['PL' => 2],
        'browser_stats' => ['Chrome' => 1, 'Safari' => 1],
        'cross_dimensional_stats' => [
            'country_code' => [
                'PL' => [
                    'browser' => ['Chrome' => 1, 'Safari' => 1],
                ],
            ],
        ],
        'cross_filter_pairs' => [],
        'filter_qr_counts' => [],
    ]);

    $stats = $link->getCachedStats(
        dateFrom: $date,
        dateTo: $date,
        filters: ['country_code' => 'PL'],
    );

    expect($stats['totalVisits'])->toBe(2)
        ->and($stats['visitsByBrowser'])->toMatchArray(['Chrome' => 1, 'Safari' => 1]);
});

it('serves two-filter breakdowns from cross_filter_pairs without scanning full raw history', function () {
    $link = createCrossDimLink();
    $date = Carbon::yesterday()->toDateString();

    ShortUrlDailyStats::query()->create([
        'short_url_id' => $link->id,
        'date' => $date,
        'visits_count' => 1,
        'unique_visits_count' => 1,
        'all_visits_count' => 1,
        'bot_visits_count' => 0,
        'proxy_visits_count' => 0,
        'qr_visits_count' => 0,
        'country_stats' => ['PL' => 1],
        'browser_stats' => ['Chrome' => 1],
        'cross_dimensional_stats' => [],
        'cross_filter_pairs' => [
            CrossDimensionalStatsEngine::compositeFilterKey([
                'country_code' => 'PL',
                'browser' => 'Chrome',
            ]) => [
                '_total' => 1,
                'device_type' => ['desktop' => 1],
            ],
        ],
        'filter_qr_counts' => [],
    ]);

    $stats = (new FilteredStatsCollector($link))->collect(
        $date,
        $date,
        ['country_code' => 'PL', 'browser' => 'Chrome'],
    );

    expect($stats['totalVisits'])->toBe(1)
        ->and($stats['visitsByDevice'])->toMatchArray(['desktop' => 1]);
});

it('re-aggregates days missing cross-dimensional rollups', function () {
    $link = createCrossDimLink();
    $date = Carbon::yesterday()->toDateString();

    ShortUrlDailyStats::query()->create([
        'short_url_id' => $link->id,
        'date' => $date,
        'visits_count' => 1,
        'unique_visits_count' => 1,
        'all_visits_count' => 1,
        'bot_visits_count' => 0,
        'proxy_visits_count' => 0,
        'qr_visits_count' => 0,
        'country_stats' => ['PL' => 1],
        'cross_dimensional_stats' => null,
        'cross_filter_pairs' => null,
        'filter_qr_counts' => null,
    ]);

    $link->visits()->create([
        'ip_address' => '1.1.1.1',
        'ip_hash' => hash('sha256', '1.1.1.1'),
        'country_code' => 'PL',
        'browser' => 'Chrome',
        'visited_at' => $date.' 10:00:00',
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $this->artisan(AggregateAndPruneVisitsCommand::class)->assertSuccessful();

    $daily = ShortUrlDailyStats::query()->where('short_url_id', $link->id)->whereDate('date', $date)->first();

    expect($daily->cross_dimensional_stats)->not->toBeNull()
        ->and($daily->cross_filter_pairs)->not->toBeNull();
});
