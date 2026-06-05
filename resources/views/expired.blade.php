<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.expired_title') }}</title>
    
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
</head>
<body class="bg-[#FCFCFC] dark:bg-[#0C0C0C] min-h-screen flex flex-col justify-between items-center py-10 px-6 font-sans antialiased">
    @php
        $siteName = config('filament-short-url.site_name') ?: config('app.name', 'Laravel');
    @endphp

    {{-- Main Expiry Box --}}
    <div class="w-full max-w-[360px] flex flex-col items-center gap-6 my-auto">
        <div class="flex flex-col items-center text-center pb-2 select-none w-full">
            <span class="text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white mb-3">{{ $siteName }}</span>

            <div class="w-16 h-16 rounded-full bg-amber-500/10 dark:bg-amber-500/20 flex items-center justify-center mt-4 mb-2 text-amber-500">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <p class="text-xl font-medium text-neutral-900 dark:text-white mt-2">
                {{ __('filament-short-url::default.expired_title') }}
            </p>
            <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-1">
                {{ __('filament-short-url::default.expired_description') }}
            </p>
        </div>

        <div class="w-full flex flex-col gap-4">
            {{-- Short URL info box --}}
            @if ($shortUrl)
                <div class="relative flex items-center justify-center px-3 py-3.5 w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 transition duration-200">
                    <div class="flex flex-col justify-center text-center min-w-0">
                        <span class="text-[10px] font-bold text-neutral-400 dark:text-neutral-500 uppercase tracking-wide select-none">
                            Expired Link
                        </span>
                        <p class="break-all text-xs font-mono font-semibold text-rose-600 dark:text-rose-400 mt-1 select-all">
                            {{ $shortUrl->getShortUrl() }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Action Button --}}
            <a href="{{ url('/') }}" class="w-full mt-2 py-3.5 rounded-xl bg-neutral-900 hover:bg-neutral-800 dark:bg-white dark:hover:bg-neutral-200 dark:text-neutral-900 text-white font-semibold text-sm transition duration-200 flex justify-center items-center gap-2 text-center">
                <span>{{ __('filament-short-url::default.expired_btn_home') }}</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </a>
        </div>
    </div>

    {{-- Footer section --}}
    <div class="flex flex-col items-center gap-2 mt-auto select-none">
        <span class="text-xs font-medium text-neutral-400 dark:text-neutral-600">© {{ date('Y') }} {{ $siteName }} Inc. All rights reserved.</span>
    </div>
</body>
</html>
