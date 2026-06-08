<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsScalingProfile;
use Bjanczak\FilamentShortUrl\Services\Stats\TodayStatsBuffer;
use Bjanczak\FilamentShortUrl\Services\VisitCounterBuffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    cache()->forget('filament-short-url:settings');
});

it('forces counter buffering when queue connection is redis', function () {
    app(ShortUrlSettingsManager::class)->set([
        'queue_connection' => 'redis',
        'counter_buffering_enabled' => false,
    ]);

    expect(config('filament-short-url.counter_buffering.enabled'))->toBeTrue()
        ->and(app(StatsScalingProfile::class)->shouldForceCounterBuffering())->toBeTrue()
        ->and(app(StatsScalingProfile::class)->counterBufferingEnabled())->toBeTrue();
});

it('uses sync optimized micro cache ttl for today sql rollups', function () {
    config([
        'filament-short-url.queue_connection' => 'sync',
        'filament-short-url.geo_ip.stats_cache_ttl' => 300,
    ]);

    expect(app(StatsScalingProfile::class)->todaySqlMicroCacheTtl())->toBe(15);
});

it('keeps historical stats cache hot while today totals refresh from micro cache', function () {
    config([
        'filament-short-url.queue_connection' => 'sync',
        'filament-short-url.geo_ip.stats_cache_ttl' => 300,
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/stats-scale',
        'url_key' => 'statsscale',
        'track_visits' => true,
    ]);

    $stats = $shortUrl->getCachedStats();
    expect($stats['totalVisits'])->toBe(0);

    $shortUrl->visits()->create([
        'ip_hash' => hash('sha256', '9.9.9.9'),
        'visited_at' => now(),
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    expect($shortUrl->getCachedStats()['totalVisits'])->toBe(0);

    $shortUrl->clearStatsCache();

    expect($shortUrl->getCachedStats()['totalVisits'])->toBe(1);
});

it('records today totals through stats visit recorder without busting historical cache key', function () {
    config([
        'filament-short-url.queue_connection' => 'sync',
        'filament-short-url.geo_ip.stats_cache_ttl' => 300,
        'filament-short-url.geo_ip.enabled' => false,
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/recorder',
        'url_key' => 'statsrec',
        'track_visits' => true,
    ]);

    $shortUrl->getCachedStats();

    $tracker = app(ShortUrlTracker::class);
    $request = Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '8.8.8.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
    ]);

    $visit = $tracker->record(
        shortUrl: $shortUrl,
        request: $request,
        precomputedProxyDetection: ['is_bot' => false, 'is_proxy' => false],
    );

    expect($visit)->toBeInstanceOf(ShortUrlVisit::class);

    cache()->forget('filament-short-url:stats:today:sql:'.$shortUrl->id.':'.now()->toDateString());

    $freshShortUrl = ShortUrl::findOrFail($shortUrl->id);

    expect($freshShortUrl->getCachedStats()['totalVisits'])->toBe(1);
});

it('uses dedicated queue redis for counters when settings queue is redis', function () {
    config(['filament-short-url.queue_connection' => 'redis']);

    if (! app(PluginRedisConnection::class)->isAvailable()) {
        expect(app(StatsScalingProfile::class)->usesDedicatedRedisCounters())->toBeFalse();

        return;
    }

    expect(app(VisitCounterBuffer::class)->usesDedicatedRedis())->toBeTrue()
        ->and(app(StatsScalingProfile::class)->usesRedisTodayBuffer())->toBeTrue();
});

it('reads today summary from dedicated queue redis when settings queue is redis', function () {
    config(['filament-short-url.queue_connection' => 'redis']);

    if (! app(PluginRedisConnection::class)->isAvailable()) {
        $this->markTestSkipped('Requires queue Redis connection.');
    }

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/redis-stats',
        'url_key' => 'redisstats',
        'track_visits' => true,
    ]);

    $visit = new ShortUrlVisit([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'is_qr_scan' => false,
        'ip_hash' => hash('sha256', '1.2.3.4'),
    ]);

    app(TodayStatsBuffer::class)->recordVisit($shortUrl, $visit, true);

    $summary = app(TodayStatsBuffer::class)->getTodaySummary((int) $shortUrl->id);

    expect($summary)->not->toBeNull()
        ->and($summary['totalVisits'])->toBe(1)
        ->and($summary['uniqueVisits'])->toBe(1)
        ->and($summary['source'])->toBe('redis');

    app(TodayStatsBuffer::class)->clearToday((int) $shortUrl->id);
});

it('buffers link totals on dedicated redis when settings queue is redis', function () {
    config(['filament-short-url.queue_connection' => 'redis']);

    if (! app(PluginRedisConnection::class)->isAvailable()) {
        $this->markTestSkipped('Requires queue Redis connection.');
    }

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/redis-counter',
        'url_key' => 'rediscounter',
        'track_visits' => true,
        'total_visits' => 5,
    ]);

    $shortUrl->incrementVisits(isUnique: true, isQrScan: false);

    expect($shortUrl->getRealTimeTotalVisits())->toBe(6)
        ->and((int) $shortUrl->fresh()->total_visits)->toBe(5);
});
