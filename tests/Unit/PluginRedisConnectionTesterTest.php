<?php

use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnectionTester;

it('reports missing queue redis configuration', function () {
    config(['queue.connections.redis' => ['driver' => 'database']]);

    $result = app(PluginRedisConnectionTester::class)->test();

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('queue.connections.redis');
});

it('tests queue redis when available', function () {
    if (! is_array(config('queue.connections.redis')) || (config('queue.connections.redis.driver') ?? null) !== 'redis') {
        $this->markTestSkipped('Requires queue.connections.redis.');
    }

    $result = app(PluginRedisConnectionTester::class)->test();

    if (! $result['ok']) {
        $this->markTestSkipped($result['message']);
    }

    expect($result)->toHaveKeys(['ok', 'message', 'client', 'redis_connection', 'latency_ms'])
        ->and($result['client'])->toBeIn(['phpredis', 'predis']);
});
