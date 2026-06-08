<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnection;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsScalingProfile;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Redis\Connection as RedisConnectionContract;

/**
 * Buffered visit counters — dedicated Redis path (Settings queue=redis) or Laravel cache (sync + toggle).
 */
class VisitCounterBuffer
{
    public function __construct(
        private readonly StatsScalingProfile $profile,
        private readonly PluginRedisConnection $pluginRedis,
    ) {}

    public function usesDedicatedRedis(): bool
    {
        return $this->profile->usesDedicatedRedisCounters();
    }

    public function isEnabled(): bool
    {
        if ($this->usesDedicatedRedis()) {
            return true;
        }

        return (bool) config('filament-short-url.counter_buffering.enabled', false);
    }

    public function keyPrefix(): string
    {
        return (string) config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
    }

    public function dirtyIdsKey(): string
    {
        return $this->keyPrefix().'dirty_ids';
    }

    public function totalKey(int $shortUrlId): string
    {
        return $this->keyPrefix()."total:{$shortUrlId}";
    }

    public function uniqueKey(int $shortUrlId): string
    {
        return $this->keyPrefix()."unique:{$shortUrlId}";
    }

    public function qrKey(int $shortUrlId): string
    {
        return $this->keyPrefix()."qr:{$shortUrlId}";
    }

    public function uniqueVisitKey(int $shortUrlId, string $ipHash): string
    {
        return "filament-short-url:unique-visit:{$shortUrlId}:{$ipHash}";
    }

    /**
     * Atomically reserve first-seen IP for unique counting (Redis SET NX or cache add).
     */
    public function tryReserveUniqueVisit(int $shortUrlId, string $ipHash): bool
    {
        $logicalKey = $this->uniqueVisitKey($shortUrlId, $ipHash);

        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                return false;
            }

            $result = $connection->set($this->pluginRedis->key($logicalKey), '1', 'EX', 86400, 'NX');

            if ($result === true || $result === 'OK' || $result === 1) {
                return true;
            }

