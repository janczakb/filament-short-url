<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class ShortUrlVariantsWidget extends Widget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $view = 'filament-short-url::widgets.variants';

    protected function getViewData(): array
    {
        if (! $this->record) {
            return [
                'visitsByVariant' => [],
                'totalVisits' => 0,
            ];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo, $this->filters);

        return [
            'visitsByVariant' => $stats['visitsByVariant'] ?? [],
            'totalVisits' => $stats['totalVisits'] ?? 0,
            'rotationVariants' => $this->record->rotation_variants ?? [],
        ];
    }

    public function render(): View
    {
        if (! $this->record) {
            return view('filament-short-url::widgets.empty');
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo, $this->filters);
        if (empty($stats['visitsByVariant'])) {
            return view('filament-short-url::widgets.empty');
        }

        return parent::render();
    }
}
