<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Widgets\Widget;

class ShortUrlSourcesBreakdownWidget extends Widget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $activeTab = 'referrers';

    public string $activeSubTab = 'sources';

    public array $loadedTabs = ['referrers' => true];

    protected string $view = 'filament-short-url::widgets.sources-breakdown';

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'referrers') {
            $this->loadedTabs['referrers'] = true;
        } elseif ($tab === 'utm') {
            $this->loadedTabs['utm_'.$this->activeSubTab] = true;
        }
    }

    public function setActiveSubTab(string $subTab): void
    {
        $this->activeSubTab = $subTab;
        $this->loadedTabs['utm_'.$subTab] = true;
    }

    protected function getViewData(): array
    {
        if (! $this->record) {
            return [
                'activeTab' => $this->activeTab,
                'activeSubTab' => $this->activeSubTab,
                'visitsByReferer' => [],
                'utmSources' => [],
                'utmMediums' => [],
                'utmCampaigns' => [],
                'utmTerms' => [],
                'utmContents' => [],
                'totalVisits' => 0,
            ];
        }

        $stats = $this->record->getCachedStats($this->dateFrom, $this->dateTo, $this->filters);

        return [
            'activeTab' => $this->activeTab,
            'activeSubTab' => $this->activeSubTab,
            'visitsByReferer' => isset($this->loadedTabs['referrers']) ? ($stats['visitsByReferer'] ?? []) : [],
            'utmSources' => isset($this->loadedTabs['utm_sources']) ? ($stats['utmSources'] ?? []) : [],
            'utmMediums' => isset($this->loadedTabs['utm_mediums']) ? ($stats['utmMediums'] ?? []) : [],
            'utmCampaigns' => isset($this->loadedTabs['utm_campaigns']) ? ($stats['utmCampaigns'] ?? []) : [],
            'utmTerms' => isset($this->loadedTabs['utm_terms']) ? ($stats['utmTerms'] ?? []) : [],
            'utmContents' => isset($this->loadedTabs['utm_contents']) ? ($stats['utmContents'] ?? []) : [],
            'totalVisits' => $stats['totalVisits'] ?? 0,
        ];
    }
}
