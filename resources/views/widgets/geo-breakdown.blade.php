<x-filament-widgets::widget>
    <div class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900 overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 px-6">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button type="button" 
                        wire:click="setActiveTab('countries')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'countries' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_countries') }}
                </button>
                <button type="button" 
                        wire:click="setActiveTab('cities')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'cities' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_cities') }}
                </button>
                <button type="button" 
                        wire:click="setActiveTab('languages')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'languages' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_languages') }}
                </button>
            </nav>
            
        </div>

        <!-- Body -->
        <div class="relative p-6 min-h-[280px]">
            <!-- Spinner -->
            <div wire:loading class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[1px] z-10 transition-opacity">
                <div class="flex items-center justify-center h-full w-full">
                    <x-filament::loading-indicator class="h-7 w-7 text-gray-900 dark:text-white" />
                </div>
            </div>

            <!-- Tab Contents -->
            <div wire:loading.class="opacity-40 pointer-events-none transition-opacity">
                <!-- Countries List -->
                @if ($activeTab === 'countries')
                    <div class="space-y-3">
                        @forelse ($visitsByCountry as $code => $count)
                            @php 
                                $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0;
                                $translatedCountry = __('filament-short-url::countries.' . strtoupper($code));
                                if (!$translatedCountry || $translatedCountry === 'filament-short-url::countries.' . strtoupper($code)) {
                                    $translatedCountry = strtoupper($code);
                                }
                            @endphp
                            <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors" x-on:click="$wire.dispatch('set-stats-filter', { key: 'country_code', value: @json($code) })">
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
                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('filament-short-url::default.stats_no_country_data') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif

                <!-- Cities List -->
                @if ($activeTab === 'cities')
                    <div class="space-y-3.5">
                        @forelse ($visitsByCity as $city => $count)
                            @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                            <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors" x-on:click="$wire.dispatch('set-stats-filter', { key: 'city', value: @json($city) })">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $city }}</span>
                                    <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">{{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span></span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('filament-short-url::default.stats_no_city_data') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif

                <!-- Languages List -->
                @if ($activeTab === 'languages')
                    <div class="space-y-3">
                        @forelse ($visitsByLanguage as $langCode => $count)
                            @php 
                                $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; 
                                $langName = \Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlLanguagesWidget::getLanguageTranslation($langCode);
                            @endphp
                            <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors" x-on:click="$wire.dispatch('set-stats-filter', { key: 'browser_language', value: @json($langCode) })">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $langName }} ({{ strtoupper($langCode) }})</span>
                                    <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">{{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span></span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-indigo-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('filament-short-url::default.stats_no_language_data') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
