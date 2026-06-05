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

        $totalClicks = 0;
        $humanClicks = 0;
        $botClicks = 0;
        $humanPercentage = 0;
        $botPercentage = 0;
        $proxyClicks = 0;
        $proxyPercentage = 0;

        // Perform total count query only if we load bots or vpn
        if (! empty($this->loadedTabs)) {
            $totalQuery = $this->record->visits()
                ->whereBetween('visited_at', [
                    ($this->dateFrom ? $this->dateFrom.' 00:00:00' : '1970-01-01 00:00:00'),
                    ($this->dateTo ? $this->dateTo.' 23:59:59' : now()->toDateTimeString()),
                ]);
            $this->record->applyStatsFilters($totalQuery, $this->filters);
            $totalClicks = $totalQuery->count();
        }

        // Calculate Bot ratio
        if (isset($this->loadedTabs['bots'])) {
            $botQuery = $this->record->visits()
                ->where('is_bot', true)
                ->whereBetween('visited_at', [
                    ($this->dateFrom ? $this->dateFrom.' 00:00:00' : '1970-01-01 00:00:00'),
                    ($this->dateTo ? $this->dateTo.' 23:59:59' : now()->toDateTimeString()),
                ]);
            $this->record->applyStatsFilters($botQuery, $this->filters);
            $botClicks = $botQuery->count();

            $humanClicks = max(0, $totalClicks - $botClicks);
            $humanPercentage = $totalClicks > 0 ? round(($humanClicks / $totalClicks) * 100, 1) : 0;
            $botPercentage = $totalClicks > 0 ? round(($botClicks / $totalClicks) * 100, 1) : 0;
        }

        // Calculate VPN / Proxy ratio
        if (isset($this->loadedTabs['vpn'])) {
            $proxyQuery = $this->record->visits()
                ->where('is_proxy', true)
                ->whereBetween('visited_at', [
                    ($this->dateFrom ? $this->dateFrom.' 00:00:00' : '1970-01-01 00:00:00'),
                    ($this->dateTo ? $this->dateTo.' 23:59:59' : now()->toDateTimeString()),
                ]);
            $this->record->applyStatsFilters($proxyQuery, $this->filters);
            $proxyClicks = $proxyQuery->count();
            $proxyPercentage = $totalClicks > 0 ? round(($proxyClicks / $totalClicks) * 100, 1) : 0;
        }

        return [
            'activeTab' => $this->activeTab,
            'totalClicks' => $totalClicks,
            'humanClicks' => $humanClicks,
            'botClicks' => $botClicks,
            'humanPercentage' => $humanPercentage,
            'botPercentage' => $botPercentage,
            'proxyClicks' => $proxyClicks,
            'proxyPercentage' => $proxyPercentage,
        ];
    }
}
