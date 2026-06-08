<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support;

class WebhookPayloadExample
{
    /**
     * @return list<string>
     */
    public static function visitedShortUrlKeys(): array
    {
        return [
            'id',
            'destination_url',
            'url_key',
            'short_url',
            'total_visits',
            'unique_visits',
        ];
    }

    /**
     * @return list<string>
     */
    public static function visitedVisitKeys(): array
    {
        return [
            'id',
            'visited_at',
            'device_type',
            'browser',
            'browser_version',
            'operating_system',
            'operating_system_version',
            'country',
            'country_code',
            'city',
            'referer_url',
            'referer_host',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'is_qr_scan',
            'browser_language',
        ];
    }

    /**
     * Sample payload for the `visited` webhook event.
     *
     * @return array<string, mixed>
     */
    public static function visitedEventSample(): array
    {
        return [
            'event' => 'visited',
            'timestamp' => '2026-06-04T12:00:00+02:00',
            'short_url' => [
                'id' => 12,
                'destination_url' => 'https://example.com/some-page',
                'url_key' => 'promo26',
                'short_url' => 'https://yoursite.com/s/promo26',
                'total_visits' => 150,
                'unique_visits' => 120,
            ],
            'visit' => [
                'id' => 345,
                'visited_at' => '2026-06-04T12:00:00+02:00',
                'device_type' => 'mobile',
                'browser' => 'Chrome',
                'browser_version' => '120.0',
                'operating_system' => 'Android',
                'operating_system_version' => '14',
                'country' => 'Poland',
                'country_code' => 'PL',
                'city' => 'Warsaw',
                'referer_url' => 'https://t.co/',
                'referer_host' => 't.co',
                'utm_source' => 'twitter',
                'utm_medium' => 'social',
                'utm_campaign' => 'summer_sale',
                'utm_term' => null,
                'utm_content' => 'banner_ad',
                'is_qr_scan' => false,
                'browser_language' => 'pl',
            ],
        ];
    }

    public static function visitedEventSampleJson(): string
    {
        $json = json_encode(
            self::visitedEventSample(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return is_string($json) ? $json : '{}';
    }
}
