<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services\Redis;

use Illuminate\Contracts\Redis\Connection as RedisConnectionContract;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Redis;

/**
 * Dedicated Redis connection for plugin hot paths when Settings → Queue Connection = redis.
 *
 * Uses queue.connections.redis (phpredis or Predis via Laravel) — independent of CACHE_STORE.
 */
class PluginRedisConnection
{
    /** @var array{connection: RedisConnectionContract, prefix: string}|null */
    private ?array $resolved = null;

    private bool $attempted = false;

    public function isQueueMode(): bool
    {
        return (string) config('filament-short-url.queue_connection', 'sync') === 'redis';
    }

    public function isAvailable(): bool
    {
        return $this->resolve() !== null;
    }

    /**
     * @return array{connection: RedisConnectionContract, prefix: string}|null
     */
    public function resolve(): ?array
    {
        if ($this->attempted) {
            return $this->resolved;
        }

        $this->attempted = true;

        if (! $this->isQueueMode()) {
            return null;
        }

        $queueConfig = config('queue.connections.redis');

        if (! is_array($queueConfig) || ($queueConfig['driver'] ?? null) !== 'redis') {
            return null;
        }

        try {
            $connection = Redis::connection($queueConfig['connection'] ?? 'default');

            $this->resolved = [
                'connection' => $connection,
                'prefix' => $this->detectKeyPrefix($connection),
            ];
        } catch (\Throwable) {
            $this->resolved = null;
        }

        return $this->resolved;
    }

    public function connection(): ?RedisConnectionContract
    {
        return $this->resolve()['connection'] ?? null;
    }

    public function prefix(): string
    {
        return $this->resolve()['prefix'] ?? '';
    }

    public function key(string $suffix): string
    {
        return $this->prefix().$suffix;
    }

    private function detectKeyPrefix(RedisConnectionContract $connection): string
    {
        if ($connection instanceof PhpRedisConnection) {
            $options = $connection->client()->getOption(\Redis::OPT_PREFIX);

            return is_string($options) ? $options : '';
        }

        return (string) config('database.redis.options.prefix', '');
    }
}
