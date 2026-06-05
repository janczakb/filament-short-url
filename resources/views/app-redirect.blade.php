<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.app_redirect_title') }}</title>
    
    <!-- Premium Google Fonts: Bricolage Grotesque -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">
    
    <!-- Self-contained Tailwind CSS CDN for maximum plug-and-play reliability -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Bricolage Grotesque', 'sans-serif'],
                    },
                }
            }
        }
        
        // Detect system dark mode preferences
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @php
        $pixelMetaIds = $pixels->where('type', 'meta')->pluck('pixel_id');
        $pixelGoogleIds = $pixels->where('type', 'google')->pluck('pixel_id');
        $pixelLinkedinIds = $pixels->where('type', 'linkedin')->pluck('pixel_id');
        $pixelTiktokIds = $pixels->where('type', 'tiktok')->pluck('pixel_id');
        $pixelPinterestIds = $pixels->where('type', 'pinterest')->pluck('pixel_id');
    @endphp

    <!-- Meta / Facebook Pixel -->
    @if($pixelMetaIds->isNotEmpty())
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    @foreach($pixelMetaIds as $id)
    fbq('init', '{{ $id }}');
    fbq('track', 'PageView');
    @endforeach
    </script>
    @foreach($pixelMetaIds as $id)
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $id }}&ev=PageView&noscript=1" /></noscript>
    @endforeach
    @endif

    <!-- Google Analytics / GTM -->
    @if($pixelGoogleIds->isNotEmpty())
    @php $firstGoogleId = $pixelGoogleIds->first(); @endphp
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $firstGoogleId }}"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    @foreach($pixelGoogleIds as $id)
    gtag('config', '{{ $id }}');
    @endforeach
    </script>
    @endif

    <!-- LinkedIn Insight -->
    @if($pixelLinkedinIds->isNotEmpty())
    <script type="text/javascript">
    window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
    @foreach($pixelLinkedinIds as $id)
    window._linkedin_data_partner_ids.push("{{ $id }}");
    @endforeach
    (function(l) {
    if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])};
    window.lintrk.q=[]}
    var s = document.getElementsByTagName("script")[0];
    var b = document.createElement("script");
    b.type = "text/javascript";b.async = true;
    b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
    s.parentNode.insertBefore(b, s);})(window.lintrk);
    </script>
    @foreach($pixelLinkedinIds as $id)
    <noscript><img height="1" width="1" style="display:none;" alt="" src="https://px.ads.linkedin.com/collect/?pid={{ $id }}&fmt=gif" /></noscript>
    @endforeach
    @endif

    <!-- TikTok Pixel -->
    @if($pixelTiktokIds->isNotEmpty())
    <script>
    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.mixpanel;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=o||{};var a=d.createElement("script");a.type="text/javascript",a.async=!0,a.src=r+"?sdkid="+e+"&lib="+t;var c=d.getElementsByTagName("script")[0];c.parentNode.insertBefore(a,c)};
      @foreach($pixelTiktokIds as $id)
      ttq.load('{{ $id }}');
      ttq.page();
      @endforeach
    }(window, document, 'ttq');
    </script>
    @endif

    <!-- Pinterest Tag -->
    @if($pixelPinterestIds->isNotEmpty())
    <script>
    !function(e,n,t,r,a,s,o){e[r]||(e[r]=function(){(e[r].q=e[r].q||[]).push(arguments)},e[r].q=e[r].q||[],s=n.createElement(t),s.async=!0,s.src="https://s.pntrac.com/tag.js",o=n.getElementsByTagName(t)[0],o.parentNode.insertBefore(s,o))}(window,document,"script","pintrk");
    @foreach($pixelPinterestIds as $id)
    pintrk('load', '{{ $id }}');
    pintrk('page');
    @endforeach
    </script>
    @foreach($pixelPinterestIds as $id)
    <noscript><img height="1" width="1" style="display:none;" alt="" src="https://ct.pinterest.com/v3/?event=init&tid={{ $id }}&noscript=1" /></noscript>
    @endforeach
    @endif
