@php
    $logoPath = function_exists('setting') ? setting('logo_path') : null;
    $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
    $siteName = config('filament-short-url.site_name') ?: config('app.name', 'Laravel');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $siteName)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200..800&display=swap" rel="stylesheet">
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
        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-[#FCFCFC] dark:bg-[#0C0C0C] min-h-screen font-sans antialiased text-neutral-900 dark:text-white">
    <div class="mx-auto w-full max-w-3xl px-6 py-10">
        <header class="mb-8 flex flex-col items-center text-center">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="mb-4 h-[52px] w-auto object-contain" />
            @else
                <span class="mb-2 text-2xl font-extrabold tracking-tight">{{ $siteName }}</span>
            @endif
            @hasSection('subtitle')
                <p class="text-sm text-neutral-500 dark:text-neutral-400">@yield('subtitle')</p>
            @endif
        </header>

        @yield('content')
    </div>
</body>
</html>
