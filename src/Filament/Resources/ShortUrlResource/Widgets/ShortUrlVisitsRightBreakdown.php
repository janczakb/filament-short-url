<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlVisitsRightBreakdown extends Widget
{
    public ?ShortUrl $record = null;

    protected string $view = 'filament-short-url::widgets.visits-right-breakdown';

    protected int|string|array $columnSpan = [
        'lg' => 1,
    ];

    public function mount(?ShortUrl $record = null): void
    {
        $this->record = $record;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        if (! $this->record) {
            return [
                'visitsByCountry' => [],
                'totalVisits' => 0,
            ];
        }

        $stats = $this->record->getCachedStats();

        return [
            'visitsByCountry' => $stats['visitsByCountry'] ?? [],
            'totalVisits' => $stats['totalVisits'] ?? 0,
        ];
    }
}
