<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShortUrlStatsOverview extends BaseWidget
{
    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo);

        $topSource = 'Direct';
        if (! empty($stats['utmSources'])) {
            $topSource = array_key_first($stats['utmSources']);
        }

        $topCountry = '—';
        if (! empty($stats['visitsByCountry'])) {
            $topCountry = array_key_first($stats['visitsByCountry']);
        }

        return [
            Stat::make(__('filament-short-url::default.stats_card_total'), number_format($stats['totalVisits'] ?? 0))
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('primary'),

            Stat::make(__('filament-short-url::default.stats_card_unique'), number_format($stats['uniqueVisits'] ?? 0))
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make(__('filament-short-url::default.stats_card_today_clicks'), number_format($stats['visitsToday'] ?? 0))
                ->icon('heroicon-o-clock')
                ->color('success'),

            Stat::make(__('filament-short-url::default.stats_card_top_source'), $topSource)
                ->icon('heroicon-o-megaphone')
                ->color('warning'),

            Stat::make(__('filament-short-url::default.stats_card_top_country'), $topCountry)
                ->icon('heroicon-o-globe-alt')
                ->color('success'),

            Stat::make(__('filament-short-url::default.stats_card_qr_scans'), number_format($stats['qrScans'] ?? 0))
                ->icon('heroicon-o-qr-code')
                ->color('info'),
        ];
    }
}
