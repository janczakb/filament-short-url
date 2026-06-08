<?php

use Bjanczak\FilamentShortUrl\Services\LiveFeedBroadcaster;

it('falls back to sse polling when no redis source is available', function () {
    config([
        'filament-short-url.live_feed.use_redis_push' => true,
        'filament-short-url.queue_connection' => 'sync',
    ]);

    expect(LiveFeedBroadcaster::usesRedisPush())->toBeFalse()
        ->and(LiveFeedBroadcaster::resolveRedisSource())->toBeNull()
        ->and(LiveFeedBroadcaster::redisSourceDriver())->toBeNull();
});

it('can disable redis push via config even with redis queue setting', function () {
    config([
        'filament-short-url.live_feed.use_redis_push' => false,
        'filament-short-url.queue_connection' => 'redis',
    ]);

    expect(LiveFeedBroadcaster::usesRedisPush())->toBeFalse();
});

it('stores latest visit id in cache on publish when redis is unavailable', function () {
    config(['filament-short-url.queue_connection' => 'sync']);

    LiveFeedBroadcaster::publish(42, 9001);

    expect(LiveFeedBroadcaster::latestId(42))->toBe(9001);
});

it('waitForPublish returns null when redis push is unavailable', function () {
    expect(LiveFeedBroadcaster::waitForPublish(1, 1))->toBeNull();
});

it('prefers plugin queue_connection redis over cache store for redis source driver', function () {
    config(['filament-short-url.queue_connection' => 'redis']);

    if (LiveFeedBroadcaster::redisSourceFromPluginQueueSettings() === null) {
        expect(LiveFeedBroadcaster::redisSourceDriver())->toBeNull();

        return;
    }

    expect(LiveFeedBroadcaster::redisSourceDriver())->toBe('queue');
});