</head>
<body class="bg-[#FCFCFC] dark:bg-[#0C0C0C] min-h-screen flex flex-col justify-between items-center py-10 px-6 font-sans antialiased">
    @php
        $siteName = config('filament-short-url.site_name') ?: config('app.name', 'Laravel');
        
        // Retrieve app metadata for premium visual hints
        $apps = \Bjanczak\FilamentShortUrl\Services\AppLinkingEngine::getSupportedApps();
        $matchedApp = $apps[$appId] ?? null;
        $appColor = $matchedApp['color'] ?? '#3b82f6';
        $appName = $matchedApp['name'] ?? 'App';
    @endphp

    {{-- Main Container --}}
    <div class="w-full max-w-[360px] flex flex-col items-center gap-6 my-auto">
        <div class="flex flex-col items-center text-center pb-2 select-none w-full">
            <span class="text-2xl font-extrabold tracking-tight text-neutral-900 dark:text-white mb-6 select-none">{{ $siteName }}</span>

            <!-- Premium Dynamic Loader with App Theme Highlight -->
            <div class="relative flex items-center justify-center w-20 h-20 mb-6">
                <!-- Outer Pulse ring -->
                <div class="absolute inset-0 rounded-full opacity-20 animate-ping" style="background-color: {{ $appColor }};"></div>
                <!-- Inner Spinning Border -->
                <div class="absolute inset-0 rounded-full border-4 border-neutral-100 dark:border-neutral-900"></div>
                <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-current animate-spin" style="color: {{ $appColor }};"></div>
                <!-- Center App Accent -->
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-md" style="background-color: {{ $appColor }};">
                    {{ substr($appName, 0, 1) }}
                </div>
            </div>
            
            <p class="text-xl font-bold text-neutral-900 dark:text-white mt-2">
                {{ __('filament-short-url::default.app_redirect_opening_in', ['app' => $appName]) }}
            </p>
            <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-2 max-w-[280px]">
                {{ __('filament-short-url::default.app_redirect_waiting_text') }}
            </p>
        </div>
        
        <div class="w-full flex flex-col gap-4">
            {{-- Action Buttons --}}
            <div class="flex flex-col gap-3 w-full mt-2">
                <!-- Manual trigger if redirect didn't catch -->
                <a href="{{ $deepLink }}"
                   class="w-full py-3.5 rounded-xl text-white font-semibold text-sm transition duration-200 shadow-md flex justify-center items-center gap-2 text-center"
                   style="background-color: {{ $appColor }}; filter: brightness(0.95);"
                   onmouseover="this.style.filter='brightness(1.05)';"
                   onmouseout="this.style.filter='brightness(0.95)';"
                   id="deep-link-btn">
                    <span>{{ __('filament-short-url::default.app_redirect_btn_open') }}</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                
                <!-- Fallback Button -->
                <a href="{{ $destination }}"
                   class="w-full py-3 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-neutral-500 dark:text-neutral-400 font-semibold text-sm transition duration-200 flex justify-center items-center gap-2 shadow-sm"
                   id="fallback-btn">
                    <span>{{ __('filament-short-url::default.app_redirect_btn_browser') }}</span>
                </a>
            </div>

            {{-- In-App Browser Alert Banner (Hidden by default, shown via JS) --}}
            <div id="in-app-alert" class="hidden mt-4 p-3.5 rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-950/20 dark:bg-amber-950/10 text-amber-800 dark:text-amber-400 text-xs text-center flex flex-col gap-1 select-none">
                <p class="font-bold flex items-center justify-center gap-1.5 text-amber-900 dark:text-amber-300">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ __('filament-short-url::default.app_redirect_in_app_warning_title') }}</span>
                </p>
                <p class="text-[11px] leading-relaxed opacity-95">
                    {{ __('filament-short-url::default.app_redirect_in_app_warning_desc') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Footer section --}}
    <div class="flex flex-col items-center gap-2 mt-auto select-none">
        <span class="text-xs font-medium text-neutral-400 dark:text-neutral-600">© {{ date('Y') }} {{ $siteName }}. All rights reserved.</span>
    </div>

    <!-- Automatic JS Deep Linking Engine -->
    <script>
        (function() {
            var deepLink = @json($deepLink);
            var fallbackUrl = @json($destination);
            var hasPixels = @json(!empty($pixels) && $pixels->isNotEmpty());
            
            // Detect in-app webviews
            var ua = navigator.userAgent || navigator.vendor || window.opera;
            var isInstagram = ua.indexOf('Instagram') > -1;
            var isFacebook = (ua.indexOf('FBAN') > -1) || (ua.indexOf('FBAV') > -1);
            var isMessenger = (ua.indexOf('Messenger') > -1) || (ua.indexOf('FB_IAB') > -1);
            
            if (isInstagram || isFacebook || isMessenger) {
                var alertBox = document.getElementById('in-app-alert');
                if (alertBox) {
                    alertBox.classList.remove('hidden');
                }
            }

            var fallbackTimeout = null;

            function launchApp() {
                // Try opening native app scheme
                window.location.replace(deepLink);
                
                // Trigger fallback if not installed/unsupported after 1.8 seconds
                fallbackTimeout = setTimeout(function() {
                    window.location.replace(fallbackUrl);
                }, 1800);
                
                // Cancel fallback if user switches context (app successfully opened)
                window.addEventListener('pagehide', function() {
                    if (fallbackTimeout) clearTimeout(fallbackTimeout);
                });
                window.addEventListener('visibilitychange', function() {
                    if (document.hidden && fallbackTimeout) {
                        clearTimeout(fallbackTimeout);
                    }
                });
                window.addEventListener('blur', function() {
                    if (fallbackTimeout) {
                        clearTimeout(fallbackTimeout);
                    }
                });
            }

            // Also clear timeout if user clicks manual trigger button
            var deepLinkBtn = document.getElementById('deep-link-btn');
            if (deepLinkBtn) {
                deepLinkBtn.addEventListener('click', function() {
                    if (fallbackTimeout) {
                        clearTimeout(fallbackTimeout);
                    }
                });
            }

            if (hasPixels) {
                // Wait 250ms for pixels to fire before triggering redirect
                setTimeout(launchApp, 250);
            } else {
                launchApp();
            }
        })();
    </script>
</body>
</html>
