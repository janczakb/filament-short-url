<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Cache\RedisStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        // Atomically pull the dirty-ID list so incoming increments during this command
        // are written to a fresh list rather than being lost. Strategy is driver-aware:
        // Redis uses RENAME + SMEMBERS (O(N)) for true atomicity; all other stores use
        // Cache::pull() which is atomic on most drivers (file, database, memcached).
        $store = Cache::store()->getStore();
        $isRedis = $store instanceof RedisStore;

        if ($isRedis) {
            $tempKey = "{$dirtyKey}:temp:".time();
            try {
                $conn = $store->connection();
                if ($conn->exists($dirtyKey)) {
                    $conn->rename($dirtyKey, $tempKey);
                    $rawIds = $conn->smembers($tempKey);
                    $conn->del($tempKey);
                    $dirtyIds = $rawIds ?: [];
                } else {
                    $dirtyIds = [];
                }
            } catch (\Throwable) {
                $dirtyIds = [];
            }
        } else {
            $dirtyIds = Cache::pull($dirtyKey, []);
        }

        if (empty($dirtyIds)) {
            $this->info('No buffered counters to synchronize.');

            return 0;
        }

        $dirtyIds = array_unique(array_filter(array_map('intval', $dirtyIds)));
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
                $shortUrls = ShortUrl::whereIn('id', array_keys($updatesToMake))
                    ->with('customDomain')
                    ->get(['id', 'url_key', 'custom_domain_id']);

                $appHost = parse_url(config('app.url'), PHP_URL_HOST);

                foreach ($updatesToMake as $id => $deltas) {
                    ShortUrl::where('id', $id)->update([
                        'total_visits' => DB::raw("total_visits + {$deltas['total']}"),
                        'unique_visits' => DB::raw("unique_visits + {$deltas['unique']}"),
                        'qr_scans' => DB::raw("qr_scans + {$deltas['qr']}"),
                    ]);
                    $processed++;
                }

                // Bust redirect cache for all host-variant keys so subsequent requests
                // see fresh counters (for max_visits enforcement etc.)
                foreach ($shortUrls as $url) {
                    $hostsToForget = array_unique(array_filter([
                        'default',
                        $appHost,
                        $url->customDomain?->domain,
                    ]));

                    foreach ($hostsToForget as $host) {
                        Cache::forget("filament-short-url:{$url->url_key}:{$host}");
                    }
                }
            });
        } catch (\Throwable $e) {
            // DB transaction failed — restore pulled cache values so no clicks are lost.
            // Increments are used (not set) to safely merge with any new clicks that
            // arrived during the failed transaction window.
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

            // Restore dirty IDs using the same driver-aware strategy
            if ($isRedis) {
                $conn = $store->connection();
                $conn->sadd($dirtyKey, ...array_keys($updatesToMake));
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
