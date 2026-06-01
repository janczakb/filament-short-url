<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.warning_title') ?? 'Redirect Warning' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgb(254, 242, 242) 0%, rgb(254, 226, 226) 100%);
        }
        .dark body {
            background: radial-gradient(circle at 10% 20%, rgb(17, 24, 39) 0%, rgb(15, 23, 42) 100%);
        }
    </style>
</head>
<body class="flex h-full items-center justify-center p-4 antialiased dark:text-white">
    <div class="w-full max-w-lg rounded-2xl border border-white/20 bg-white/60 p-8 shadow-xl backdrop-blur-xl dark:border-gray-800/30 dark:bg-gray-900/60">
        <div class="text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-500 dark:bg-rose-950/30 dark:text-rose-400">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">
                {{ __('filament-short-url::default.warning_title') ?? 'Security Redirect Warning' }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('filament-short-url::default.warning_description') ?? 'You are leaving this secure portal and being redirected to an external target link. Please ensure you trust the address below:' }}
            </p>
        </div>

        <div class="mt-6 rounded-xl border border-rose-100 bg-rose-50/50 p-4 dark:border-rose-950/20 dark:bg-rose-950/10">
            <p class="break-all font-mono text-sm font-semibold text-rose-700 dark:text-rose-400">
                {{ $destinationUrl }}
            </p>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <a href="{{ request()->fullUrlWithQuery(['confirmed' => 1]) }}"
               class="flex flex-1 items-center justify-center rounded-xl bg-rose-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-rose-500/20 transition-all hover:bg-rose-600 hover:shadow-rose-500/30 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                {{ __('filament-short-url::default.warning_btn_continue') ?? 'Continue to Destination' }}
            </a>
            
            <button onclick="window.history.back()"
                    class="flex flex-1 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition-all hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-900">
                {{ __('filament-short-url::default.warning_btn_back') ?? 'Go Back' }}
            </button>
        </div>
    </div>
</body>
</html>
