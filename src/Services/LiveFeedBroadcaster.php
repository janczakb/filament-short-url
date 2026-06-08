<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Illuminate\Cache\RedisStore;
use Illuminate\Redis\Connections\PhpRedisConnection;

/**
 * Publishes live-feed cursor updates on visit insert so SSE streams avoid DB polling.
 *
 * Redis source priority (first match wins):
 * 1. Short URL Settings → Queue Connection = redis (plugin config)
 * 2. Laravel cache store = redis (CACHE_STORE)
 *
 * When Redis + PhpRedis are available, SSE uses pub/sub push; otherwise SSE polls the cursor.
 */
class LiveFeedBroadcaster
{
    public static function cursorCacheKey(int $shortUrlId): string
    {
        return "fsu:live-feed:cursor:{$shortUrlId}";
    }

    public static function channelName(int $shortUrlId): string
    {
        return "fsu:live-feed:channel:{$shortUrlId}";
    }

    /**
     * @return array{connection: RedisConnectionContract, prefix: string}|null
     */
    public static function resolveRedisSource(): ?array
    {
        $pluginRedis = app(PluginRedisConnection::class);

        if ($pluginRedis->isAvailable()) {
            $resolved = $pluginRedis->resolve();

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return self::redisSourceFromCacheStore();
    }

    /**
     * Plugin Settings → General → Queue Connection = redis (wins over CACHE_STORE).
     *
     * @return array{connection: RedisConnectionContract, prefix: string}|null
     */
    public static function redisSourceFromPluginQueueSettings(): ?array
    {
        $pluginRedis = app(PluginRedisConnection::class);

        if (! $pluginRedis->isQueueMode() || ! $pluginRedis->isAvailable()) {
            return null;
        }

        return $pluginRedis->resolve();
    }

    /**
     * @return array{connection: RedisConnectionContract, prefix: string}|null
     */
    public static function redisSourceFromCacheStore(): ?array
    {
        try {
            $store = cache()->store()->getStore();

            if (! $store instanceof RedisStore) {
                return null;
            }

            return [
                'connection' => $store->connection(),
                'prefix' => $store->getPrefix(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public static function redisStore(): ?RedisStore
    {
        try {
            $store = cache()->store()->getStore();

            return $store instanceof RedisStore ? $store : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whether SSE should use Redis pub/sub push (requires Redis + PhpRedis).
     */
    public static function usesRedisPush(): bool
    {
        if (! config('filament-short-url.live_feed.use_redis_push', true)) {
            return false;
        }

        $source = self::resolveRedisSource();

        if ($source === null) {
            return false;
        }

        return $source['connection'] instanceof PhpRedisConnection;
    }

    /**
     * Which Redis source is active for the live feed (for diagnostics / UI).
     */
    public static function redisSourceDriver(): ?string
    {
        if (self::redisSourceFromPluginQueueSettings() !== null) {
            return 'queue';
        }

        if (self::redisSourceFromCacheStore() !== null) {
            return 'cache';
        }

        return null;
    }

    public static function publish(int $shortUrlId, int $visitId): void
    {
        if ($visitId <= 0) {
            return;
        }

        try {
            if ($source = self::resolveRedisSource()) {
                $cursorKey = $source['prefix'].self::cursorCacheKey($shortUrlId);
                $channel = $source['prefix'].self::channelName($shortUrlId);

                $source['connection']->setex($cursorKey, 86400, (string) $visitId);
                $source['connection']->publish($channel, (string) $visitId);
            } else {
                cache()->put(self::cursorCacheKey($shortUrlId), $visitId, 86400);
            }
        } catch (\Throwable) {
            // Never block visit persistence on live-feed publishing failures.
        }
    }

    public static function latestId(int $shortUrlId): int
    {
        try {
            if ($source = self::resolveRedisSource()) {
                $value = $source['connection']->get($source['prefix'].self::cursorCacheKey($shortUrlId));

                return (int) ($value ?? 0);
            }

            return (int) cache()->get(self::cursorCacheKey($shortUrlId), 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Block until a visit is published on the link channel or the timeout elapses.
     *
     * Returns the visit id on push, or null on timeout / unsupported Redis client.
     */
    public static function waitForPublish(int $shortUrlId, int $timeoutSeconds): ?int
    {
        $source = self::resolveRedisSource();

        if ($source === null || $timeoutSeconds < 1) {
            return null;
        }

        $connection = $source['connection'];
        $channel = $source['prefix'].self::channelName($shortUrlId);

        if ($connection instanceof PhpRedisConnection) {
            return self::waitForPublishPhpRedis($connection, $channel, $timeoutSeconds);
        }

        return null;
    }

    private static function waitForPublishPhpRedis(
        PhpRedisConnection $connection,
        string $channel,
        int $timeoutSeconds,
    ): ?int {
        $client = $connection->client();
        $received = null;

        try {
            $client->setOption(\Redis::OPT_READ_TIMEOUT, (float) $timeoutSeconds);

            $client->subscribe([$channel], function (\Redis $redis, string $ch, string $message) use (&$received, $channel): void {
                $received = (int) $message;
                $redis->unsubscribe([$channel]);
            });
        } catch (\RedisException) {
            try {
                $client->unsubscribe([$channel]);
            } catch (\Throwable) {
            }
        } finally {
            $client->setOption(\Redis::OPT_READ_TIMEOUT, -1.0);
        }

        return ($received !== null && $received > 0) ? $received : null;
    }
}
