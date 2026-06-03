@php
    $matchedAppId = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::matchApp($destinationUrl);
    $apps = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::getSupportedApps();
@endphp

<div class="mt-4 space-y-4">
    <!-- macOS style main block -->
    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 shadow-none">
        
        <!-- Header Info -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 mb-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h4 class="text-xs font-bold text-gray-850 dark:text-white uppercase tracking-wider">
                    {{ __('filament-short-url::default.app_linking_supported_apps') }}
                </h4>
            </div>
            
            <div class="flex items-center gap-2 text-[10px]">
                <span class="text-gray-500 dark:text-gray-450 font-medium">
                    {{ __('filament-short-url::default.app_linking_supported_os') }}
                </span>
                <span class="px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-800 text-gray-650 dark:text-gray-300 font-semibold">iOS</span>
                <span class="px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-800 text-gray-650 dark:text-gray-300 font-semibold">Android</span>
            </div>
        </div>

        <!-- Matched App Banner -->
        @if ($matchedAppId && isset($apps[$matchedAppId]))
            @php
                $matchedApp = $apps[$matchedAppId];
                $matchedDomain = explode('/', $matchedApp['domains'][0])[0];
                $matchedFavicon = "https://icons.duckduckgo.com/ip2/{$matchedDomain}.ico";
                $deepLink = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::convertToScheme($destinationUrl, $matchedAppId);
            @endphp
            <div class="mb-4 p-4 rounded-xl bg-emerald-500/5 dark:bg-emerald-500/5 border border-emerald-500 dark:border-emerald-600 text-xs shadow-none">
                <div class="flex items-start gap-4">
                    <!-- Squircle Favicon Box -->
                    <div class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center p-2 flex-shrink-0">
                        <img src="{{ $matchedFavicon }}" alt="{{ $matchedApp['name'] }}" class="w-6 h-6 object-contain" onerror="this.src='https://icons.duckduckgo.com/ip2/google.com.ico'">
                    </div>
                    
                    <div class="flex-grow min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h5 class="text-xs font-bold text-emerald-850 dark:text-emerald-450">
                                {{ __('filament-short-url::default.app_linking_redirect_active', ['app' => $matchedApp['name']]) }}
                            </h5>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500 text-white uppercase tracking-wider">
                                {{ __('filament-short-url::default.app_linking_auto_open') }}
                            </span>
                        </div>
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-450 mt-1 leading-relaxed">
                            {!! __('filament-short-url::default.app_linking_matched_description', ['app' => e($matchedApp['name'])]) !!}
                        </p>
                        <div class="mt-2 text-[10px] font-mono text-emerald-600 dark:text-emerald-400 break-all select-all">
                            {{ __('filament-short-url::default.app_linking_deep_link_scheme', ['scheme' => $deepLink]) }}
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Standard Web Redirect banner -->
            <div class="mb-4 p-3.5 rounded-xl bg-gray-100/50 dark:bg-gray-800/10 border border-gray-200 dark:border-gray-800 text-xs flex items-center gap-3 shadow-none">
                <div class="w-7 h-7 rounded-[6px] bg-white dark:bg-gray-800 flex items-center justify-center p-1.5 flex-shrink-0">
                    <svg class="w-4 h-4 text-gray-450" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-gray-500 dark:text-gray-450 leading-relaxed text-[11px]">
                    {{ __('filament-short-url::default.app_linking_standard_redirect') }}
                </span>
            </div>
        @endif

        <!-- Flat iOS-like Grid without Borders -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mb-4">
            @foreach ($apps as $appId => $app)
                @php
                    $isMatched = ($appId === $matchedAppId);
                    $appDomain = explode('/', $app['domains'][0])[0];
                    $appFavicon = "https://icons.duckduckgo.com/ip2/{$appDomain}.ico";
                @endphp
                <div class="flex items-center gap-2.5 p-1.5 rounded-lg transition {{ $isMatched ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-400 font-bold' : 'hover:bg-gray-100/60 dark:hover:bg-gray-800/50 text-gray-700 dark:text-gray-300' }}">
                    <!-- Favicon Squircle -->
                    <div class="w-7 h-7 rounded-[6px] bg-white dark:bg-gray-800 flex items-center justify-center p-1.5 flex-shrink-0">
                        <img src="{{ $appFavicon }}" alt="{{ $app['name'] }}" class="w-4 h-4 object-contain" onerror="this.src='https://icons.duckduckgo.com/ip2/google.com.ico'">
                    </div>
                    <span class="text-xs font-semibold truncate">
                        {{ $app['name'] }}
                    </span>
                    @if ($isMatched)
                        <span class="ml-auto flex h-3.5 w-3.5 items-center justify-center rounded-full bg-emerald-500 text-white text-[8px] font-bold shrink-0">
                            ✓
                        </span>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Help footer -->
        <p class="text-[11px] text-gray-450 dark:text-gray-500 border-t border-gray-200 dark:border-gray-800 pt-3 leading-relaxed">
            {{ __('filament-short-url::default.app_linking_supported_apps_helper') }}
        </p>

    </div>
</div>
