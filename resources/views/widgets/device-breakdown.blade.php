<x-filament-widgets::widget>
    <div class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900 overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 px-6">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button type="button" 
                        wire:click="setActiveTab('devices')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'devices' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_devices') }}
                </button>
                <button type="button" 
                        wire:click="setActiveTab('browsers')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'browsers' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_browsers') }}
                </button>
                <button type="button" 
                        wire:click="setActiveTab('os')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'os' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_breakdown_os') }}
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
                <!-- Devices List -->
                @if ($activeTab === 'devices')
                    <div class="space-y-3.5">
                        @forelse ($visitsByDevice as $device => $count)
                            @php 
                                $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; 
                                $deviceLabel = match (strtolower($device)) {
                                    'desktop' => __('filament-short-url::default.stats_device_desktop'),
                                    'mobile' => __('filament-short-url::default.stats_device_mobile'),
                                    'tablet' => __('filament-short-url::default.stats_device_tablet'),
                                    default => ucfirst($device),
                                };
                            @endphp
                            <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors" x-on:click="$wire.dispatch('set-stats-filter', { key: 'device_type', value: '{{ addslashes($device) }}' })">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300">
                                        @if (strtolower($device) === 'desktop')
                                            <x-filament::icon icon="heroicon-m-computer-desktop" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                        @elseif (strtolower($device) === 'mobile')
                                            <x-filament::icon icon="heroicon-m-device-phone-mobile" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                        @elseif (strtolower($device) === 'tablet')
                                            <x-filament::icon icon="heroicon-m-device-tablet" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                        @else
                                            <x-filament::icon icon="heroicon-m-question-mark-circle" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                        @endif
                                        <span>{{ $deviceLabel }}</span>
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
                                    {{ __('filament-short-url::default.stats_no_device_data') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif

                <!-- Browsers List -->
                @if ($activeTab === 'browsers')
                    <div class="space-y-3.5">
                        @forelse ($visitsByBrowser as $browser => $count)
                            @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                            <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors flex items-center justify-between text-sm" x-on:click="$wire.dispatch('set-stats-filter', { key: 'browser', value: '{{ addslashes($browser) }}' })">
                                <div class="flex flex-col min-w-0">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon :icon="$browserIcons[$browser] ?? 'heroicon-m-globe-alt'" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                                        <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $browser }}</span>
                                    </div>
                                    @if (!empty($visitsByBrowserVersion[$browser]))
                                        @php
                                            $versionsList = [];
                                            foreach ($visitsByBrowserVersion[$browser] as $ver => $vCount) {
                                                $versionsList[] = "v{$ver} ({$vCount})";
                                            }
                                        @endphp
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 ml-6 truncate">
                                            {{ implode(', ', $versionsList) }}
                                        </span>
                                    @endif
                                </div>
                                <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0 ml-2">
                                    {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                                </span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('filament-short-url::default.stats_no_browser_data') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif

                <!-- OS List -->
                @if ($activeTab === 'os')
                    <div class="space-y-3.5">
                        @forelse ($visitsByOs as $os => $count)
                            @php $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0; @endphp
                            <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors flex items-center justify-between text-sm" x-on:click="$wire.dispatch('set-stats-filter', { key: 'operating_system', value: '{{ addslashes($os) }}' })">
                                <div class="flex flex-col min-w-0">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon :icon="$osIcons[$os] ?? 'heroicon-m-cpu-chip'" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                                        <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $os }}</span>
                                    </div>
                                    @if (!empty($visitsByOsVersion[$os]))
                                        @php
                                            $versionsList = [];
                                            foreach ($visitsByOsVersion[$os] as $ver => $vCount) {
                                                $versionsList[] = "v{$ver} ({$vCount})";
                                            }
                                        @endphp
                                        <span class="text-[10px] text-gray-400 dark:text-gray-500 ml-6 truncate">
                                            {{ implode(', ', $versionsList) }}
                                        </span>
                                    @endif
                                </div>
                                <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0 ml-2">
                                    {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                                </span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('filament-short-url::default.stats_no_os_data') }}
                                </p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
