<?php

namespace Bjanczak\FilamentShortUrl\Jobs;

use Bjanczak\FilamentShortUrl\Services\Queue\QueueWorkerProbe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyQueueWorkerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 30;

    public function __construct(
        public readonly string $probeId,
    ) {}

    public function handle(): void
    {
        QueueWorkerProbe::markProcessed($this->probeId);
    }
}
