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
    @endphp

    <div class="space-y-6">
        <div class="p-6 bg-white rounded-xl border border-gray-200 dark:bg-gray-900 dark:border-gray-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('filament-short-url::default.stats_filter_date_range') }}
                </h3>
                <div class="flex-1 max-w-lg">
                    <form class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        {{ $this->form }}
                    </form>
                </div>
            </div>
        </div>

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlStatsOverview::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], key('stats-overview-' . $dateFrom . '-' . $dateTo))

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsChart::class, [
                    'record' => $record,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                ], key('stats-chart-' . $dateFrom . '-' . $dateTo))
            </div>
            <div>
                @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsRightBreakdown::class, [
                    'record' => $record,
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                ], key('stats-right-breakdown-' . $dateFrom . '-' . $dateTo))
            </div>
        </div>

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsBottomBreakdown::class, [
            'record' => $record,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], key('stats-bottom-breakdown-' . $dateFrom . '-' . $dateTo))
    </div>
</x-filament-panels::page>
