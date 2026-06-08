<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.pixel_loading_title') }}</title>

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
        $logoPath = function_exists('setting') ? setting('logo_path') : null;
        $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
        $siteName = config('filament-short-url.site_name') ?: config('app.name', 'Laravel');
    @endphp

    {{-- Main card --}}
    <div class="w-full max-w-[360px] flex flex-col items-center gap-6 my-auto">
        <div class="flex flex-col items-center text-center pb-2 select-none w-full">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-[60px] w-auto object-contain mb-4" />
            @else
                <span class="text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white mb-3">{{ $siteName }}</span>
            @endif

            {{-- Animated spinner --}}
            <div class="my-5 flex items-center justify-center gap-1.5" aria-hidden="true">
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-300 dark:bg-neutral-600 animate-bounce [animation-delay:-0.3s]"></span>
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-400 dark:bg-neutral-500 animate-bounce [animation-delay:-0.15s]"></span>
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-500 dark:bg-neutral-400 animate-bounce"></span>
            </div>

            <p class="text-xl font-medium text-neutral-900 dark:text-white mt-2">
                {{ __('filament-short-url::default.pixel_loading_title') }}
            </p>
            <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-1">
                {{ __('filament-short-url::default.pixel_loading_description') }}
            </p>
        </div>

        {{-- Progress bar --}}
        <div class="w-full h-1 rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
            <div id="pixel-progress" class="h-full w-0 rounded-full bg-neutral-900 dark:bg-white transition-all ease-linear" style="transition-duration: 220ms;"></div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex flex-col items-center gap-2 mt-auto select-none">
        <span class="text-xs font-medium text-neutral-400 dark:text-neutral-600">© {{ date('Y') }} {{ $siteName }} Inc. All rights reserved.</span>
    </div>

    {{-- Redirect after pixels fire --}}
    <script>
        // Animate the progress bar to full in sync with the redirect delay
        requestAnimationFrame(function() {
            document.getElementById('pixel-progress').style.width = '100%';
        });

        setTimeout(function() {
            window.location.replace(@json($destination));
        }, 250);
    </script>
</body>
</html>
