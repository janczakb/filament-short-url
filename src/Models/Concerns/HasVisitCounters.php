<?php

namespace Bjanczak\FilamentShortUrl\Models\Concerns;

use Bjanczak\FilamentShortUrl\Jobs\IncrementVisitJob;
use Bjanczak\FilamentShortUrl\Services\VisitCounterBuffer;
use Illuminate\Support\Facades\DB;

trait HasVisitCounters
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected static ?array $bufferedTotalVisits = null;

    protected static ?array $bufferedUniqueVisits = null;

    protected static ?array $bufferedQrScans = null;

    /**
     * Get the real-time total visits count.
     */
    public function getRealTimeTotalVisits(): int
    {
        $buffer = app(VisitCounterBuffer::class);

        if ($buffer->isEnabled()) {
            $dbValue = (int) ($this->attributes['total_visits'] ?? 0);

            try {
                return $dbValue + $buffer->getBufferedTotal((int) $this->id);
            } catch (\Throwable) {
                return $dbValue;
            }
        }

        $cacheKey = "filament-short-url:visits:{$this->id}";
        $cacheTtl = (int) config('filament-short-url.cache_ttl', 3600);

        return (int) cache()->remember($cacheKey, $cacheTtl, fn () => (int) ($this->attributes['total_visits'] ?? 0));
    }

    /**
     * Preload buffered counters for many links in one cache round-trip (list tables).
     *
     * @param  iterable<int|string|null>  $ids
     */
    public static function preloadBufferedCountersForIds(iterable $ids): void
    {
        static::loadBufferedForIds(is_array($ids) ? $ids : iterator_to_array($ids));
    }

    /**
     * Atomically increment visit counters — single query when unique,
     * to avoid race conditions and two round-trips. Supports write-back caching.
     */
    public function incrementVisits(bool $isUnique = false, bool $isQrScan = false, bool $incrementTotal = true): void
    {
        if (! $incrementTotal) {
            $this->incrementUniqueVisitOnly($isUnique);

            return;
        }

        $buffer = app(VisitCounterBuffer::class);

        if ($buffer->isEnabled()) {
            try {
                $buffer->increment((int) $this->id, $isUnique, $isQrScan, true);

                return;
            } catch (\Throwable) {
                if (! $buffer->usesDedicatedRedis()) {
                    try {
                        $buffer->revertIncrement((int) $this->id, $isUnique, $isQrScan, true);
                    } catch (\Throwable) {
                        // Best-effort revert before DB fallback.
                    }

                    $this->fallbackIncrementVisits($isUnique, $isQrScan);

                    return;
                }

                $connection = config('filament-short-url.queue_connection', 'sync');
                dispatch((new IncrementVisitJob($this->id, $isUnique, $isQrScan))->onConnection($connection ?: 'sync'));

                return;
            }
        }

        $updates = [];
        if ($isUnique) {
            $updates['unique_visits'] = DB::raw('unique_visits + 1');
        }
        if ($isQrScan) {
            $updates['qr_scans'] = DB::raw('qr_scans + 1');
        }

        $this->newQuery()
            ->where('id', $this->id)
            ->increment('total_visits', 1, $updates);

        $cacheKey = "filament-short-url:visits:{$this->id}";
        try {
            if (! cache()->add($cacheKey, $this->total_visits + 1, 3600)) {
                cache()->increment($cacheKey);
            }
        } catch (\Throwable) {
            // Never let cache errors disrupt redirection or DB counters
        }
    }

    private function incrementUniqueVisitOnly(bool $isUnique): void
    {
        if (! $isUnique) {
            return;
        }

        $buffer = app(VisitCounterBuffer::class);

        if ($buffer->isEnabled()) {
            try {
                $buffer->increment((int) $this->id, true, false, false);

                return;
            } catch (\Throwable) {
                $this->newQuery()
                    ->where('id', $this->id)
                    ->increment('unique_visits');

                return;
            }
        }

        $this->newQuery()
            ->where('id', $this->id)
            ->increment('unique_visits');
    }

    public function touchVisitCountCache(int $delta): void
    {
        if (app(VisitCounterBuffer::class)->isEnabled()) {
            return;
        }

        $cacheKey = "filament-short-url:visits:{$this->id}";
        $cacheTtl = (int) config('filament-short-url.cache_ttl', 3600);

        try {
            if (! cache()->add($cacheKey, ((int) ($this->attributes['total_visits'] ?? 0)), $cacheTtl)) {
                cache()->increment($cacheKey, $delta);
            }
        } catch (\Throwable) {
            // Ignore cache failures on the redirect hot path.
        }
    }

    /**
     * Persist buffered increments directly when dirty-ID registration fails after cache increment.
     */
    private function fallbackIncrementVisits(bool $isUnique, bool $isQrScan): void
    {
        $updates = [];
        if ($isUnique) {
            $updates['unique_visits'] = DB::raw('unique_visits + 1');
        }
        if ($isQrScan) {
            $updates['qr_scans'] = DB::raw('qr_scans + 1');
        }

        $this->newQuery()
            ->where('id', $this->id)
            ->increment('total_visits', 1, $updates);
    }

    /**
     * Clear in-request buffered counter preload state (Octane/long-lived workers).
     */
    public static function flushBufferedCounterMemory(): void
    {
        static::$bufferedTotalVisits = null;
        static::$bufferedUniqueVisits = null;
        static::$bufferedQrScans = null;
    }

    /**
     * Preload buffered click counters for selected IDs in a single batch query.
     */
    protected static function loadBufferedForIds(array $ids): void
    {
        $buffer = app(VisitCounterBuffer::class);

        if (! $buffer->isEnabled()) {
            return;
        }

        if (static::$bufferedTotalVisits === null) {
            static::$bufferedTotalVisits = [];
            static::$bufferedUniqueVisits = [];
            static::$bufferedQrScans = [];
        }

        $ids = array_values(array_unique(array_filter($ids, fn ($id): bool => $id !== null && $id !== '')));
        if (empty($ids)) {
            return;
        }

        $ids = array_values(array_filter(
            $ids,
            fn ($id): bool => ! array_key_exists((int) $id, static::$bufferedTotalVisits),
        ));

        if ($ids === []) {
            return;
        }

        try {
            $totals = $buffer->manyBufferedTotals(array_map('intval', $ids));
            $uniques = $buffer->manyBufferedUniques(array_map('intval', $ids));
            $qrs = $buffer->manyBufferedQr(array_map('intval', $ids));

            foreach ($ids as $id) {
                $id = (int) $id;
                static::$bufferedTotalVisits[$id] = (int) ($totals[$id] ?? 0);
                static::$bufferedUniqueVisits[$id] = (int) ($uniques[$id] ?? 0);
                static::$bufferedQrScans[$id] = (int) ($qrs[$id] ?? 0);
            }
        } catch (\Throwable) {
            foreach ($ids as $id) {
                $id = (int) $id;
                static::$bufferedTotalVisits[$id] = static::$bufferedTotalVisits[$id] ?? 0;
                static::$bufferedUniqueVisits[$id] = static::$bufferedUniqueVisits[$id] ?? 0;
                static::$bufferedQrScans[$id] = static::$bufferedQrScans[$id] ?? 0;
            }
        }
    }

    /**
     * Get the total visits count, merging the database value with any buffered clicks in cache.
     */
    public function getTotalVisitsAttribute(): int
    {
        $dbValue = $this->attributes['total_visits'] ?? 0;

        if (! app(VisitCounterBuffer::class)->isEnabled()) {
            return $dbValue;
        }

        static::loadBufferedForIds([$this->id]);

        $buffered = static::$bufferedTotalVisits[$this->id] ?? 0;

        return $dbValue + $buffered;
    }

    /**
     * Get the unique visits count, merging the database value with any buffered clicks in cache.
     */
    public function getUniqueVisitsAttribute(): int
    {
        $dbValue = $this->attributes['unique_visits'] ?? 0;

        if (! app(VisitCounterBuffer::class)->isEnabled()) {
            return $dbValue;
        }

        static::loadBufferedForIds([$this->id]);

        $buffered = static::$bufferedUniqueVisits[$this->id] ?? 0;

        return $dbValue + $buffered;
    }

    /**
     * Get the QR scans count, merging the database value with any buffered clicks in cache.
     */
    public function getQrScansAttribute(): int
    {
        $dbValue = $this->attributes['qr_scans'] ?? 0;

        if (! app(VisitCounterBuffer::class)->isEnabled()) {
            return $dbValue;
        }

        static::loadBufferedForIds([$this->id]);

        $buffered = static::$bufferedQrScans[$this->id] ?? 0;

        return $dbValue + $buffered;
    }
}
