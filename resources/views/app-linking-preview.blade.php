@php
    $matchedAppId = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::matchApp($destinationUrl);
    $apps = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::getSupportedApps();
    $appCount = count($apps);
@endphp

<div class="app-linking-preview">
    @if ($matchedAppId && isset($apps[$matchedAppId]))
        @php
            $matchedApp = $apps[$matchedAppId];
            $matchedDomain = explode('/', $matchedApp['domains'][0])[0];
            $matchedFavicon = "https://icons.duckduckgo.com/ip2/{$matchedDomain}.ico";
            $deepLink = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::convertToScheme($destinationUrl, $matchedAppId);
        @endphp

        <div class="app-linking-status app-linking-status--matched">
            <div class="app-linking-status-main">
                <div class="app-linking-status-icon app-linking-status-icon--matched">
                    <img
                        src="{{ $matchedFavicon }}"
                        alt="{{ $matchedApp['name'] }}"
                        loading="lazy"
                        onerror="this.src='https://icons.duckduckgo.com/ip2/google.com.ico'"
                    >
                </div>

                <div class="app-linking-status-copy">
                    <div class="app-linking-status-title-row">
                        <p class="app-linking-status-title">{{ $matchedApp['name'] }}</p>
                        <span class="app-linking-status-badge app-linking-status-badge--success">
                            {{ __('filament-short-url::default.app_linking_auto_open') }}
                        </span>
                    </div>
                    <p class="app-linking-status-desc">
                        {!! __('filament-short-url::default.app_linking_matched_description', ['app' => e($matchedApp['name'])]) !!}
                    </p>
                </div>
            </div>

            <div class="app-linking-deep-link">
                <span class="app-linking-deep-link-label">{{ __('filament-short-url::default.app_linking_deep_link_label') }}</span>
                <code class="app-linking-deep-link-value">{{ $deepLink }}</code>
            </div>
        </div>
    @else
        <div class="app-linking-status app-linking-status--browser">
            <div class="app-linking-status-icon app-linking-status-icon--browser">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.467.732-3.553" />
                </svg>
            </div>

            <div class="app-linking-status-copy">
                <p class="app-linking-status-title">{{ __('filament-short-url::default.app_linking_standard_redirect') }}</p>
            </div>
        </div>
    @endif

    <div class="app-linking-catalog">
        <div class="app-linking-catalog-header">
            <div class="app-linking-catalog-heading">
                <p class="app-linking-catalog-title">{{ __('filament-short-url::default.app_linking_supported_apps') }}</p>
                <p class="app-linking-catalog-subtitle">
                    {{ __('filament-short-url::default.app_linking_preconfigured_count', ['count' => $appCount]) }}
                </p>
            </div>

            <div class="app-linking-os-badges" aria-label="{{ __('filament-short-url::default.app_linking_supported_os') }}">
                <span class="app-linking-os-badge">iOS</span>
                <span class="app-linking-os-badge">Android</span>
            </div>
        </div>

        <div class="app-linking-grid">
            @foreach ($apps as $appId => $app)
                @php
                    $isMatched = ($appId === $matchedAppId);
                    $appDomain = explode('/', $app['domains'][0])[0];
                    $appFavicon = "https://icons.duckduckgo.com/ip2/{$appDomain}.ico";
                @endphp

                <div @class([
                    'app-linking-app',
                    'app-linking-app--matched' => $isMatched,
                ])>
                    <div class="app-linking-app-icon-wrap">
                        <img
                            src="{{ $appFavicon }}"
                            alt=""
                            class="app-linking-app-icon"
                            loading="lazy"
                            onerror="this.src='https://icons.duckduckgo.com/ip2/google.com.ico'"
                        >
                        @if ($isMatched)
                            <span class="app-linking-app-check" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        @endif
                    </div>
                    <span class="app-linking-app-name">{{ $app['name'] }}</span>
                </div>
            @endforeach
        </div>

        <p class="app-linking-footnote">
            {{ __('filament-short-url::default.app_linking_supported_apps_helper') }}
        </p>
    </div>
</div>
