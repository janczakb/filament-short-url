<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Illuminate\Support\Collection;

class VisitCsvExporter
{
    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            __('filament-short-url::default.stats_csv_time'),
            __('filament-short-url::default.stats_csv_ip'),
            __('filament-short-url::default.stats_csv_country'),
            __('filament-short-url::default.stats_csv_device'),
            __('filament-short-url::default.stats_csv_browser'),
            __('filament-short-url::default.stats_csv_os'),
            __('filament-short-url::default.stats_csv_referer'),
            __('filament-short-url::default.stats_csv_utm_source'),
            __('filament-short-url::default.stats_csv_utm_medium'),
            __('filament-short-url::default.stats_csv_utm_campaign'),
            __('filament-short-url::default.stats_csv_utm_term'),
            __('filament-short-url::default.stats_csv_utm_content'),
            __('filament-short-url::default.stats_csv_variant'),
            __('filament-short-url::default.stats_csv_qr_scan'),
            __('filament-short-url::default.stats_csv_bot'),
            __('filament-short-url::default.stats_csv_proxy'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function row(ShortUrlVisit $visit): array
    {
        return [
            $visit->visited_at->toDateTimeString(),
            $visit->ip_address ?? '—',
            $visit->country ? "{$visit->country_code} - {$visit->country}" : '—',
            $visit->device_type ? match (strtolower((string) $visit->device_type)) {
                'desktop' => __('filament-short-url::default.stats_device_desktop'),
                'mobile' => __('filament-short-url::default.stats_device_mobile'),
                'tablet' => __('filament-short-url::default.stats_device_tablet'),
                default => ucfirst((string) $visit->device_type),
            } : '—',
            $visit->browser ? "{$visit->browser} ({$visit->browser_version})" : '—',
            $visit->operating_system ? "{$visit->operating_system} ({$visit->operating_system_version})" : '—',
            $visit->referer_url ?? '—',
            $visit->utm_source ?? '—',
            $visit->utm_medium ?? '—',
            $visit->utm_campaign ?? '—',
            $visit->utm_term ?? '—',
            $visit->utm_content ?? '—',
            $visit->selected_variant ?? '—',
            $visit->is_qr_scan ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no'),
            $visit->is_bot ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no'),
            $visit->is_proxy ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no'),
        ];
    }

    /**
     * @param  Collection<int, ShortUrlVisit>|iterable<int, ShortUrlVisit>  $visits
     */
    public function stream(iterable $visits, callable $writer): void
    {
        $writer($this->headers());

        foreach ($visits as $visit) {
            $writer($this->row($visit));
        }
    }
}
