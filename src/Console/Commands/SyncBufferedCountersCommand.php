<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SyncBufferedCountersCommand extends Command
{
    /** @var string */
    protected $signature = 'short-url:sync-counters';

    /** @var string */
    protected $description = 'Sync buffered short URL visit counters from cache to the database';

    public function handle(): int
    {
        $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
        $dirtyKey = "{$prefix}dirty_ids";

        // Pull the list atomically to avoid race conditions with incoming clicks
        if (Cache::getDefaultDriver() === 'redis' && class_exists(Redis::class)) {
            $tempKey = "{$dirtyKey}:temp:".time();
            try {
                if (Redis::exists($dirtyKey)) {
                    Redis::rename($dirtyKey, $tempKey);
                    $dirtyIds = Redis::smembers($tempKey);
                    Redis::del($tempKey);
                } else {
                    $dirtyIds = [];
                }
            } catch (\Throwable) {
                // If key does not exist or rename fails, fallback
                $dirtyIds = [];
            }
        } else {
            $dirtyIds = Cache::pull($dirtyKey, []);
        }

        if (empty($dirtyIds)) {
            $this->info('No buffered counters to synchronize.');

            return 0;
        }

        $dirtyIds = array_unique(array_filter($dirtyIds));
        $processed = 0;
        $updatesToMake = [];

        foreach ($dirtyIds as $id) {
            $totalKey = "{$prefix}total:{$id}";
            $uniqueKey = "{$prefix}unique:{$id}";
            $qrKey = "{$prefix}qr:{$id}";

            $totalDelta = (int) Cache::pull($totalKey, 0);
            $uniqueDelta = (int) Cache::pull($uniqueKey, 0);
            $qrDelta = (int) Cache::pull($qrKey, 0);

            if ($totalDelta > 0 || $uniqueDelta > 0 || $qrDelta > 0) {
                $updatesToMake[$id] = [
                    'total' => $totalDelta,
                    'unique' => $uniqueDelta,
                    'qr' => $qrDelta,
                ];
            }
        }

        if (empty($updatesToMake)) {
            $this->info('No buffered counters to synchronize.');

            return 0;
        }

        try {
            DB::transaction(function () use ($updatesToMake, &$processed) {
                $shortUrls = ShortUrl::whereIn('id', array_keys($updatesToMake))->get(['id', 'url_key']);

                foreach ($updatesToMake as $id => $deltas) {
                    ShortUrl::where('id', $id)->update([
                        'total_visits' => DB::raw("total_visits + {$deltas['total']}"),
                        'unique_visits' => DB::raw("unique_visits + {$deltas['unique']}"),
                        'qr_scans' => DB::raw("qr_scans + {$deltas['qr']}"),
                    ]);
                    $processed++;
                }

                foreach ($shortUrls as $url) {
                    Cache::forget("filament-short-url:{$url->url_key}");
                }
            });
        } catch (\Throwable $e) {
            // Restore pulled values in cache so no clicks are lost
            foreach ($updatesToMake as $id => $deltas) {
                $totalKey = "{$prefix}total:{$id}";
                $uniqueKey = "{$prefix}unique:{$id}";
                $qrKey = "{$prefix}qr:{$id}";

                if ($deltas['total'] > 0) {
                    Cache::increment($totalKey, $deltas['total']);
                }
                if ($deltas['unique'] > 0) {
                    Cache::increment($uniqueKey, $deltas['unique']);
                }
                if ($deltas['qr'] > 0) {
                    Cache::increment($qrKey, $deltas['qr']);
                }
            }

            // Put the IDs back into the dirty list
            if (Cache::getDefaultDriver() === 'redis' && class_exists(Redis::class)) {
                Redis::sadd($dirtyKey, ...array_keys($updatesToMake));
            } else {
                $lock = Cache::lock("{$prefix}dirty_ids_lock", 2);
                $lock->get(function () use ($prefix, $updatesToMake) {
                    $cachedDirty = Cache::get("{$prefix}dirty_ids", []);
                    if (! is_array($cachedDirty)) {
                        $cachedDirty = [];
                    }
                    $merged = array_unique(array_merge($cachedDirty, array_keys($updatesToMake)));
                    Cache::forever("{$prefix}dirty_ids", $merged);
                });
            }

            throw $e;
        }

        $this->info("Successfully synchronized counters for {$processed} short URLs.");

        return 0;
    }
}