            return false;
        }

        return cache()->add($logicalKey, true, 86400);
    }

    public function increment(int $shortUrlId, bool $isUnique, bool $isQrScan, bool $incrementTotal = true): void
    {
        if ($this->usesDedicatedRedis()) {
            $this->incrementViaDedicatedRedis($shortUrlId, $isUnique, $isQrScan, $incrementTotal);

            return;
        }

        $this->incrementViaCache($shortUrlId, $isUnique, $isQrScan, $incrementTotal);
    }

    public function revertIncrement(int $shortUrlId, bool $isUnique, bool $isQrScan, bool $revertTotal = true): void
    {
        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                return;
            }

            if ($revertTotal) {
                $connection->decr($this->pluginRedis->key($this->totalKey($shortUrlId)));
            }

            if ($isUnique) {
                $connection->decr($this->pluginRedis->key($this->uniqueKey($shortUrlId)));
            }

            if ($isQrScan) {
                $connection->decr($this->pluginRedis->key($this->qrKey($shortUrlId)));
            }

            return;
        }

        if ($revertTotal) {
            cache()->decrement($this->totalKey($shortUrlId));
        }

        if ($isUnique) {
            cache()->decrement($this->uniqueKey($shortUrlId));
        }

        if ($isQrScan) {
            cache()->decrement($this->qrKey($shortUrlId));
        }
    }

    public function getBufferedTotal(int $shortUrlId): int
    {
        return $this->getBufferedValue($shortUrlId, 'total');
    }

    public function getBufferedUnique(int $shortUrlId): int
    {
        return $this->getBufferedValue($shortUrlId, 'unique');
    }

    public function getBufferedQr(int $shortUrlId): int
    {
        return $this->getBufferedValue($shortUrlId, 'qr');
    }

    /**
     * @param  list<int>  $shortUrlIds
     * @return array<int, int>
     */
    public function manyBufferedTotals(array $shortUrlIds): array
    {
        return $this->manyBuffered($shortUrlIds, 'total');
    }

    /**
     * @param  list<int>  $shortUrlIds
     * @return array<int, int>
     */
    public function manyBufferedUniques(array $shortUrlIds): array
    {
        return $this->manyBuffered($shortUrlIds, 'unique');
    }

    /**
     * @param  list<int>  $shortUrlIds
     * @return array<int, int>
     */
    public function manyBufferedQr(array $shortUrlIds): array
    {
        return $this->manyBuffered($shortUrlIds, 'qr');
    }

    /**
     * @return list<int>
     */
    public function listDirtyIds(): array
    {
        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                return [];
            }

            $raw = $connection->smembers($this->pluginRedis->key($this->dirtyIdsKey()));

            return $this->normalizeDirtyIds($raw);
        }

        $store = cache()->store()->getStore();

        if ($store instanceof RedisStore) {
            try {
                $raw = $store->connection()->smembers($store->getPrefix().$this->dirtyIdsKey());

                return $this->normalizeDirtyIds($raw);
            } catch (\Throwable) {
                return [];
            }
        }

        $dirtyIds = cache()->get($this->dirtyIdsKey(), []);

        return is_array($dirtyIds) ? $this->normalizeDirtyIds($dirtyIds) : [];
    }

    /**
     * Atomically drain dirty IDs for sync (Redis RENAME+SPOP or cache pull).
     *
     * @return array{ids: list<int>, requeue: bool, requeueKey: ?string, connection: ?RedisConnectionContract, prefixedDirtyKey: ?string}
     */
    public function pullDirtyIdsForSync(): array
    {
        if ($this->usesDedicatedRedis()) {
            return $this->pullDirtyIdsFromDedicatedRedis();
        }

        $store = cache()->store()->getStore();

        if ($store instanceof RedisStore) {
            $conn = $store->connection();
            $dirtyKey = $this->dirtyIdsKey();
            $prefixedDirtyKey = $store->getPrefix().$dirtyKey;
            $tempKey = "{$prefixedDirtyKey}:temp:".time();

            try {
                if ($conn->exists($prefixedDirtyKey)) {
                    $conn->rename($prefixedDirtyKey, $tempKey);
                    $rawIds = $this->spopAll($conn, $tempKey);

                    return [
                        'ids' => $this->normalizeDirtyIds($rawIds),
                        'requeue' => true,
                        'requeueKey' => $tempKey,
                        'connection' => $conn,
                        'prefixedDirtyKey' => $prefixedDirtyKey,
                    ];
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        $dirtyIds = cache()->pull($this->dirtyIdsKey(), []);

        return [
            'ids' => is_array($dirtyIds) ? $this->normalizeDirtyIds($dirtyIds) : [],
            'requeue' => false,
            'requeueKey' => null,
            'connection' => null,
            'prefixedDirtyKey' => null,
        ];
    }

    /**
     * @return array{total: int, unique: int, qr: int}
     */
    public function pullDeltas(int $shortUrlId): array
    {
        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                return ['total' => 0, 'unique' => 0, 'qr' => 0];
            }

            $totalKey = $this->pluginRedis->key($this->totalKey($shortUrlId));
            $uniqueKey = $this->pluginRedis->key($this->uniqueKey($shortUrlId));
            $qrKey = $this->pluginRedis->key($this->qrKey($shortUrlId));

            $total = (int) ($connection->get($totalKey) ?: 0);
            $unique = (int) ($connection->get($uniqueKey) ?: 0);
            $qr = (int) ($connection->get($qrKey) ?: 0);

            if ($total > 0) {
                $connection->del($totalKey);
            }

            if ($unique > 0) {
                $connection->del($uniqueKey);
            }

            if ($qr > 0) {
                $connection->del($qrKey);
            }

            return ['total' => $total, 'unique' => $unique, 'qr' => $qr];
        }

        return [
            'total' => (int) cache()->pull($this->totalKey($shortUrlId), 0),
            'unique' => (int) cache()->pull($this->uniqueKey($shortUrlId), 0),
            'qr' => (int) cache()->pull($this->qrKey($shortUrlId), 0),
        ];
    }

    /**
     * @param  array<int, array{total: int, unique: int, qr: int}>  $updatesToMake
     */
    public function restoreDeltasAfterFailedSync(array $updatesToMake): void
    {
        foreach ($updatesToMake as $id => $deltas) {
            if ($deltas['total'] > 0) {
                $this->incrementCounterKey($this->totalKey((int) $id), $deltas['total']);
            }
            if ($deltas['unique'] > 0) {
                $this->incrementCounterKey($this->uniqueKey((int) $id), $deltas['unique']);
            }
            if ($deltas['qr'] > 0) {
                $this->incrementCounterKey($this->qrKey((int) $id), $deltas['qr']);
            }
        }
    }

    /**
     * @param  list<int>  $ids
     */
    public function restoreDirtyIds(array $ids, ?RedisConnectionContract $connection = null, ?string $prefixedDirtyKey = null, ?string $requeueTempKey = null): void
    {
        if ($ids === []) {
            return;
        }

        if ($this->usesDedicatedRedis()) {
            $connection ??= $this->pluginRedis->connection();
            $prefixedDirtyKey ??= $this->pluginRedis->key($this->dirtyIdsKey());

            if ($connection === null) {
                return;
            }

            $connection->sadd($prefixedDirtyKey, ...$ids);

            if ($requeueTempKey && $connection->exists($requeueTempKey)) {
                $remaining = $connection->smembers($requeueTempKey);
                if (! empty($remaining)) {
                    $connection->sadd($prefixedDirtyKey, ...$remaining);
                }
            }

            return;
        }

        $prefix = $this->keyPrefix();
        $lock = cache()->lock("{$prefix}dirty_ids_lock", 2);
        $lock->get(function () use ($ids): void {
            $cachedDirty = cache()->get($this->dirtyIdsKey(), []);
            if (! is_array($cachedDirty)) {
                $cachedDirty = [];
            }
            $merged = array_unique(array_merge($cachedDirty, $ids));
            cache()->forever($this->dirtyIdsKey(), $merged);
        });
    }

    public function registerDirtyId(int $shortUrlId): bool
    {
        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                return false;
            }

            $connection->sadd($this->pluginRedis->key($this->dirtyIdsKey()), $shortUrlId);

            return true;
        }

        $store = cache()->store()->getStore();

        if ($store instanceof RedisStore) {
            try {
                $store->connection()->sadd($store->getPrefix().$this->dirtyIdsKey(), $shortUrlId);

                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        $lock = cache()->lock($this->keyPrefix().'dirty_ids_lock', 2);

        $registered = $lock->get(function () use ($shortUrlId): bool {
            $dirtyIds = cache()->get($this->dirtyIdsKey(), []);
            if (! is_array($dirtyIds)) {
                $dirtyIds = [];
            }

            if (count($dirtyIds) >= 50000) {
                return false;
            }

            if (! in_array($shortUrlId, $dirtyIds, true)) {
                $dirtyIds[] = $shortUrlId;
                cache()->forever($this->dirtyIdsKey(), $dirtyIds);
            }

            return true;
        });

        return $registered !== null && $registered !== false;
    }

    private function incrementViaDedicatedRedis(int $shortUrlId, bool $isUnique, bool $isQrScan, bool $incrementTotal): void
    {
        $connection = $this->pluginRedis->connection();

        if ($connection === null) {
            throw new \RuntimeException('Dedicated Redis connection unavailable.');
        }

        $connection->pipeline(function ($pipe) use ($shortUrlId, $isUnique, $isQrScan, $incrementTotal): void {
            if ($incrementTotal) {
                $pipe->incr($this->pluginRedis->key($this->totalKey($shortUrlId)));
            }

            if ($isUnique) {
                $pipe->incr($this->pluginRedis->key($this->uniqueKey($shortUrlId)));
            }

            if ($isQrScan) {
                $pipe->incr($this->pluginRedis->key($this->qrKey($shortUrlId)));
            }

            $pipe->sadd($this->pluginRedis->key($this->dirtyIdsKey()), $shortUrlId);
        });
    }

    private function incrementViaCache(int $shortUrlId, bool $isUnique, bool $isQrScan, bool $incrementTotal): void
    {
        if ($incrementTotal) {
            cache()->increment($this->totalKey($shortUrlId));
        }

        if ($isUnique) {
            cache()->increment($this->uniqueKey($shortUrlId));
        }

        if ($isQrScan) {
            cache()->increment($this->qrKey($shortUrlId));
        }

        if (! $this->registerDirtyId($shortUrlId)) {
            throw new \RuntimeException('Failed to register dirty buffered counter id.');
        }
    }

    private function getBufferedValue(int $shortUrlId, string $type): int
    {
        $key = match ($type) {
            'unique' => $this->uniqueKey($shortUrlId),
            'qr' => $this->qrKey($shortUrlId),
            default => $this->totalKey($shortUrlId),
        };

        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                return 0;
            }

            return (int) ($connection->get($this->pluginRedis->key($key)) ?: 0);
        }

        return (int) cache()->get($key, 0);
    }

    /**
     * @param  list<int>  $shortUrlIds
     * @return array<int, int>
     */
    private function manyBuffered(array $shortUrlIds, string $type): array
    {
        $result = [];

        if ($shortUrlIds === []) {
            return $result;
        }

        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                foreach ($shortUrlIds as $id) {
                    $result[(int) $id] = 0;
                }

                return $result;
            }

            $keys = [];
            foreach ($shortUrlIds as $id) {
                $id = (int) $id;
                $logical = match ($type) {
                    'unique' => $this->uniqueKey($id),
                    'qr' => $this->qrKey($id),
                    default => $this->totalKey($id),
                };
                $keys[$id] = $this->pluginRedis->key($logical);
            }

            $values = $connection->mget(array_values($keys));

            $i = 0;
            foreach ($keys as $id => $redisKey) {
                $result[$id] = (int) ($values[$i] ?? 0);
                $i++;
            }

            return $result;
        }

        $logicalKeys = [];
        foreach ($shortUrlIds as $id) {
            $id = (int) $id;
            $logicalKeys[$id] = match ($type) {
                'unique' => $this->uniqueKey($id),
                'qr' => $this->qrKey($id),
                default => $this->totalKey($id),
            };
        }

        try {
            $values = cache()->many(array_values($logicalKeys));
            foreach ($logicalKeys as $id => $logicalKey) {
                $result[$id] = (int) ($values[$logicalKey] ?? 0);
            }
        } catch (\Throwable) {
            foreach ($shortUrlIds as $id) {
                $result[(int) $id] = 0;
            }
        }

        return $result;
    }

    private function incrementCounterKey(string $logicalKey, int $delta): void
    {
        if ($this->usesDedicatedRedis()) {
            $connection = $this->pluginRedis->connection();

            if ($connection === null) {
                return;
            }

            $connection->incrby($this->pluginRedis->key($logicalKey), $delta);

            return;
        }

        cache()->increment($logicalKey, $delta);
    }

    /**
     * @return array{ids: list<int>, requeue: bool, requeueKey: ?string, connection: ?RedisConnectionContract, prefixedDirtyKey: ?string}
     */
    private function pullDirtyIdsFromDedicatedRedis(): array
    {
        $connection = $this->pluginRedis->connection();

        if ($connection === null) {
            return [
                'ids' => [],
                'requeue' => false,
                'requeueKey' => null,
                'connection' => null,
                'prefixedDirtyKey' => null,
            ];
        }

        $prefixedDirtyKey = $this->pluginRedis->key($this->dirtyIdsKey());
        $tempKey = "{$prefixedDirtyKey}:temp:".time();

        try {
            if ($connection->exists($prefixedDirtyKey)) {
                $connection->rename($prefixedDirtyKey, $tempKey);
                $rawIds = $this->spopAll($connection, $tempKey);

                return [
                    'ids' => $this->normalizeDirtyIds($rawIds),
                    'requeue' => true,
                    'requeueKey' => $tempKey,
                    'connection' => $connection,
                    'prefixedDirtyKey' => $prefixedDirtyKey,
                ];
            }
        } catch (\Throwable) {
            // fall through
        }

        return [
            'ids' => [],
            'requeue' => false,
            'requeueKey' => null,
            'connection' => $connection,
            'prefixedDirtyKey' => $prefixedDirtyKey,
        ];
    }

    /**
     * @return list<int|string>
     */
    private function spopAll(RedisConnectionContract $connection, string $tempKey): array
    {
        $rawIds = [];

        do {
            $batch = $connection->spop($tempKey, 1000);
            if (is_array($batch) && ! empty($batch)) {
                array_push($rawIds, ...$batch);
            } elseif (is_string($batch) && $batch !== '') {
                $rawIds[] = $batch;
            } else {
                break;
            }
        } while (true);

        return $rawIds;
    }

    /**
     * @param  array<int|string>|list<int|string>  $raw
     * @return list<int>
     */
    private function normalizeDirtyIds(array $raw): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $raw))));
    }
}
