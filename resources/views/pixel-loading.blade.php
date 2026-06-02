<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.pixel_loading_title') ?? 'Connecting...' }}</title>

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

    <!-- Meta / Facebook Pixel -->
    @if(!empty($pixelMetaId))
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $pixelMetaId }}');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $pixelMetaId }}&ev=PageView&noscript=1" /></noscript>
    @endif

    <!-- Google Analytics / GTM -->
    @if(!empty($pixelGoogleId))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $pixelGoogleId }}"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $pixelGoogleId }}');
    </script>
    @endif

    <!-- LinkedIn Insight -->
    @if(!empty($pixelLinkedinId))
    <script type="text/javascript">
    _linkedin_data_partner_id = "{{ $pixelLinkedinId }}";
    window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
    window._linkedin_data_partner_ids.push(_linkedin_data_partner_id);
    (function(l) {
    if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])};
    window.lintrk.q=[]}
    var s = document.getElementsByTagName("script")[0];
    var b = document.createElement("script");
    b.type = "text/javascript";b.async = true;
    b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
    s.parentNode.insertBefore(b, s);})(window.lintrk);
    </script>
    <noscript><img height="1" width="1" style="display:none;" alt="" src="https://px.ads.linkedin.com/collect/?pid={{ $pixelLinkedinId }}&fmt=gif" /></noscript>
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
                {{ __('filament-short-url::default.pixel_loading_title') ?? 'Connecting...' }}
            </p>
            <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-1">
                {{ __('filament-short-url::default.pixel_loading_description') ?? 'Preparing your connection and forwarding you now.' }}
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
            window.location.replace("{!! addslashes($destination) !!}");
        }, 250);
    </script>
</body>
</html>
