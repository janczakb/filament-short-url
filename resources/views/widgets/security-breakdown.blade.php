<x-filament-widgets::widget>
    <div class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900 overflow-hidden">
        <!-- Tabs Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 px-6">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <button type="button" 
                        wire:click="setActiveTab('bots')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'bots' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_security_bot_ratio') }}
                </button>
                <button type="button" 
                        wire:click="setActiveTab('vpn')"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-semibold focus:outline-none transition-colors {{ $activeTab === 'vpn' ? 'border-gray-900 text-gray-900 dark:border-white dark:text-white' : 'border-transparent text-gray-400 hover:text-gray-700 dark:text-gray-500 dark:hover:text-gray-300' }}">
                    {{ __('filament-short-url::default.stats_security_vpn_blocked') }}
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
                <!-- Bot Clicks Tab -->
                @if ($activeTab === 'bots')
                    <div class="space-y-4">
                        {{-- Human Clicks --}}
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300">
                                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <span>{{ __('filament-short-url::default.stats_security_real_users') }}</span>
                                </span>
                                <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($humanClicks) }} 
                                    <span class="text-gray-400 dark:text-gray-500">({{ $humanPercentage }}%)</span>
                                </span>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $humanPercentage }}%"></div>
                            </div>
                        </div>

                        {{-- Bot Clicks --}}
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300">
                                    <span class="inline-block h-2 w-2 rounded-full bg-purple-500"></span>
                                    <span>{{ __('filament-short-url::default.stats_security_bots') }}</span>
                                </span>
                                <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($botClicks) }} 
                                    <span class="text-gray-400 dark:text-gray-500">({{ $botPercentage }}%)</span>
                                </span>
                            </div>
                            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-purple-500 transition-all duration-500" style="width: {{ $botPercentage }}%"></div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- VPN / Proxy Blocked Tab -->
                @if ($activeTab === 'vpn')
                    <div class="mt-2">
                        @if ($proxyClicks === 0)
                            <div class="flex flex-col items-center justify-center py-6 text-center">
                                <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-500 dark:bg-emerald-950/30 dark:text-emerald-400">
                                    <x-filament::icon icon="heroicon-o-check-circle" class="h-6 w-6" />
                                </div>
                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                    {{ __('filament-short-url::default.stats_security_no_vpn_blocks') }}
                                </p>
                            </div>
                        @else
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 font-mono">
                                        {{ number_format($proxyClicks) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('filament-short-url::default.stats_security_proxy_clicks') }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-rose-50 px-3 py-2 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold">
                                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4" />
                                        <span>VPN / Proxy Traffic</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-full rounded-full bg-rose-500 transition-all duration-500" style="width: {{ $proxyPercentage }}%"></div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
