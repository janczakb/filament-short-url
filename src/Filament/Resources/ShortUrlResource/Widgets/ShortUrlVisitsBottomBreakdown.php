<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlVisitsBottomBreakdown extends Widget
{
    public ?ShortUrl $record = null;

    protected string $view = 'filament-short-url::widgets.visits-bottom-breakdown';

    protected int|string|array $columnSpan = 'full';

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
                'visitsByDevice' => [],
                'visitsByBrowser' => [],
                'visitsByOs' => [],
                'visitsByReferer' => [],
                'totalVisits' => 0,
            ];
        }

        $stats = $this->record->getCachedStats();

        return [
            'visitsByDevice' => $stats['visitsByDevice'] ?? [],
            'visitsByBrowser' => $stats['visitsByBrowser'] ?? [],
            'visitsByOs' => $stats['visitsByOs'] ?? [],
            'visitsByReferer' => $stats['visitsByReferer'] ?? [],
            'totalVisits' => $stats['totalVisits'] ?? 0,
        ];
    }
}
