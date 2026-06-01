<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlVisitsRightBreakdown extends Widget
{
    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

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
                'visitsByCity' => [],
                'totalVisits' => 0,
            ];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo);

        return [
            'visitsByCountry' => $stats['visitsByCountry'] ?? [],
            'visitsByCity' => $stats['visitsByCity'] ?? [],
            'totalVisits' => $stats['totalVisits'] ?? 0,
        ];
    }
}
