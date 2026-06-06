<x-filament-panels::page>
    <x-filament::tabs class="mb-6">
        <x-filament::tabs.item
            tag="a"
            href="{{ \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource::getUrl('stats', ['record' => $record]) }}"
            icon="heroicon-m-presentation-chart-line"
        >
            {{ __('filament-short-url::default.stats_tab_statistics') }}
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="true"
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

    <div class="mt-2">
        @livewire(\Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlLiveFeedWidget::class, [
            'record' => $record,
            'dateFrom' => null,
            'dateTo' => null,
            'filters' => [],
        ], key('live-feed-page-' . $record->id))
    </div>
</x-filament-panels::page>
