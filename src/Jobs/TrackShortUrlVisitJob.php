<?php

namespace Bjanczak\FilamentShortUrl\Jobs;

use Bjanczak\FilamentShortUrl\Events\ShortUrlVisited;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\Ga4MeasurementProtocolService;
use Bjanczak\FilamentShortUrl\Services\LiveFeedBroadcaster;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        public readonly int $shortUrlId,
        public readonly string $ipAddress,
        public readonly string $userAgent,
        public readonly ?string $refererUrl = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $city = null,
        public readonly ?string $utmSource = null,
        public readonly ?string $utmMedium = null,
        public readonly ?string $utmCampaign = null,
        public readonly ?string $utmTerm = null,
        public readonly ?string $utmContent = null,
        public readonly bool $isQrScan = false,
        public readonly ?string $browserLanguage = null,
        public readonly ?string $selectedVariant = null,
        /** @var array{is_proxy?: bool, is_bot?: bool}|null */
        public readonly ?array $precomputedProxyDetection = null,
        public readonly bool $skipTotalIncrement = false,
    ) {
        $this->onQueue(config('filament-short-url.queue_name', 'default'));
    }

    public function handle(ShortUrlTracker $tracker): void
    {
        // Re-fetch fresh model to avoid acting on stale state
        $shortUrl = ShortUrl::find($this->shortUrlId);

        if (! $shortUrl || ! $shortUrl->track_visits) {
            return;
        }

        // Reconstruct a minimal Request-like object for the tracker
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => $this->ipAddress,
            'HTTP_USER_AGENT' => $this->userAgent,
            'HTTP_REFERER' => $this->refererUrl,
        ]);

        $countryCode = $this->countryCode;
        $city = $this->city;

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
            selectedVariant: $this->selectedVariant,
            precomputedProxyDetection: $this->precomputedProxyDetection,
            skipTotalIncrement: $this->skipTotalIncrement,
        );

        // Null means the tracker detected a bot/crawler — nothing to dispatch.
        if ($visit === null) {
            return;
        }

        LiveFeedBroadcaster::publish($shortUrl->id, (int) $visit->id);

        // Skip webhooks and GA4 for proxy/VPN visits. The visit is still persisted
        // to the database for audit purposes, but external integrations should only
        // receive events for legitimate human traffic — matching what the stats UI shows.
        if ($visit->is_bot || $visit->is_proxy) {
            return;
        }

        // Fire event for user listeners
        ShortUrlVisited::dispatch($shortUrl, $visit);

        // Check if limit is reached now
        if ($shortUrl->max_visits !== null && $shortUrl->getRealTimeTotalVisits() >= $shortUrl->max_visits) {
            $limitReachedKey = "fsu:limit-reached-webhook-sent:{$shortUrl->id}";
            if (cache()->add($limitReachedKey, true, 86400 * 30)) {
                $shortUrl->dispatchWebhook('limit_reached');
            }
        }

        // Trigger Webhook if active
        $shortUrl->dispatchWebhook('visited', [
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
        ]);

        // Optional GA4 Measurement Protocol integration
        if ($shortUrl->ga_tracking_id && config('filament-short-url.ga4.api_secret')) {
            app(Ga4MeasurementProtocolService::class)->send(
                $shortUrl,
                $visit,
                $this->ipAddress,
                $this->userAgent,
            );
        }
    }
}
