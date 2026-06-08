<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ogMeta['title'] }}</title>

    {{-- Robots Search Indexing configuration --}}
    @if($shortUrl->do_index)
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ $ogMeta['canonical_url'] }}">
    @else
        <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- Open Graph / Facebook Metadata --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $ogMeta['short_url'] }}">
    <meta property="og:site_name" content="{{ $ogMeta['site_name'] }}">
    <meta property="og:title" content="{{ $ogMeta['title'] }}">
    @if($ogMeta['description'])
        <meta property="og:description" content="{{ $ogMeta['description'] }}">
    @endif
    @if($ogMeta['image_url'])
        <meta property="og:image" content="{{ $ogMeta['image_url'] }}">
        @if($ogMeta['image_width'] && $ogMeta['image_height'])
            <meta property="og:image:width" content="{{ $ogMeta['image_width'] }}">
            <meta property="og:image:height" content="{{ $ogMeta['image_height'] }}">
        @endif
    @endif

    {{-- Twitter Metadata --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $ogMeta['short_url'] }}">
    <meta name="twitter:title" content="{{ $ogMeta['title'] }}">
    @if($ogMeta['description'])
        <meta name="twitter:description" content="{{ $ogMeta['description'] }}">
    @endif
    @if($ogMeta['image_url'])
        <meta name="twitter:image" content="{{ $ogMeta['image_url'] }}">
    @endif

    {{-- Human visitors only: bots must stay on this page to read OG tags --}}
    @if(!$shortUrl->is_cloaked && !$isBot)
        <meta http-equiv="refresh" content="0;url={{ e($destination) }}">
        <script>
            window.location.replace(@json($destination));
        </script>
    @endif

    @if($shortUrl->is_cloaked)
        <style>
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                overflow: hidden;
                background-color: #ffffff;
            }
            iframe {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 100%;
                height: 100%;
                border: none;
                margin: 0;
                padding: 0;
                overflow: hidden;
                z-index: 999999;
            }
        </style>
    @endif
</head>
<body class="h-full bg-white text-neutral-800 antialiased font-sans flex items-center justify-center">

    @if($shortUrl->is_cloaked)
        {{-- Link Cloaking Iframe --}}
        <iframe src="{{ e($destination) }}" title="{{ $ogMeta['title'] }}"></iframe>
    @elseif(!$isBot)
        {{-- Standard JS redirect page fallback for human visitors --}}
        <div class="text-center p-6 max-w-sm flex flex-col items-center gap-4 select-none">
            <div class="flex items-center justify-center gap-1.5" aria-hidden="true">
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-300 dark:bg-neutral-600 animate-bounce [animation-delay:-0.3s]"></span>
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-400 dark:bg-neutral-500 animate-bounce [animation-delay:-0.15s]"></span>
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-500 dark:bg-neutral-400 animate-bounce"></span>
            </div>
            <p class="text-lg font-medium text-neutral-600">{{ __('filament-short-url::default.redirect_html_redirecting') }}</p>
            <p class="text-xs text-neutral-400">If you are not redirected automatically, <a href="{{ e($destination) }}" class="text-primary-600 underline font-semibold hover:text-primary-500">click here</a>.</p>
        </div>
    @endif

</body>
</html>
