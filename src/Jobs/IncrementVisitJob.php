<?php

namespace Bjanczak\FilamentShortUrl\Jobs;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class IncrementVisitJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $shortUrlId,
        public readonly bool $isUnique = false,
        public readonly bool $isQrScan = false,
    ) {
        $this->onQueue(config('filament-short-url.queue_name', 'default'));
    }

    public function handle(): void
    {
        $shortUrl = ShortUrl::find($this->shortUrlId);

        if (! $shortUrl) {
            return;
        }

        $updates = [];
        if ($this->isUnique) {
            $updates['unique_visits'] = DB::raw('unique_visits + 1');
        }
        if ($this->isQrScan) {
            $updates['qr_scans'] = DB::raw('qr_scans + 1');
        }

        $shortUrl->newQuery()
            ->where('id', $shortUrl->id)
            ->increment(
                'total_visits',
                1,
                $updates
            );
    }
}
