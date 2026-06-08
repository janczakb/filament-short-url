<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlSecurityBreakdownWidget extends Widget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $activeTab = 'bots';

    public array $loadedTabs = ['bots' => true];

    protected string $view = 'filament-short-url::widgets.security-breakdown';

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
                'totalClicks' => 0,
                'humanClicks' => 0,
                'botClicks' => 0,
                'humanPercentage' => 0,
                'botPercentage' => 0,
                'proxyClicks' => 0,
                'proxyPercentage' => 0,
            ];
        }

        $stats = $this->record->getSecurityBreakdownStats(
            dateFrom: $this->dateFrom,
            dateTo: $this->dateTo,
            filters: $this->filters,
        );

        return [
            'activeTab' => $this->activeTab,
            'totalClicks' => $stats['totalClicks'],
            'humanClicks' => $stats['humanClicks'],
            'botClicks' => $stats['botClicks'],
            'humanPercentage' => $stats['humanPercentage'],
            'botPercentage' => $stats['botPercentage'],
            'proxyClicks' => $stats['proxyClicks'],
            'proxyPercentage' => $stats['proxyPercentage'],
        ];
    }
}
