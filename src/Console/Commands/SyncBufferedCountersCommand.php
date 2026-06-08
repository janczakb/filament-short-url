<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\VisitCounterBuffer;
use Bjanczak\FilamentShortUrl\Support\ShortUrlCacheInvalidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncBufferedCountersCommand extends Command
{
    /** @var string */
    protected $signature = 'short-url:sync-counters';

    /** @var string */
    protected $description = 'Sync buffered short URL visit counters from cache to the database';

    public function handle(VisitCounterBuffer $buffer): int
    {
        $pull = $buffer->pullDirtyIdsForSync();
        $dirtyIds = $pull['ids'];

        if (empty($dirtyIds)) {
            $this->info('No buffered counters to synchronize.');

            return 0;
        }

        $processed = 0;
        $updatesToMake = [];

        foreach ($dirtyIds as $id) {
            $deltas = $buffer->pullDeltas((int) $id);

            if ($deltas['total'] > 0 || $deltas['unique'] > 0 || $deltas['qr'] > 0) {
                $updatesToMake[$id] = $deltas;
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

                foreach ($updatesToMake as $id => $deltas) {
                    ShortUrl::where('id', $id)->update([
                        'total_visits' => DB::raw("total_visits + {$deltas['total']}"),
                        'unique_visits' => DB::raw("unique_visits + {$deltas['unique']}"),
                        'qr_scans' => DB::raw("qr_scans + {$deltas['qr']}"),
                    ]);
                    $processed++;
                }

                foreach ($shortUrls as $url) {
                    ShortUrlCacheInvalidator::forget($url);
                }
            });
        } catch (Throwable $e) {
            $buffer->restoreDeltasAfterFailedSync($updatesToMake);
            $buffer->restoreDirtyIds(
                array_keys($updatesToMake),
                $pull['connection'],
                $pull['prefixedDirtyKey'],
                $pull['requeueKey'],
            );

            throw $e;
        }

        if ($pull['requeue'] && $pull['requeueKey'] && $pull['connection']) {
            $pull['connection']->del($pull['requeueKey']);
        }

        $this->info("Successfully synchronized counters for {$processed} short URLs.");

        return 0;
    }
}
