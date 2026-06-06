<x-filament-widgets::widget>
    {{--
        wire:poll calls checkForUpdates() instead of blindly re-rendering.
        checkForUpdates() does a single MAX(id) query; if nothing changed it
        calls skipRender() and Livewire returns a ~100-byte "no diff" response.
        Only when a new visit is detected does the full render happen.
        .visible stops polling entirely when the widget is scrolled out of view.
    --}}
    <div wire:poll.5s.visible="checkForUpdates"
         class="fi-section rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900 overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-white/10 bg-gray-50/50 dark:bg-white/5 px-6 py-4">
            <div class="flex items-center gap-2">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ __('filament-short-url::default.stats_tab_live_feed') }}
                </h3>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">
                {{ __('filament-short-url::default.stats_live_feed_poll_interval') }}
            </span>
        </div>

        <!-- Body -->
        <div class="p-6 relative min-h-[300px]">
            <!-- Spinner shown only during re-renders that actually happen -->
            <div wire:loading class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[1px] z-10 transition-opacity">
                <div class="flex items-center justify-center h-full w-full">
                    <x-filament::loading-indicator class="h-7 w-7 text-gray-900 dark:text-white" />
                </div>
            </div>

            <div class="space-y-4" wire:loading.class="opacity-40 pointer-events-none transition-opacity">
                @forelse ($visits as $visit)
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 border border-gray-100 dark:border-white/5 rounded-xl hover:bg-gray-50/50 dark:hover:bg-white/5 transition-all">

                        <!-- Visitor & Location Info -->
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            {{-- Flag from flagcdn.com. Alpine handles broken image gracefully without
                                 inline onerror JS (which can violate CSP nonce policies). --}}
                            <div class="mt-0.5 shrink-0 w-6 flex items-center" title="{{ $visit['country'] }}">
                                @if ($visit['flag_url'])
                                    <span x-data="{ error: false }">
                                        <img x-show="!error"
                                             x-on:error="error = true"
                                             src="{{ $visit['flag_url'] }}"
                                             width="20"
                                             height="15"
                                             alt="{{ $visit['country_code'] }}"
                                             class="rounded-[2px] object-cover shadow-sm">
                                        <span x-show="error" class="text-base leading-none">🌐</span>
                                    </span>
                                @else
                                    <span class="text-base leading-none">🌐</span>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <button type="button"
                                            x-on:click="$wire.dispatch('set-stats-filter', { key: 'country_code', value: '{{ $visit['country_code'] }}' })"
                                            class="text-sm font-semibold text-gray-950 dark:text-white hover:underline truncate">
                                        {{ $visit['city'] ?: 'Unknown City' }}, {{ $visit['country'] ?: 'Unknown Country' }}
                                    </button>

                                    @if ($visit['is_qr_scan'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-100 dark:border-purple-900/40">
                                            <x-filament::icon icon="heroicon-m-qr-code" class="w-3.5 h-3.5" />
                                            QR Scan
                                        </span>
                                    @endif

                                    @if ($visit['selected_variant'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-100 dark:border-amber-900/40">
                                            Variant: {{ $visit['selected_variant'] }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-2">
                                    {{-- time_ago precomputed in PHP — no Carbon::parse() per-row in Blade --}}
                                    <span>{{ $visit['time_ago'] }}</span>
                                    <span>•</span>
                                    <span class="font-mono text-[11px]">{{ $visit['ip_address'] ?: '—' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Referrer -->
                        <div class="flex items-center gap-2 min-w-[150px] max-w-[220px]">
                            <x-filament::icon icon="heroicon-m-link" class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0" />
                            <span class="text-xs font-medium text-gray-600 dark:text-gray-400 truncate"
                                  title="{{ $visit['referer_url'] }}">
                                {{ $visit['referer_host'] ?: __('filament-short-url::default.stats_referer_direct') }}
                            </span>
                        </div>

                        <!-- Device / Browser / OS filters -->
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Device -->
                            <button type="button"
                                    x-on:click="$wire.dispatch('set-stats-filter', { key: 'device_type', value: '{{ $visit['device_type'] }}' })"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                    title="Filter by device: {{ $visit['device_type'] }}">
                                @if (strtolower($visit['device_type'] ?? '') === 'desktop')
                                    <x-filament::icon icon="heroicon-m-computer-desktop" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                                @elseif (strtolower($visit['device_type'] ?? '') === 'mobile')
                                    <x-filament::icon icon="heroicon-m-device-phone-mobile" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                                @elseif (strtolower($visit['device_type'] ?? '') === 'tablet')
                                    <x-filament::icon icon="heroicon-m-device-tablet" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                                @else
                                    <x-filament::icon icon="heroicon-m-question-mark-circle" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                                @endif
                                <span class="capitalize">{{ $visit['device_type'] ?: 'Unknown' }}</span>
                            </button>

                            <!-- Browser -->
                            <button type="button"
                                    x-on:click="$wire.dispatch('set-stats-filter', { key: 'browser', value: '{{ $visit['browser'] }}' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                    title="Filter by browser: {{ $visit['browser'] }}">
                                <x-filament::icon icon="heroicon-m-globe-alt" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                                <span>{{ $visit['browser'] ?: 'Browser' }}</span>
                            </button>

                            <!-- OS -->
                            <button type="button"
                                    x-on:click="$wire.dispatch('set-stats-filter', { key: 'operating_system', value: '{{ $visit['operating_system'] }}' })"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-white/5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                    title="Filter by OS: {{ $visit['operating_system'] }}">
                                <x-filament::icon icon="heroicon-m-cpu-chip" class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" />
                                <span>{{ $visit['operating_system'] ?: 'OS' }}</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                            <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-6 w-6" />
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('filament-short-url::default.stats_live_feed_empty') }}
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
