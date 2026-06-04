<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

        {{-- Countries --}}
        <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
            <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5" />
                </div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_countries') }}</h3>
            </div>
            <div class="space-y-3">
                @forelse ($visitsByCountry as $country => $count)
                    @php 
                        $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0;
                        $translatedCountry = \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsRightBreakdown::getCountryTranslation($country);
                        $code = \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsRightBreakdown::getCountryCode($country);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300">
                                @if ($code)
                                    <img src="https://flagcdn.com/h20/{{ strtolower($code) }}.webp" class="w-5 h-auto rounded-sm inline-block" alt="{{ $translatedCountry }}" />
                                @endif
                                <span>{{ $translatedCountry }}</span>
                            </span>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">{{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span></span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-indigo-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_country_data') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Languages --}}
        <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
            <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 dark:bg-indigo-950/50 dark:text-indigo-400">
                    <x-filament::icon icon="heroicon-o-language" class="h-5 w-5" />
                </div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_languages') }}</h3>
            </div>
            <div class="space-y-3">
                @forelse ($visitsByLanguage as $langCode => $count)
                    @php 
                        $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; 
                        $langName = \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlVisitsRightBreakdown::getLanguageTranslation($langCode);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $langName }} ({{ strtoupper($langCode) }})</span>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">{{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span></span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-indigo-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_language_data') }}</p>
                @endforelse
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
