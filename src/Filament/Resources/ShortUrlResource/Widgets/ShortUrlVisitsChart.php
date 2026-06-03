<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ShortUrlVisitsChart extends ChartWidget
{
    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected ?string $maxHeight = '200px';

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return __('filament-short-url::default.stats_chart_title');
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo);
        $visitsByDay = $stats['visitsByDay'] ?? [];

        return [
            'datasets' => [
                [
                    'label' => __('filament-short-url::default.qr_chart_visits_label'),
                    'data' => array_values($visitsByDay),
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.08)',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => 'rgb(99, 102, 241)',
                    'pointRadius' => 3,
                    'tension' => 0.4,
                    'fill' => true,
                ],
            ],
            'labels' => array_map(function (string $date) {
                try {
                    $carbon = Carbon::parse($date);
                    if (strlen($date) === 7) { // Y-m
                        return $carbon->format('m.Y');
                    }

                    return $carbon->format('d.m');
                } catch (\Throwable $e) {
                    return $date;
                }
            }, array_keys($visitsByDay)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
