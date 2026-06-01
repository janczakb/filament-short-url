@php
    $deviceIcons = [
        'desktop' => 'heroicon-m-computer-desktop',
        'mobile'  => 'heroicon-m-device-phone-mobile',
        'tablet'  => 'heroicon-m-device-tablet',
        'robot'   => 'heroicon-m-cpu-chip',
    ];

    $browserIcons = [
        'Chrome'            => 'heroicon-m-globe-alt',
        'Firefox'           => 'heroicon-m-globe-americas',
        'Safari'            => 'heroicon-m-compass',
        'Edge'              => 'heroicon-m-globe-asia-australia',
        'Opera'             => 'heroicon-m-bolt',
        'Internet Explorer' => 'heroicon-m-wrench',
        'Samsung Browser'   => 'heroicon-m-device-phone-mobile',
    ];

    $osIcons = [
        'Windows'   => 'heroicon-m-computer-desktop',
        'OS X'      => 'heroicon-m-computer-desktop',
        'macOS'     => 'heroicon-m-computer-desktop',
        'iOS'       => 'heroicon-m-device-phone-mobile',
        'Android'   => 'heroicon-m-device-phone-mobile',
        'Linux'     => 'heroicon-m-cpu-chip',
    ];
@endphp

<x-filament-widgets::widget>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Devices --}}
            <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-500 dark:bg-emerald-950/50 dark:text-emerald-400">
                        <x-filament::icon icon="heroicon-o-device-phone-mobile" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_devices') }}</h3>
                </div>
                <div class="space-y-4">
                    @forelse ($visitsByDevice as $device => $count)
                        @php $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; @endphp
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                <x-filament::icon :icon="$deviceIcons[$device] ?? 'heroicon-m-question-mark-circle'" class="h-4 w-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between text-sm">
                                    <span class="capitalize font-medium text-gray-700 dark:text-gray-300 truncate">{{ $device }}</span>
                                    <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">{{ $pct }}%</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_device_data') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Browsers --}}
            <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-500 dark:bg-amber-950/50 dark:text-amber-400">
                        <x-filament::icon icon="heroicon-o-globe-asia-australia" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_browsers') }}</h3>
                </div>
                <div class="space-y-3.5">
                    @forelse ($visitsByBrowser as $browser => $count)
                        @php $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; @endphp
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <x-filament::icon :icon="$browserIcons[$browser] ?? 'heroicon-m-globe-alt'" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                                <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $browser }}</span>
                            </div>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0 ml-2">
                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_browser_data') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Operating Systems --}}
            <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-950/50 dark:text-blue-400">
                        <x-filament::icon icon="heroicon-o-computer-desktop" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_os') }}</h3>
                </div>
                <div class="space-y-3.5">
                    @forelse ($visitsByOs as $os => $count)
                        @php $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; @endphp
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 min-w-0">
                                <x-filament::icon :icon="$osIcons[$os] ?? 'heroicon-m-computer-desktop'" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                                <span class="font-medium text-gray-700 dark:text-gray-300 truncate">{{ $os }}</span>
                            </div>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0 ml-2">
                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_os_data') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Referers --}}
            <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-500 dark:bg-purple-950/50 dark:text-purple-400">
                        <x-filament::icon icon="heroicon-o-link" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_referers') }}</h3>
                </div>
                <div class="space-y-3">
                    @forelse ($visitsByReferer as $referer => $count)
                        @php $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; @endphp
                        <div class="flex items-center justify-between text-sm gap-4">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <x-filament::icon icon="heroicon-m-link" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                                @if ($referer === 'Direct')
                                    <span class="font-medium text-gray-500 dark:text-gray-400 truncate">{{ $referer }}</span>
                                @else
                                    <a href="https://{{ $referer }}" target="_blank" rel="noopener"
                                       class="truncate text-indigo-600 hover:underline dark:text-indigo-400 font-medium">
                                        {{ $referer }}
                                    </a>
                                @endif
                            </div>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0 ml-2">
                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_referer_data') }}</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- UTM Campaigns Section --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

            {{-- UTM Sources --}}
            <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-500 dark:bg-rose-950/50 dark:text-rose-400">
                        <x-filament::icon icon="heroicon-o-megaphone" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_utm_source') }}</h3>
                </div>
                <div class="space-y-3.5">
                    @forelse ($utmSources as $source => $count)
                        @php $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $source }}</span>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_utm_data') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- UTM Mediums --}}
            <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-500 dark:bg-teal-950/50 dark:text-teal-400">
                        <x-filament::icon icon="heroicon-o-tag" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_utm_medium') }}</h3>
                </div>
                <div class="space-y-3.5">
                    @forelse ($utmMediums as $medium => $count)
                        @php $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $medium }}</span>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_utm_data') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- UTM Campaigns --}}
            <div class="fi-wi-stats-breakdown-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-md dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex items-center gap-3 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-50 text-cyan-500 dark:bg-cyan-950/50 dark:text-cyan-400">
                        <x-filament::icon icon="heroicon-o-flag" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('filament-short-url::default.stats_breakdown_utm_campaign') }}</h3>
                </div>
                <div class="space-y-3.5">
                    @forelse ($utmCampaigns as $campaign => $count)
                        @php $pct = $totalVisits > 0 ? round($count / $totalVisits * 100) : 0; @endphp
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ $campaign }}</span>
                            <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white shrink-0">
                                {{ number_format($count) }} <span class="text-gray-400 dark:text-gray-500">({{ $pct }}%)</span>
                            </span>
                        </div>
                    @empty
                        <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('filament-short-url::default.stats_no_utm_data') }}</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
