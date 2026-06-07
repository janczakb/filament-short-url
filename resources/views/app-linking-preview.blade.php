@php
    $matchedAppId = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::matchApp($destinationUrl);
    $apps = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::getSupportedApps();
@endphp

<div class="mt-4 space-y-3">

    {{-- Matched App Banner --}}
    @if ($matchedAppId && isset($apps[$matchedAppId]))
        @php
            $matchedApp = $apps[$matchedAppId];
            $matchedDomain = explode('/', $matchedApp['domains'][0])[0];
            $matchedFavicon = "https://icons.duckduckgo.com/ip2/{$matchedDomain}.ico";
            $deepLink = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::convertToScheme($destinationUrl, $matchedAppId);
        @endphp
        <div class="p-3.5 rounded-xl bg-emerald-500/8 border border-emerald-500/40 dark:border-emerald-500/30 dark:bg-emerald-500/5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 rounded-[10px] bg-white dark:bg-neutral-800 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-center shadow-sm flex-shrink-0">
                    <img src="{{ $matchedFavicon }}" alt="{{ $matchedApp['name'] }}" class="w-5 h-5 object-contain" onerror="this.src='https://icons.duckduckgo.com/ip2/google.com.ico'">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 truncate">{{ $matchedApp['name'] }}</span>
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-emerald-500 text-white text-[9px] font-bold uppercase tracking-wider shrink-0">
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            {{ __('filament-short-url::default.app_linking_auto_open') }}
                        </span>
                    </div>
                    <p class="text-[10px] text-emerald-600/80 dark:text-emerald-500 mt-0.5 leading-snug">
                        {!! __('filament-short-url::default.app_linking_matched_description', ['app' => e($matchedApp['name'])]) !!}
                    </p>
                </div>
            </div>
            <div class="font-mono text-[10px] text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg px-2.5 py-1.5 break-all select-all border border-emerald-100 dark:border-emerald-900/30">
                {{ $deepLink }}
            </div>
        </div>
    @else
        {{-- Standard Web Redirect notice --}}
        <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-neutral-100/80 dark:bg-neutral-800/30 border border-neutral-200 dark:border-neutral-700/50">
            <div class="w-7 h-7 rounded-lg bg-white dark:bg-neutral-800 flex items-center justify-center flex-shrink-0 border border-neutral-200 dark:border-neutral-700">
                <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                </svg>
            </div>
            <span class="text-[11px] text-neutral-500 dark:text-neutral-400 leading-snug">
                {{ __('filament-short-url::default.app_linking_standard_redirect') }}
            </span>
        </div>
    @endif

    {{-- Supported Apps Section --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900/50 overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-neutral-100 dark:border-neutral-800">
            <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">
                {{ __('filament-short-url::default.app_linking_supported_apps') }}
            </span>
            <div class="flex items-center gap-1">
                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 tracking-wide">iOS</span>
                <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 tracking-wide">Android</span>
            </div>
        </div>

        {{-- iOS-style App Grid --}}
        <div class="p-2 grid grid-cols-4 gap-1">
            @foreach ($apps as $appId => $app)
                @php
                    $isMatched = ($appId === $matchedAppId);
                    $appDomain = explode('/', $app['domains'][0])[0];
                    $appFavicon = "https://icons.duckduckgo.com/ip2/{$appDomain}.ico";
                @endphp
                <div class="flex flex-col items-center gap-1 py-2 px-1 rounded-xl transition-colors relative
                    {{ $isMatched
                        ? 'bg-emerald-50 dark:bg-emerald-500/10'
                        : 'hover:bg-neutral-50 dark:hover:bg-neutral-800/50' }}">
                    {{-- App Icon --}}
                    <div class="w-10 h-10 rounded-[10px] flex items-center justify-center
                        {{ $isMatched
                            ? 'bg-white dark:bg-neutral-800 shadow-sm ring-1 ring-emerald-400/30'
                            : 'bg-neutral-100 dark:bg-neutral-800' }}">
                        <img
                            src="{{ $appFavicon }}"
                            alt="{{ $app['name'] }}"
                            class="w-6 h-6 object-contain"
                            onerror="this.src='https://icons.duckduckgo.com/ip2/google.com.ico'"
                        >
                    </div>
                    {{-- App Name --}}
                    <span class="text-[9px] font-medium text-center leading-tight
                        {{ $isMatched
                            ? 'text-emerald-700 dark:text-emerald-400 font-semibold'
                            : 'text-neutral-500 dark:text-neutral-400' }}"
                        style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;">
                        {{ $app['name'] }}
                    </span>
                    {{-- Matched checkmark badge --}}
                    @if ($isMatched)
                        <span class="absolute top-1.5 right-1.5 w-3.5 h-3.5 rounded-full bg-emerald-500 flex items-center justify-center">
                            <svg class="w-2 h-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Footer hint --}}
        <div class="px-3 py-2 border-t border-neutral-100 dark:border-neutral-800">
            <p class="text-[10px] text-neutral-400 dark:text-neutral-500 leading-relaxed">
                {{ __('filament-short-url::default.app_linking_supported_apps_helper') }}
            </p>
        </div>
    </div>
</div>
