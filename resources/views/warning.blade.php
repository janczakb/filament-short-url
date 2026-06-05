<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.warning_title') }}</title>
    
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
        $logoPath = function_exists('setting') ? setting('logo_path') : null;
        $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
        $siteName = config('filament-short-url.site_name') ?: config('app.name', 'Laravel');
    @endphp

    {{-- Main Warning Box --}}
    <div class="w-full max-w-[360px] flex flex-col items-center gap-6 my-auto">
        <div class="flex flex-col items-center text-center pb-2 select-none w-full">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-[60px] w-auto object-contain mb-4" />
            @else
                <span class="text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white mb-3">{{ $siteName }}</span>
            @endif
            <p class="text-xl font-medium text-neutral-900 dark:text-white mt-2">
                {{ __('filament-short-url::default.warning_title') }}
            </p>
            <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-1">
                {{ __('filament-short-url::default.warning_description') }}
            </p>
        </div>
        
        <div class="w-full flex flex-col gap-4">
            {{-- Destination URL Container styled exactly like Custom Login input box --}}
            <div class="flex flex-col gap-1.5 w-full">
                <div class="relative flex items-center justify-between px-3 py-3 w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 transition duration-200">
                    <div class="flex-grow flex flex-col justify-center min-w-0 pr-4">
                        <span class="text-[10px] font-bold text-neutral-400 dark:text-neutral-500 uppercase tracking-wide select-none">
                            Destination URL
                        </span>
                        <p id="dest-url-text" class="break-all text-xs font-mono font-semibold text-rose-600 dark:text-rose-400 mt-1 select-all">
                            {{ $destinationUrl }}
                        </p>
                    </div>
                    
                    <!-- Quick Copy Button -->
                    <button onclick="
                        const text = document.getElementById('dest-url-text').innerText;
                        navigator.clipboard.writeText(text);
                        const btn = this;
                        const origHtml = btn.innerHTML;
                        btn.innerHTML = `<svg class='h-4 w-4 text-emerald-500' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='3.5'><path stroke-linecap='round' stroke-linejoin='round' d='M5 13l4 4L19 7' /></svg>`;
                        setTimeout(() => { btn.innerHTML = origHtml; }, 2000);
                    " class="flex-shrink-0 p-2 rounded-lg border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-950 text-neutral-400 hover:text-neutral-600 dark:text-neutral-500 dark:hover:text-neutral-300 transition-colors focus:outline-none" title="Copy URL">
                        <svg class="h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col gap-3 w-full mt-2">
                <!-- Continue Button (Primary Accent) -->
                <a href="{{ request()->fullUrlWithQuery(['confirmed' => 1]) }}"
                   class="w-full py-3.5 rounded-xl bg-rose-600 hover:bg-rose-500 dark:bg-rose-500 dark:hover:bg-rose-400 text-white font-semibold text-sm transition duration-200 shadow-sm flex justify-center items-center gap-2 text-center">
                    <span>{{ __('filament-short-url::default.warning_btn_continue') }}</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                
                <!-- Go Back Button (Secondary) -->
                <button onclick="window.history.back()"
                        class="w-full py-3 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 hover:bg-neutral-50 dark:hover:bg-neutral-800 text-neutral-900 dark:text-white font-semibold text-sm transition duration-200 flex justify-center items-center gap-2 shadow-sm">
                    {{ __('filament-short-url::default.warning_btn_back') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Footer section --}}
    <div class="flex flex-col items-center gap-2 mt-auto select-none">
        <span class="text-xs font-medium text-neutral-400 dark:text-neutral-600">© {{ date('Y') }} {{ $siteName }} Inc. All rights reserved.</span>
    </div>
</body>
</html>
