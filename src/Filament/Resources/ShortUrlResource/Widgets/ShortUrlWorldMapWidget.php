<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlWorldMapWidget extends Widget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $view = 'filament-short-url::widgets.world-map';

    protected int|string|array $columnSpan = 'full';

    public function mount(?ShortUrl $record = null): void
    {
        $this->record = $record;
    }

    /**
     * Build a country_code → visit count map via the shared stats cache layer.
     *
     * @return array<string, int>
     */
    public function getCountryData(): array
    {
        if (! $this->record) {
            return [];
        }

        $stats = $this->record->getCachedStats(
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
            filters: $this->filters,
        );

        $countries = $stats['visitsByCountry'] ?? [];

        if (! is_array($countries)) {
            return [];
        }

        arsort($countries);

        return $countries;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $countryData = $this->getCountryData();
        $max = max(1, ...array_values($countryData) ?: [1]);
        $total = array_sum($countryData);

        $normalized = [];
        foreach ($countryData as $code => $count) {
            $normalized[$code] = round(($count / $max) * 100);
        }

        $svgPath = dirname(__FILE__, 6).'/resources/views/widgets/world-map.svg';
        $svgContent = cache()->remember('filament-short-url:world-map-svg', 86400, function () use ($svgPath): string {
            if (! file_exists($svgPath)) {
                return '';
            }

            $content = file_get_contents($svgPath);
            if (! is_string($content)) {
                return '';
            }

            $content = preg_replace('/<\?xml[^>]*\?>/i', '', $content) ?? $content;
            $content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $content) ?? $content;

            return $content;
        });

        return [
            'countryData' => $countryData,
            'maxCount' => $max,
            'totalClicks' => $total,
            'normalized' => $normalized,
            'svgContent' => $svgContent,
        ];
    }
}
