<?php

namespace Bjanczak\FilamentShortUrl\Jobs;

use Bjanczak\FilamentShortUrl\Events\ShortUrlVisited;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Queued job for recording short URL visits.
 *
 * By dispatching this to a queue, the redirect response is sent to the visitor
 * immediately — tracking happens asynchronously without adding latency.
 */
class TrackShortUrlVisitJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** @var int Max retry attempts if the job fails */
    public int $tries = 3;

    /** @var int Delay between retries (seconds) */
    public int $backoff = 5;

    public function __construct(
        public readonly ShortUrl $shortUrl,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly ?string $refererUrl,
        public readonly ?string $countryCode = null,
        public readonly ?string $city = null,
        public readonly ?string $utmSource = null,
        public readonly ?string $utmMedium = null,
        public readonly ?string $utmCampaign = null,
        public readonly ?string $utmTerm = null,
        public readonly ?string $utmContent = null,
        public readonly bool $isQrScan = false,
        public readonly ?string $browserLanguage = null,
    ) {
        $this->onQueue(config('filament-short-url.queue_name', 'default'));
    }

    public function handle(ShortUrlTracker $tracker): void
    {
        // Re-fetch fresh model to avoid acting on stale state
        $shortUrl = ShortUrl::find($this->shortUrl->id);

        if (! $shortUrl || ! $shortUrl->track_visits) {
            return;
        }

        // Reconstruct a minimal Request-like object for the tracker
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => $this->ipAddress,
            'HTTP_USER_AGENT' => $this->userAgent,
            'HTTP_REFERER' => $this->refererUrl,
        ]);

        $countryCode = isset($this->countryCode) ? $this->countryCode : null;
        $city = isset($this->city) ? $this->city : null;

        $visit = $tracker->record(
            shortUrl: $shortUrl,
            request: $request,
            preResolvedCountryCode: $countryCode,
            preResolvedCity: $city,
            utmSource: $this->utmSource,
            utmMedium: $this->utmMedium,
            utmCampaign: $this->utmCampaign,
            utmTerm: $this->utmTerm,
            utmContent: $this->utmContent,
            isQrScan: $this->isQrScan,
            browserLanguage: $this->browserLanguage,
        );

        // Null means bot/crawler — nothing to dispatch or report
        if ($visit === null) {
            return;
        }

        // Fire event for user listeners
        ShortUrlVisited::dispatch($shortUrl, $visit);

        // Trigger Webhook if active
        $targetUrl = $shortUrl->webhook_url;
        $globalUrl = config('filament-short-url.global_webhook_url');
        $events = config('filament-short-url.webhook_events', []);

        $webhooksToDispatch = [];
        if (! empty($targetUrl)) {
            $webhooksToDispatch[] = $targetUrl;
        }
        if (! empty($globalUrl) && in_array('visited', $events)) {
            $webhooksToDispatch[] = $globalUrl;
        }

        if (! empty($webhooksToDispatch)) {
            $payload = [
                'event' => 'visited',
                'timestamp' => now()->toIso8601String(),
                'short_url' => [
                    'id' => $shortUrl->id,
                    'destination_url' => $shortUrl->destination_url,
                    'url_key' => $shortUrl->url_key,
                    'short_url' => $shortUrl->getShortUrl(),
                    'total_visits' => (int) $shortUrl->getRealTimeTotalVisits(),
                    'unique_visits' => (int) $shortUrl->unique_visits,
                ],
                'visit' => [
                    'id' => $visit->id,
                    'visited_at' => $visit->visited_at->toIso8601String(),
                    'device_type' => $visit->device_type,
                    'browser' => $visit->browser,
                    'browser_version' => $visit->browser_version,
                    'operating_system' => $visit->operating_system,
                    'operating_system_version' => $visit->operating_system_version,
                    'country' => $visit->country,
                    'country_code' => $visit->country_code,
                    'city' => $visit->city,
                    'referer_url' => $visit->referer_url,
                    'referer_host' => $visit->referer_host,
                    'utm_source' => $visit->utm_source,
                    'utm_medium' => $visit->utm_medium,
                    'utm_campaign' => $visit->utm_campaign,
                    'utm_term' => $visit->utm_term,
                    'utm_content' => $visit->utm_content,
                    'is_qr_scan' => (bool) $visit->is_qr_scan,
                    'browser_language' => $visit->browser_language,
                ],
            ];

            foreach (array_unique($webhooksToDispatch) as $url) {
                try {
                    dispatch(new SendWebhookJob(
                        url: $url,
                        event: 'visited',
                        payload: $payload
                    )->onConnection($this->connection ?: 'sync'));
                } catch (\Throwable $e) {
                    Log::error('[FilamentShortUrl] Visited webhook dispatch failed', [
                        'url' => $url,
                        'url_key' => $shortUrl->url_key,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Optional GA4 Measurement Protocol integration
        if ($shortUrl->ga_tracking_id && config('filament-short-url.ga4.api_secret')) {
            $this->sendGa4Hit($shortUrl, $visit);
        }
    }

    private function sendGa4Hit(ShortUrl $shortUrl, ShortUrlVisit $visit): void
    {
        $apiSecret = config('filament-short-url.ga4.api_secret');
        $firebaseAppId = config('filament-short-url.ga4.firebase_app_id');

        // GA4 Measurement Protocol requires a client_id
        $clientId = Str::uuid()->toString();

        $payload = [
            'client_id' => $clientId,
            'events' => [
                [
                    'name' => 'short_url_visit',
                    'params' => [
                        'url_key' => $shortUrl->url_key,
                        'destination_url' => $shortUrl->destination_url,
                        'device_type' => $visit->device_type ?? 'unknown',
                        'country' => $visit->country ?? 'unknown',
                        'browser' => $visit->browser ?? 'unknown',
                    ],
                ],
            ],
        ];

        $queryParams = ['api_secret' => $apiSecret];
        if ($firebaseAppId) {
            $queryParams['firebase_app_id'] = $firebaseAppId;
        } else {
            $queryParams['measurement_id'] = $shortUrl->ga_tracking_id;
        }

        try {
            Http::timeout(5)
                ->post(
                    'https://www.google-analytics.com/mp/collect?'.http_build_query($queryParams),
                    $payload
                );
        } catch (\Throwable $e) {
            Log::warning('[FilamentShortUrl] GA4 Measurement Protocol hit failed', [
                'url_key' => $shortUrl->url_key,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
