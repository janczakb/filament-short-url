<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
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

        // Pull the list atomically to avoid race conditions with incoming clicks
        if (Cache::getDefaultDriver() === 'redis' && class_exists(\Illuminate\Support\Facades\Redis::class)) {
            $tempKey = "{$dirtyKey}:temp:" . time();
            try {
                \Illuminate\Support\Facades\Redis::rename($dirtyKey, $tempKey);
                $dirtyIds = \Illuminate\Support\Facades\Redis::smembers($tempKey);
                \Illuminate\Support\Facades\Redis::del($tempKey);
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

        foreach ($dirtyIds as $id) {
            $totalKey = "{$prefix}total:{$id}";
            $uniqueKey = "{$prefix}unique:{$id}";

            $totalDelta = (int) Cache::pull($totalKey, 0);
            $uniqueDelta = (int) Cache::pull($uniqueKey, 0);

            if ($totalDelta > 0 || $uniqueDelta > 0) {
                // Perform a single atomic update query for this URL
                ShortUrl::where('id', $id)->update([
                    'total_visits' => DB::raw("total_visits + {$totalDelta}"),
                    'unique_visits' => DB::raw("unique_visits + {$uniqueDelta}"),
                ]);
                $processed++;
            }
        }

        $this->info("Successfully synchronized counters for {$processed} short URLs.");

        return 0;
    }
}
