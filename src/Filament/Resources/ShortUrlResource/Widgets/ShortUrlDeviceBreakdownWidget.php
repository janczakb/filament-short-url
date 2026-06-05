<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlDeviceBreakdownWidget extends Widget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $activeTab = 'devices';

    public array $loadedTabs = ['devices' => true];

    protected string $view = 'filament-short-url::widgets.device-breakdown';

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
                'visitsByDevice' => [],
                'visitsByBrowser' => [],
                'visitsByOs' => [],
                'visitsByBrowserVersion' => [],
                'visitsByOsVersion' => [],
                'totalVisits' => 0,
                'browserIcons' => [],
                'osIcons' => [],
            ];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo, $this->filters);

        return [
            'activeTab' => $this->activeTab,
            'visitsByDevice' => isset($this->loadedTabs['devices']) ? ($stats['visitsByDevice'] ?? []) : [],
            'visitsByBrowser' => isset($this->loadedTabs['browsers']) ? ($stats['visitsByBrowser'] ?? []) : [],
            'visitsByOs' => isset($this->loadedTabs['os']) ? ($stats['visitsByOs'] ?? []) : [],
            'visitsByBrowserVersion' => isset($this->loadedTabs['browsers']) ? ($stats['visitsByBrowserVersion'] ?? []) : [],
            'visitsByOsVersion' => isset($this->loadedTabs['os']) ? ($stats['visitsByOsVersion'] ?? []) : [],
            'totalVisits' => $stats['totalVisits'] ?? 0,
            'browserIcons' => [
                'Chrome' => 'heroicon-m-globe-alt',
                'Firefox' => 'heroicon-m-globe-alt',
                'Safari' => 'heroicon-m-globe-alt',
                'Edge' => 'heroicon-m-globe-alt',
                'Opera' => 'heroicon-m-globe-alt',
                'Internet Explorer' => 'heroicon-m-globe-alt',
            ],
            'osIcons' => [
                'Windows' => 'heroicon-m-cpu-chip',
                'OS X' => 'heroicon-m-cpu-chip',
                'Android' => 'heroicon-m-cpu-chip',
                'iOS' => 'heroicon-m-cpu-chip',
                'Ubuntu' => 'heroicon-m-cpu-chip',
                'Linux' => 'heroicon-m-cpu-chip',
            ],
        ];
    }
}
