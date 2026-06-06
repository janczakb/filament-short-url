<x-filament-panels::page>
    <x-filament::tabs class="mb-6">
        <x-filament::tabs.item
            wire:click="$set('activeTab', 'statistics')"
            :active="$activeTab === 'statistics'"
            icon="heroicon-m-presentation-chart-line"
        >
            {{ __('filament-short-url::default.stats_tab_statistics') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            tag="a"
            href="{{ \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource::getUrl('stats.live', ['record' => $record]) }}"
            icon="heroicon-m-bolt"
        >
            {{ __('filament-short-url::default.stats_tab_live_feed') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            tag="a"
            href="{{ \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource::getUrl('stats.logs', ['record' => $record]) }}"
            icon="heroicon-m-list-bullet"
        >
            {{ __('filament-short-url::default.stats_tab_visit_logs') }}
        </x-filament::tabs.item>
    </x-filament::tabs>

    @php
        $dateFrom = $this->filterData['date_from'] ?? null;
        $dateTo = $this->filterData['date_to'] ?? null;
        $isCustom = ($this->filterData['preset'] ?? '') === 'custom';
        $activeFilters = $this->activeFilters ?? [];
        $filtersHash = md5(json_encode($activeFilters));
    @endphp

    <div class="space-y-6">
        <div class="flex justify-end items-center">
            <div class="w-full {{ $isCustom ? 'max-w-xl' : 'max-w-[200px]' }}">
                <form>
                    {{ $this->form }}
                </form>
            </div>
        </div>

        @if (! empty($activeFilters))
            <div class="flex flex-wrap items-center gap-2 p-3 bg-gray-50/50 dark:bg-white/5 border border-gray-100 dark:border-white/10 rounded-xl">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mr-1">Active Filters:</span>
                @foreach ($activeFilters as $key => $value)
                    @php
                        $displayValue = $value;
                        if ($key === 'country_code') {
                            $translatedCountry = __('filament-short-url::countries.' . strtoupper($value));
                            if ($translatedCountry && $translatedCountry !== 'filament-short-url::countries.' . strtoupper($value)) {
                                $displayValue = $translatedCountry;
                            }
                        }
                    @endphp
                    <div class="flex items-center gap-1.5 px-3 py-1 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 rounded-full text-xs font-semibold text-indigo-700 dark:text-indigo-300">
                        <span class="opacity-75 uppercase text-[10px] tracking-wider">{{ $key === 'country_code' ? 'country' : str_replace('_', ' ', $key) }}:</span>
                        <span>{{ $displayValue }}</span>
                        <button type="button" 
                                wire:click="clearStatsFilter('{{ $key }}')" 
                                class="hover:text-indigo-900 dark:hover:text-indigo-100 transition-colors focus:outline-none ml-1">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endforeach
                <button type="button" 
                        wire:click="clearAllStatsFilters" 
                        class="text-xs font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors focus:outline-none ml-auto px-2.5 py-1 rounded-md hover:bg-gray-100 dark:hover:bg-white/5">
                    Clear all filters
                </button>
            </div>
        @endif

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlStatsOverview::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => $activeFilters,
        ], key('stats-overview-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsChart::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => $activeFilters,
        ], key('stats-chart-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGeoBreakdownWidget::class, [
                'record' => $record,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'filters' => $activeFilters,
            ], key('stats-geo-breakdown-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))

            @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlDeviceBreakdownWidget::class, [
                'record' => $record,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'filters' => $activeFilters,
            ], key('stats-device-breakdown-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))
        </div>

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlWorldMapWidget::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => $activeFilters,
        ], key('stats-world-map-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlSourcesBreakdownWidget::class, [
                'record' => $record,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'filters' => $activeFilters,
            ], key('stats-sources-breakdown-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))

            @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlSecurityBreakdownWidget::class, [
                'record' => $record,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'filters' => $activeFilters,
            ], key('stats-security-breakdown-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))
        </div>

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVariantsWidget::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => $activeFilters,
        ], key('stats-variants-' . $dateFrom . '-' . $dateTo . '-' . $filtersHash))
    </div>
</x-filament-panels::page>

