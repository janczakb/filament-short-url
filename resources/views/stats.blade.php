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

    <div class="space-y-6">
        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlStatsOverview::class, ['record' => $record])

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsChart::class, ['record' => $record])
            </div>
            <div>
                @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsRightBreakdown::class, ['record' => $record])
            </div>
        </div>

        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsBottomBreakdown::class, ['record' => $record])
    </div>
</x-filament-panels::page>
