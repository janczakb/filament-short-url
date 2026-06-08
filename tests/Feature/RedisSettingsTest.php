<?php

use Bjanczak\FilamentShortUrl\Services\Queue\PluginQueueWorkerTester;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

if (! function_exists('mgr')) {
    function mgr(): ShortUrlSettingsManager
    {
        return app(ShortUrlSettingsManager::class);
    }
}

it('reports sync queue worker test as immediately ok', function () {
    $result = app(PluginQueueWorkerTester::class)->test([
        'queue_connection' => 'sync',
        'queue_name' => 'default',
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['message'])->toContain('sync');
});

it('overrides database redis config from settings when queue is redis', function () {
    cache()->forget('filament-short-url:settings');
    app()->forgetInstance(ShortUrlSettingsManager::class);

    mgr()->set([
        'queue_connection' => 'redis',
        'queue_name' => 'short-url',
        'redis_host' => '10.0.0.5',
        'redis_port' => 6380,
        'redis_password' => 'secret',
        'redis_database' => 2,
        'redis_key_prefix' => 'fsu:',
    ]);

    expect(config('database.redis.default.host'))->toBe('10.0.0.5')
        ->and(config('database.redis.default.port'))->toBe(6380)
        ->and(config('database.redis.default.password'))->toBe('secret')
        ->and(config('database.redis.default.database'))->toBe(2)
        ->and(config('database.redis.options.prefix'))->toBe('fsu:')
        ->and(config('queue.connections.redis.driver'))->toBe('redis')
        ->and(config('queue.connections.redis.queue'))->toBe('short-url');
});

it('preserves redis password when form submits masked placeholder', function () {
    cache()->forget('filament-short-url:settings');
    app()->forgetInstance(ShortUrlSettingsManager::class);

    mgr()->set([
        'queue_connection' => 'redis',
        'redis_host' => '127.0.0.1',
        'redis_port' => 6379,
        'redis_password' => 'keep-me',
        'redis_database' => 0,
    ]);

    mgr()->set([
        'queue_connection' => 'redis',
        'redis_host' => '127.0.0.1',
        'redis_port' => 6379,
        'redis_password' => '••••••••••••••••••••',
        'redis_database' => 0,
    ]);

    expect(mgr()->get('redis_password'))->toBe('keep-me');
});
