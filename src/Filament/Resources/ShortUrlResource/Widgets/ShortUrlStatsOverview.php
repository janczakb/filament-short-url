<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShortUrlStatsOverview extends BaseWidget
{
    public ?ShortUrl $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $stats = $this->record->getCachedStats();

        return [
            Stat::make(__('filament-short-url::default.stats_card_total'), number_format($stats['totalVisits'] ?? 0))
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('primary'),

            Stat::make(__('filament-short-url::default.stats_card_unique'), number_format($stats['uniqueVisits'] ?? 0))
                ->icon('heroicon-o-user-group')
                ->color('info'),

            Stat::make(__('filament-short-url::default.stats_card_today'), number_format($stats['visitsToday'] ?? 0))
                ->icon('heroicon-o-sun')
                ->color('warning'),

            Stat::make(__('filament-short-url::default.stats_card_week'), number_format($stats['visitsThisWeek'] ?? 0))
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make(__('filament-short-url::default.stats_card_month'), number_format($stats['visitsThisMonth'] ?? 0))
                ->icon('heroicon-o-calendar')
                ->color('gray'),
        ];
    }
}
