<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('filament-short-url::default.password_title') ?? 'Password Protected' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgb(239, 246, 255) 0%, rgb(219, 234, 254) 100%);
        }
        .dark body {
            background: radial-gradient(circle at 10% 20%, rgb(17, 24, 39) 0%, rgb(15, 23, 42) 100%);
        }
    </style>
</head>
<body class="flex h-full items-center justify-center p-4 antialiased dark:text-white">
    <div class="w-full max-w-md rounded-2xl border border-white/20 bg-white/60 p-8 shadow-xl backdrop-blur-xl dark:border-gray-800/30 dark:bg-gray-900/60">
        <div class="text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500 dark:bg-amber-950/30 dark:text-amber-400">
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
            </div>
            <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white">
                {{ __('filament-short-url::default.password_title') ?? 'Password Protected' }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('filament-short-url::default.password_description') ?? 'This link is password-protected. Please enter the correct password to continue.' }}
            </p>
        </div>

        <form method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <input id="password" name="password" type="password" required placeholder="{{ __('filament-short-url::default.password_placeholder') ?? 'Enter password' }}"
                       class="w-full rounded-xl border border-gray-200 bg-white/80 px-4 py-3 text-sm text-gray-900 outline-none transition-all focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 dark:border-gray-800 dark:bg-gray-950/80 dark:text-white dark:focus:border-amber-500" />
                
                @if ($errors->has('password'))
                    <p class="mt-2 text-xs font-medium text-rose-500">
                        {{ $errors->first('password') }}
                    </p>
                @endif
            </div>

            <button type="submit"
                    class="flex w-full items-center justify-center rounded-xl bg-amber-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/20 transition-all hover:bg-amber-600 hover:shadow-amber-500/30 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                {{ __('filament-short-url::default.password_btn_unlock') ?? 'Unlock & Redirect' }}
            </button>
        </form>
    </div>
</body>
</html>
