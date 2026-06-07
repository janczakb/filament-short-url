<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $shortUrl->og_title ?: ($shortUrl->title ?: $shortUrl->url_key) }}</title>

    {{-- Robots Search Indexing configuration --}}
    @if($shortUrl->do_index)
        <meta name="robots" content="index, follow">
    @else
        <meta name="robots" content="noindex, nofollow">
    @endif

    {{-- Open Graph / Facebook Metadata --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $shortUrl->getShortUrl() }}">
    <meta property="og:title" content="{{ $shortUrl->og_title ?: ($shortUrl->title ?: $shortUrl->url_key) }}">
    @if($shortUrl->og_description)
        <meta property="og:description" content="{{ $shortUrl->og_description }}">
    @endif
    @if($shortUrl->og_image)
        @php
            $ogImageUrl = $shortUrl->og_image;
            if (!str_starts_with($ogImageUrl, 'http')) {
                $ogImageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($ogImageUrl);
            }
        @endphp
        <meta property="og:image" content="{{ $ogImageUrl }}">
    @endif

    {{-- Twitter Metadata --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $shortUrl->getShortUrl() }}">
    <meta name="twitter:title" content="{{ $shortUrl->og_title ?: ($shortUrl->title ?: $shortUrl->url_key) }}">
    @if($shortUrl->og_description)
        <meta name="twitter:description" content="{{ $shortUrl->og_description }}">
    @endif
    @if($shortUrl->og_image)
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @endif

    {{-- If not cloaked, execute client-side redirect for users --}}
    @if(!$shortUrl->is_cloaked)
        <meta http-equiv="refresh" content="0;url={!! $destination !!}">
        <script>
            window.location.replace("{!! addslashes($destination) !!}");
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
        <iframe src="{!! $destination !!}" title="{{ $shortUrl->og_title ?: $shortUrl->url_key }}"></iframe>
    @else
        {{-- Standard JS redirect page fallback --}}
        <div class="text-center p-6 max-w-sm flex flex-col items-center gap-4 select-none">
            {{-- Animated spinner --}}
            <div class="flex items-center justify-center gap-1.5" aria-hidden="true">
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-300 dark:bg-neutral-600 animate-bounce [animation-delay:-0.3s]"></span>
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-400 dark:bg-neutral-500 animate-bounce [animation-delay:-0.15s]"></span>
                <span class="block w-2.5 h-2.5 rounded-full bg-neutral-500 dark:bg-neutral-400 animate-bounce"></span>
            </div>
            <p class="text-lg font-medium text-neutral-600">Redirecting you to the destination website...</p>
            <p class="text-xs text-neutral-400">If you are not redirected automatically, <a href="{!! $destination !!}" class="text-primary-600 underline font-semibold hover:text-primary-500">click here</a>.</p>
        </div>
    @endif

</body>
</html>
