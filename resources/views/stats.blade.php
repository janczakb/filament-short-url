<x-filament-panels::page>
    <x-filament::tabs class="mb-6">
        <x-filament::tabs.item
            :active="true"
            icon="heroicon-m-presentation-chart-line"
        >
            {{ __('filament-short-url::default.stats_tab_statistics') }}
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
    @endphp

    <div class="space-y-6">
        <div class="flex justify-end items-center">
            <div class="w-full {{ $isCustom ? 'max-w-xl' : 'max-w-[200px]' }}">
                <form>
                    {{ $this->form }}
                </form>
            </div>
        </div>

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlStatsOverview::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], key('stats-overview-' . $dateFrom . '-' . $dateTo))

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsChart::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], key('stats-chart-' . $dateFrom . '-' . $dateTo))

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsRightBreakdown::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], key('stats-right-breakdown-' . $dateFrom . '-' . $dateTo))

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlWorldMapWidget::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], key('stats-world-map-' . $dateFrom . '-' . $dateTo))

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsBottomBreakdown::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], key('stats-bottom-breakdown-' . $dateFrom . '-' . $dateTo))
    </div>
</x-filament-panels::page>
