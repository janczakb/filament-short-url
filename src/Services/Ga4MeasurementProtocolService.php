<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Ga4MeasurementProtocolService
{
    private const COLLECT_URL = 'https://www.google-analytics.com/mp/collect';

    private const DEBUG_COLLECT_URL = 'https://www.google-analytics.com/debug/mp/collect';

    public function isValidMeasurementId(?string $measurementId): bool
    {
        if ($measurementId === null || $measurementId === '') {
            return false;
        }

        return preg_match('/^G-[A-Z0-9]+$/', strtoupper(trim($measurementId))) === 1;
    }

    public function normalizeMeasurementId(?string $measurementId): ?string
    {
        if ($measurementId === null || $measurementId === '') {
            return null;
        }

        $normalized = strtoupper(trim($measurementId));

        return $this->isValidMeasurementId($normalized) ? $normalized : null;
    }

    /**
     * Privacy-safe deterministic client_id for GA4 MP session stitching.
     */
    public function buildClientId(string $ipAddress, string $userAgent): string
    {
        $hash = hash('sha256', $ipAddress.'|'.$userAgent);

        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }

    /**
     * GA4 expects a numeric session_id stable for the visitor session window.
     */
    public function buildSessionId(string $clientId): int
    {
        $window = now()->format('YmdH').(int) (now()->minute < 30 ? '0' : '1');

        return abs(crc32($clientId.'|'.$window));
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(
        ShortUrl $shortUrl,
        ShortUrlVisit $visit,
        string $clientId,
        int $sessionId,
    ): array {
        $visitedAt = $visit->visited_at ?? now();
        $shortUrlLocation = $shortUrl->getShortUrl();
        $pageTitle = $shortUrl->og_title ?: $shortUrl->url_key;

        $sharedParams = [
            'session_id' => $sessionId,
            'engagement_time_msec' => 100,
        ];

        $events = [
            [
                'name' => 'page_view',
                'params' => array_filter(array_merge($sharedParams, [
                    'page_location' => $shortUrlLocation,
                    'page_title' => $pageTitle,
                    'page_referrer' => $visit->referer_url,
                    'language' => $visit->browser_language,
                ])),
            ],
            [
                'name' => 'click',
                'params' => array_filter(array_merge($sharedParams, [
                    'link_url' => $shortUrl->destination_url,
                    'link_domain' => parse_url($shortUrl->destination_url, PHP_URL_HOST) ?: null,
                    'outbound' => true,
                ])),
            ],
            [
                'name' => 'short_url_visit',
                'params' => array_filter([
                    ...$sharedParams,
                    'url_key' => $shortUrl->url_key,
                    'destination_url' => $shortUrl->destination_url,
                    'device_type' => $visit->device_type ?? 'unknown',
                    'country' => $visit->country_code ?? $visit->country ?? 'unknown',
                    'browser' => $visit->browser ?? 'unknown',
                    'is_qr_scan' => (bool) $visit->is_qr_scan,
                ]),
            ],
        ];

        return [
            'client_id' => $clientId,
            'timestamp_micros' => (string) ($visitedAt->getTimestamp() * 1_000_000),
            'events' => $events,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function buildQueryParams(string $measurementId, string $apiSecret, ?string $firebaseAppId = null): array
    {
        $query = ['api_secret' => $apiSecret];

        if ($firebaseAppId) {
            $query['firebase_app_id'] = $firebaseAppId;

            return $query;
        }

        $query['measurement_id'] = $this->normalizeMeasurementId($measurementId) ?? $measurementId;

        return $query;
    }

    public function send(ShortUrl $shortUrl, ShortUrlVisit $visit, string $ipAddress, string $userAgent): bool
    {
        $apiSecret = config('filament-short-url.ga4.api_secret');
        $firebaseAppId = config('filament-short-url.ga4.firebase_app_id');

        if (! is_string($apiSecret) || $apiSecret === '') {
            return false;
        }

        $measurementId = $this->normalizeMeasurementId($shortUrl->ga_tracking_id);

        if ($firebaseAppId === null || $firebaseAppId === '') {
            if ($measurementId === null) {
                Log::warning('[FilamentShortUrl] GA4 MP skipped — invalid measurement ID on link.', [
                    'url_key' => $shortUrl->url_key,
                    'ga_tracking_id' => $shortUrl->ga_tracking_id,
                ]);

                return false;
            }
        }

        $clientId = $this->buildClientId($ipAddress, $userAgent);
        $sessionId = $this->buildSessionId($clientId);
        $payload = $this->buildPayload($shortUrl, $visit, $clientId, $sessionId);
        $query = $this->buildQueryParams(
            $measurementId ?? '',
            $apiSecret,
            is_string($firebaseAppId) && $firebaseAppId !== '' ? $firebaseAppId : null,
        );

        try {
            $response = Http::timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::COLLECT_URL.'?'.http_build_query($query), $payload);

            if ($response->failed()) {
                Log::warning('[FilamentShortUrl] GA4 Measurement Protocol hit rejected', [
                    'url_key' => $shortUrl->url_key,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[FilamentShortUrl] GA4 Measurement Protocol hit failed', [
                'url_key' => $shortUrl->url_key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate api_secret + measurement_id (or firebase_app_id) using GA4 debug collector.
     *
     * @return array{valid: bool, messages: list<array<string, mixed>>}
     */
    public function validateCredentials(
        string $measurementId,
        string $apiSecret,
        ?string $firebaseAppId = null,
    ): array {
        $measurementId = $this->normalizeMeasurementId($measurementId) ?? strtoupper(trim($measurementId));

        if (($firebaseAppId === null || $firebaseAppId === '') && ! $this->isValidMeasurementId($measurementId)) {
            return [
                'valid' => false,
                'messages' => [
                    ['description' => 'Invalid measurement ID format. Expected G-XXXXXXXXXX.'],
                ],
            ];
        }

        $query = $this->buildQueryParams($measurementId, $apiSecret, $firebaseAppId);
        $clientId = 'short-url-plugin-verify.'.substr(hash('sha256', $apiSecret), 0, 12);
        $payload = [
            'client_id' => $clientId,
            'timestamp_micros' => (string) (now()->getTimestamp() * 1_000_000),
            'events' => [
                [
                    'name' => 'page_view',
                    'params' => [
                        'session_id' => 1,
                        'engagement_time_msec' => 100,
                        'page_location' => rtrim((string) config('app.url'), '/').'/short-url-ga4-verify',
                        'page_title' => 'Short URL GA4 Verify',
                        'debug_mode' => 1,
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(8)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::DEBUG_COLLECT_URL.'?'.http_build_query($query), $payload);

            $messages = $response->json('validationMessages') ?? [];

            if (! is_array($messages)) {
                $messages = [];
            }

            if ($response->status() === 401) {
                return [
                    'valid' => false,
                    'messages' => [['description' => 'GA4 rejected the API secret (HTTP 401).']],
                ];
            }

            $blocking = collect($messages)->filter(function ($message): bool {
                if (! is_array($message)) {
                    return true;
                }

                $description = strtolower((string) ($message['description'] ?? ''));
                $validationCode = strtoupper((string) ($message['validationCode'] ?? ''));

                if (str_contains($description, 'api_secret') || str_contains($description, 'measurement id')) {
                    return true;
                }

                return in_array($validationCode, ['VALUE_INVALID', 'NAME_INVALID', 'NAME_RESERVED'], true);
            });

            return [
                'valid' => $blocking->isEmpty(),
                'messages' => $messages,
            ];
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'messages' => [['description' => $e->getMessage()]],
            ];
        }
    }
}
