<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.password_title') ?? 'Password Protected' }}</title>
    
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
        $siteName = config('app.name', 'Laravel');
    @endphp

    {{-- Main Sign-in Box --}}
    <div class="w-full max-w-[360px] flex flex-col items-center gap-6 my-auto">
        <div class="flex flex-col items-center text-center pb-2 select-none w-full">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-[60px] w-auto object-contain mb-4" />
            @else
                <span class="text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-white mb-3">{{ $siteName }}</span>
            @endif
            <p class="text-xl font-medium text-neutral-900 dark:text-white mt-2">
                {{ __('filament-short-url::default.password_title') ?? 'Password Protected' }}
            </p>
            <p class="text-sm text-neutral-400 dark:text-neutral-500 mt-1">
                {{ __('filament-short-url::default.password_description') ?? 'This link is password-protected. Please enter the correct password to continue.' }}
            </p>
        </div>
        
        <form method="POST" class="w-full flex flex-col gap-4">
            @csrf

            {{-- Password input styled exactly like Custom Login --}}
            <div class="flex flex-col gap-1.5 w-full">
                <div class="relative flex flex-col justify-center px-3 py-2 w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 focus-within:ring-2 focus-within:ring-neutral-900 dark:focus-within:ring-white transition duration-200">
                    <label for="password" class="text-[10px] font-bold text-neutral-400 dark:text-neutral-500 uppercase tracking-wide select-none">
                        {{ __('filament-short-url::default.password_placeholder') ?? 'Password' }}
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password"
                        placeholder="••••••••" 
                        required
                        autofocus
                        class="w-full bg-transparent text-sm text-neutral-900 dark:text-white placeholder-neutral-300 dark:placeholder-neutral-700 focus:outline-none py-0.5 mt-0.5"
                    />
                </div>
                @if ($errors->has('password'))
                    <span class="text-xs text-red-500 px-1 mt-0.5">{{ $errors->first('password') }}</span>
                @endif
            </div>

            {{-- Submit button --}}
            <button 
                type="submit" 
                class="w-full mt-2 py-3.5 rounded-xl bg-neutral-900 hover:bg-neutral-800 dark:bg-white dark:hover:bg-neutral-200 dark:text-neutral-900 text-white font-semibold text-sm transition duration-200 shadow-sm flex justify-center items-center gap-2"
            >
                <span>{{ __('filament-short-url::default.password_btn_unlock') ?? 'Unlock & Redirect' }}</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </button>
        </form>
    </div>

    {{-- Footer section --}}
    <div class="flex flex-col items-center gap-2 mt-auto select-none">
        <span class="text-xs font-medium text-neutral-400 dark:text-neutral-600">© {{ date('Y') }} {{ $siteName }} Inc. All rights reserved.</span>
    </div>
</body>
</html>
