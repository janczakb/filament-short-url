<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services\Stats;

use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;

/**
 * Runtime stats scaling profile derived from Short URL Settings (via config overrides).
 */
class StatsScalingProfile
{
    public function queueConnection(): string
    {
        return (string) config('filament-short-url.queue_connection', 'sync');
    }

    public function isSyncQueue(): bool
    {
        return $this->queueConnection() === 'sync';
    }

    public function isRedisQueue(): bool
    {
        return $this->queueConnection() === 'redis';
    }

    public function statsCacheTtl(): int
    {
        return max(1, (int) config('filament-short-url.geo_ip.stats_cache_ttl', 300));
    }

    /**
     * Micro-cache TTL for today's dimensional SQL rollups (sync / non-Redis buffer paths).
     */
    public function todaySqlMicroCacheTtl(): int
    {
        if ($this->isSyncQueue()) {
            return min(15, $this->statsCacheTtl());
        }

        return min(30, max(15, (int) floor($this->statsCacheTtl() / 2)));
    }

    /**
     * Dedicated Redis counters/stats when Settings → Queue Connection = redis.
     */
    public function usesDedicatedRedisCounters(): bool
    {
        return $this->isRedisQueue() && app(PluginRedisConnection::class)->isAvailable();
    }

    public function cacheStoreSupportsRedis(): bool
    {
        try {
            return Cache::store()->getStore() instanceof RedisStore;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Redis queue → pipelined today/hourly stats on the dedicated queue Redis connection.
     */
    public function usesRedisTodayBuffer(): bool
    {
        return $this->usesDedicatedRedisCounters();
    }

    /**
     * When true, visit jobs must not bust full stats caches — historical blobs stay hot.
     */
    public function usesHistoricalStatsCache(): bool
    {
        return true;
    }

    /**
     * Counter buffering is mandatory for Redis queue throughput.
     */
    public function shouldForceCounterBuffering(): bool
    {
        return $this->isRedisQueue();
    }

    public function counterBufferingEnabled(): bool
    {
        if ($this->shouldForceCounterBuffering()) {
            return true;
        }

        return (bool) config('filament-short-url.counter_buffering.enabled', false);
    }
}
