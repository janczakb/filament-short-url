<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ShortUrlStatsOverview extends BaseWidget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo, $this->filters);
        $totalVisits = (int) ($stats['totalVisits'] ?? 0);

        // Calculate previous period for PoP comparison
        $prevStats = null;
        if ($this->dateFrom && $this->dateTo) {
            try {
                $from = Carbon::parse($this->dateFrom);
                $to = Carbon::parse($this->dateTo);
                $diffInDays = $from->diffInDays($to) + 1;

                $prevFrom = $from->copy()->subDays($diffInDays)->toDateString();
                $prevTo = $from->copy()->subDay()->toDateString();

                $prevStats = $this->record->getCachedStats($prevFrom, $prevTo, $this->filters);
            } catch (\Throwable $e) {
                // Fail gracefully if date parsing fails
            }
        }

        $topSource = 'Direct';
        if (! empty($stats['utmSources'])) {
            $topSource = array_key_first($stats['utmSources']);
        }

        $topCountry = '—';
        if (! empty($stats['visitsByCountry'])) {
            $topCountry = array_key_first($stats['visitsByCountry']);
        }

        $trend = ! empty($stats['visitsByDay']) ? array_values($stats['visitsByDay']) : [];

        return [
            $this->buildStatWithTrend(
                __('filament-short-url::default.stats_card_total'),
                (int) ($stats['totalVisits'] ?? 0),
                $prevStats ? (int) ($prevStats['totalVisits'] ?? 0) : null,
                'heroicon-o-cursor-arrow-rays',
                'primary',
                $trend
            ),

            $this->buildStatWithTrend(
                __('filament-short-url::default.stats_card_unique'),
                (int) ($stats['uniqueVisits'] ?? 0),
                $prevStats ? (int) ($prevStats['uniqueVisits'] ?? 0) : null,
                'heroicon-o-user-group',
                'info',
                $trend
            ),

            $this->buildStatWithTrend(
                __('filament-short-url::default.stats_card_today_clicks'),
                (int) ($stats['visitsToday'] ?? 0),
                $prevStats ? (int) ($prevStats['visitsToday'] ?? 0) : null,
                'heroicon-o-clock',
                'success'
            ),

            Stat::make(__('filament-short-url::default.stats_card_top_source'), $topSource)
                ->icon('heroicon-o-megaphone')
                ->color('warning'),

            Stat::make(__('filament-short-url::default.stats_card_top_country'), $topCountry)
                ->icon('heroicon-o-globe-alt')
                ->color('success'),

            $this->buildStatWithTrend(
                __('filament-short-url::default.stats_card_qr_scans'),
                (int) ($stats['qrScans'] ?? 0),
                $prevStats ? (int) ($prevStats['qrScans'] ?? 0) : null,
                'heroicon-o-qr-code',
                'info'
            ),

            Stat::make(__('filament-short-url::default.stats_card_qr_rate'), number_format($totalVisits > 0 ? (($stats['qrScans'] ?? 0) / $totalVisits) * 100 : 0, 1).'%')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('indigo')
                ->description((function () use ($stats, $totalVisits, $prevStats) {
                    $currentRate = $totalVisits > 0 ? (($stats['qrScans'] ?? 0) / $totalVisits) * 100 : 0;
                    $prevRate = null;
                    if ($prevStats) {
                        $prevTotal = (int) ($prevStats['totalVisits'] ?? 0);
                        $prevQr = (int) ($prevStats['qrScans'] ?? 0);
                        $prevRate = $prevTotal > 0 ? ($prevQr / $prevTotal) * 100 : 0;
                    }
                    if ($prevRate !== null) {
                        $delta = $currentRate - $prevRate;

                        return ($delta >= 0 ? '+' : '').number_format($delta, 1).'% vs '.__('filament-short-url::default.stats_prev_period');
                    }

                    return '— vs '.__('filament-short-url::default.stats_prev_period');
                })())
                ->descriptionIcon((function () use ($stats, $totalVisits, $prevStats) {
                    $currentRate = $totalVisits > 0 ? (($stats['qrScans'] ?? 0) / $totalVisits) * 100 : 0;
                    $prevRate = null;
                    if ($prevStats) {
                        $prevTotal = (int) ($prevStats['totalVisits'] ?? 0);
                        $prevQr = (int) ($prevStats['qrScans'] ?? 0);
                        $prevRate = $prevTotal > 0 ? ($prevQr / $prevTotal) * 100 : 0;
                    }
                    if ($prevRate !== null) {
                        return ($currentRate - $prevRate) >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
                    }

                    return null;
                })())
                ->descriptionColor((function () use ($stats, $totalVisits, $prevStats) {
                    $currentRate = $totalVisits > 0 ? (($stats['qrScans'] ?? 0) / $totalVisits) * 100 : 0;
                    $prevRate = null;
                    if ($prevStats) {
                        $prevTotal = (int) ($prevStats['totalVisits'] ?? 0);
                        $prevQr = (int) ($prevStats['qrScans'] ?? 0);
                        $prevRate = $prevTotal > 0 ? ($prevQr / $prevTotal) * 100 : 0;
                    }
                    if ($prevRate !== null) {
                        return ($currentRate - $prevRate) >= 0 ? 'success' : 'danger';
                    }

                    return 'gray';
                })()),
        ];
    }

    /**
     * Build a stat widget with a calculated percentage trend delta and sparkline chart.
     */
    protected function buildStatWithTrend(string $label, int $current, ?int $previous, string $icon, string $color, array $trend = []): Stat
    {
        $stat = Stat::make($label, number_format($current))
            ->icon($icon)
            ->color($color);

        if (! empty($trend)) {
            $stat->chart($trend);
        }

        if ($previous !== null && $previous > 0) {
            $delta = (($current - $previous) / $previous) * 100;
            $deltaStr = ($delta >= 0 ? '+' : '').number_format($delta, 1).'%';
            $stat->description($deltaStr.' vs '.__('filament-short-url::default.stats_prev_period'))
                ->descriptionIcon($delta >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->descriptionColor($delta >= 0 ? 'success' : 'danger');
        } elseif ($previous === 0 && $current > 0) {
            $stat->description('+100.0% vs '.__('filament-short-url::default.stats_prev_period'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->descriptionColor('success');
        } else {
            $stat->description('— vs '.__('filament-short-url::default.stats_prev_period'))
                ->descriptionColor('gray');
        }

        return $stat;
    }
}
