<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services\Queue;

use Bjanczak\FilamentShortUrl\Jobs\VerifyQueueWorkerJob;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Support\Str;

/**
 * Dispatches a probe job and waits for a worker to process it.
 */
class PluginQueueWorkerTester
{
    public function __construct(
        private readonly ShortUrlSettingsManager $settingsManager,
    ) {}

    /**
     * @param  array<string, mixed>  $previewSettings
     * @return array{
     *     ok: bool,
     *     message: string,
     *     queue_connection: ?string,
     *     queue_name: ?string,
     *     latency_ms: ?float,
     * }
     */
    public function test(array $previewSettings = []): array
    {
        return $this->settingsManager->withPreviewSettings($previewSettings, function (): array {
            $connection = (string) config('filament-short-url.queue_connection', 'sync');
            $queueName = (string) config('filament-short-url.queue_name', 'default');

            if ($connection === 'sync') {
                return [
                    'ok' => true,
                    'message' => 'Queue Connection is sync — jobs run immediately during the request. No background worker is required.',
                    'queue_connection' => $connection,
                    'queue_name' => $queueName,
                    'latency_ms' => 0.0,
                ];
            }

            $probeId = (string) Str::uuid();
            QueueWorkerProbe::clear($probeId);

            try {
                dispatch(
                    (new VerifyQueueWorkerJob($probeId))
                        ->onConnection($connection)
                        ->onQueue($queueName)
                );
            } catch (\Throwable $e) {
                return [
                    'ok' => false,
                    'message' => 'Failed to enqueue probe job: '.$e->getMessage(),
                    'queue_connection' => $connection,
                    'queue_name' => $queueName,
                    'latency_ms' => null,
                ];
            }

            $startedAt = microtime(true);
            $timeoutSeconds = 12;

            while ((microtime(true) - $startedAt) < $timeoutSeconds) {
                if (QueueWorkerProbe::isProcessed($probeId)) {
                    $latencyMs = round((microtime(true) - $startedAt) * 1000, 1);
                    QueueWorkerProbe::clear($probeId);

                    return [
                        'ok' => true,
                        'message' => sprintf(
                            'Worker processed the probe on connection "%s", queue "%s" in %s ms.',
                            $connection,
                            $queueName,
                            (string) $latencyMs,
                        ),
                        'queue_connection' => $connection,
                        'queue_name' => $queueName,
                        'latency_ms' => $latencyMs,
                    ];
                }

                usleep(250_000);
            }

            return [
                'ok' => false,
                'message' => sprintf(
                    'Probe job was enqueued on connection "%s", queue "%s", but no worker processed it within %s seconds. Run: php artisan queue:work %s --queue=%s',
                    $connection,
                    $queueName,
                    (string) $timeoutSeconds,
                    $connection,
                    $queueName,
                ),
                'queue_connection' => $connection,
                'queue_name' => $queueName,
                'latency_ms' => null,
            ];
        });
    }
}
