<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services\Queue;

use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Illuminate\Support\Facades\Cache;

class QueueWorkerProbe
{
    public static function key(string $probeId): string
    {
        return "filament-short-url:queue-worker-probe:{$probeId}";
    }

    public static function markProcessed(string $probeId): void
    {
        $pluginRedis = app(PluginRedisConnection::class);

        if ($pluginRedis->isAvailable() && $pluginRedis->connection() !== null) {
            $pluginRedis->connection()->setex(
                $pluginRedis->key(self::key($probeId)),
                120,
                (string) microtime(true),
            );

            return;
        }

        Cache::put(self::key($probeId), microtime(true), 120);
    }

    public static function isProcessed(string $probeId): bool
    {
        $pluginRedis = app(PluginRedisConnection::class);

        if ($pluginRedis->isAvailable() && $pluginRedis->connection() !== null) {
            return (bool) $pluginRedis->connection()->exists($pluginRedis->key(self::key($probeId)));
        }

        return Cache::has(self::key($probeId));
    }

    public static function clear(string $probeId): void
    {
        $pluginRedis = app(PluginRedisConnection::class);

        if ($pluginRedis->isAvailable() && $pluginRedis->connection() !== null) {
            $pluginRedis->connection()->del($pluginRedis->key(self::key($probeId)));

            return;
        }

        Cache::forget(self::key($probeId));
    }
}
