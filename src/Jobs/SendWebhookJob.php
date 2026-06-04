<?php

namespace Bjanczak\FilamentShortUrl\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int Max retry attempts if the webhook fails */
    public int $tries = 3;

    /** @var int Delay between retries (seconds) */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $url,
        public readonly string $event,
        public readonly array $payload
    ) {
        $this->onQueue(config('filament-short-url.queue_name', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'wYachts-ShortUrl-Webhook/1.5',
            ];

            $secret = config('filament-short-url.webhook_signing_secret');
            if (! empty($secret)) {
                $payloadJson = json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $headers['X-ShortUrl-Signature'] = hash_hmac('sha256', $payloadJson, $secret);
            }

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($this->url, $this->payload);

            if ($response->failed()) {
                Log::warning('[FilamentShortUrl] Webhook delivery returned client/server error', [
                    'url' => $this->url,
                    'event' => $this->event,
                    'status' => $response->status(),
                ]);

                // Throw exception to trigger queue retry
                throw new \RuntimeException('Webhook failed with status '.$response->status());
            }
        } catch (\Throwable $e) {
            Log::warning('[FilamentShortUrl] Webhook delivery failed', [
                'url' => $this->url,
                'event' => $this->event,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
