<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlGeoBreakdownWidget extends Widget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $activeTab = 'countries';

    public array $loadedTabs = ['countries' => true];

    protected string $view = 'filament-short-url::widgets.geo-breakdown';

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->loadedTabs[$tab] = true;
    }

    protected function getViewData(): array
    {
        if (! $this->record) {
            return [
                'activeTab' => $this->activeTab,
                'visitsByCountry' => [],
                'visitsByCity' => [],
                'visitsByLanguage' => [],
                'totalVisits' => 0,
            ];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo, $this->filters);

        return [
            'activeTab' => $this->activeTab,
            'visitsByCountry' => isset($this->loadedTabs['countries']) ? ($stats['visitsByCountry'] ?? []) : [],
            'visitsByCity' => isset($this->loadedTabs['cities']) ? ($stats['visitsByCity'] ?? []) : [],
            'visitsByLanguage' => isset($this->loadedTabs['languages']) ? ($stats['visitsByLanguage'] ?? []) : [],
            'totalVisits' => $stats['totalVisits'] ?? 0,
        ];
    }
}
