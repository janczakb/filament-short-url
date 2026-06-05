<x-filament-widgets::widget>
    <div class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900 overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 px-6">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button type="button" 
                        wire:click="setActiveTab('referrers')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'referrers' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_referers') }}
                </button>
                <button type="button" 
                        wire:click="setActiveTab('utm')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'utm' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    UTM Parameters
                </button>
            </nav>
        </div>

        <!-- Submenu Header (only for UTM) -->
        @if ($activeTab === 'utm')
            <div class="flex items-center gap-1.5 border-b border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 px-6 py-2.5">
                <button type="button" 
                        wire:click="setActiveSubTab('sources')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all focus:outline-none {{ $activeSubTab === 'sources' ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_utm_source') }}
                </button>
                <button type="button" 
                        wire:click="setActiveSubTab('mediums')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all focus:outline-none {{ $activeSubTab === 'mediums' ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_utm_medium') }}
                </button>
                <button type="button" 
                        wire:click="setActiveSubTab('campaigns')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all focus:outline-none {{ $activeSubTab === 'campaigns' ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_utm_campaign') }}
                </button>
                <button type="button" 
                        wire:click="setActiveSubTab('terms')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all focus:outline-none {{ $activeSubTab === 'terms' ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_utm_term') }}
                </button>
                <button type="button" 
                        wire:click="setActiveSubTab('contents')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-all focus:outline-none {{ $activeSubTab === 'contents' ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'bg-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_utm_content') }}
                </button>
            </div>
        @endif

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
                <!-- Referrers Tab -->
                @if ($activeTab === 'referrers')
                    <div class="space-y-3.5">
                        @forelse ($visitsByReferer as $referer => $count)
                            @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                            <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors" x-on:click="$wire.dispatch('set-stats-filter', { key: 'referer_host', value: '{{ addslashes($referer) }}' })">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $referer }}</span>
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
                                    {{ __('filament-short-url::default.stats_no_referer_data') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif

                <!-- UTM Parameters Tab -->
                @if ($activeTab === 'utm')
                    <div class="space-y-4">
                        <!-- Sub-tab Content -->
                        <div>
                            @if ($activeSubTab === 'sources')
                                <div class="space-y-3.5 mt-2">
                                    @forelse ($utmSources as $source => $count)
                                        @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                                        <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1 rounded-lg transition-colors flex items-center justify-between text-sm" x-on:click="$wire.dispatch('set-stats-filter', { key: 'utm_source', value: '{{ addslashes($source) }}' })">
                                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $source }}</span>
                                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center py-6 text-center">
                                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('filament-short-url::default.stats_no_utm_data') }}
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            @elseif ($activeSubTab === 'mediums')
                                <div class="space-y-3.5 mt-2">
                                    @forelse ($utmMediums as $medium => $count)
                                        @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                                        <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1 rounded-lg transition-colors flex items-center justify-between text-sm" x-on:click="$wire.dispatch('set-stats-filter', { key: 'utm_medium', value: '{{ addslashes($medium) }}' })">
                                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $medium }}</span>
                                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center py-6 text-center">
                                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('filament-short-url::default.stats_no_utm_data') }}
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            @elseif ($activeSubTab === 'campaigns')
                                <div class="space-y-3.5 mt-2">
                                    @forelse ($utmCampaigns as $campaign => $count)
                                        @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                                        <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1 rounded-lg transition-colors flex items-center justify-between text-sm" x-on:click="$wire.dispatch('set-stats-filter', { key: 'utm_campaign', value: '{{ addslashes($campaign) }}' })">
                                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $campaign }}</span>
                                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center py-6 text-center">
                                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('filament-short-url::default.stats_no_utm_data') }}
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            @elseif ($activeSubTab === 'terms')
                                <div class="space-y-3.5 mt-2">
                                    @forelse ($utmTerms as $term => $count)
                                        @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                                        <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1 rounded-lg transition-colors flex items-center justify-between text-sm" x-on:click="$wire.dispatch('set-stats-filter', { key: 'utm_term', value: '{{ addslashes($term) }}' })">
                                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $term }}</span>
                                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center py-6 text-center">
                                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('filament-short-url::default.stats_no_utm_data') }}
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            @elseif ($activeSubTab === 'contents')
                                <div class="space-y-3.5 mt-2">
                                    @forelse ($utmContents as $content => $count)
                                        @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                                        <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1 rounded-lg transition-colors flex items-center justify-between text-sm" x-on:click="$wire.dispatch('set-stats-filter', { key: 'utm_content', value: '{{ addslashes($content) }}' })">
                                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $content }}</span>
                                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                                            </span>
                                        </div>
                                    @empty
                                        <div class="flex flex-col items-center justify-center py-6 text-center">
                                            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('filament-short-url::default.stats_no_utm_data') }}
                                            </p>
                                        </div>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
