<?php

namespace Bjanczak\FilamentShortUrl\Services\Stats;

/**
 * Cache helper that works consistently across file, database, Redis, Memcached, and array stores.
 */
class StatsCacheHelper
{
    public static function ttl(): int
    {
        return (int) config('filament-short-url.geo_ip.stats_cache_ttl', 300);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl ??= self::ttl();

        try {
            $cached = cache()->get($key);

            if ($cached !== null) {
                return $cached;
            }

            $lock = cache()->lock("{$key}:builder", 10);

            return $lock->block(5, function () use ($key, $callback, $ttl) {
                $cached = cache()->get($key);

                if ($cached !== null) {
                    return $cached;
                }

                $value = $callback();
                cache()->put($key, $value, $ttl);

                return $value;
            });
        } catch (\Throwable) {
            return $callback();
        }
    }

    public static function forget(string $key): void
    {
        try {
            cache()->forget($key);
        } catch (\Throwable) {
            // Ignore cache driver failures during invalidation.
        }
    }
}
