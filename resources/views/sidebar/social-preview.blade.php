@php
    $ogTitle = $get('og_title') ?: null;
    $ogDescription = $get('og_description') ?: null;

    // Resolve the OG image url
    $ogImage = null;
    $ogImageState = $get('og_image');
    if ($ogImageState) {
        if (is_array($ogImageState)) {
            $first = reset($ogImageState);
            if ($first) {
                if (is_string($first)) {
                    $ogImage = \Illuminate\Support\Facades\Storage::disk('public')->url($first);
                } elseif (method_exists($first, 'temporaryUrl')) {
                    $ogImage = $first->temporaryUrl();
                }
            }
        } elseif (is_string($ogImageState)) {
            $ogImage = \Illuminate\Support\Facades\Storage::disk('public')->url($ogImageState);
        }
    } else {
        $ogImage = $get('og_image_scraped') ?: null;
    }
    $isScraping = $get('is_scraping') ?: false;
@endphp

<div
    class="sidebar-social-container"
    x-data="{ scraping: @js((bool) $isScraping), passwordProtected: @js((bool) $isPasswordProtected) }"
    x-on:fsu-scraping-start.window="scraping = true"
    x-on:fsu-scraping-end.window="scraping = false"
>

    {{-- Header: Title only (no "i" icon) --}}
    <span class="fi-fo-field-wrp-label flex items-center gap-x-3 mb-1.5">
        <label class="fi-label text-sm font-semibold leading-6 text-gray-950 dark:text-white">
            {{ __('filament-short-url::default.live_social_preview') }}
        </label>
    </span>

    <div x-show="scraping" x-cloak>
        {{-- ===== LOADING / SCRAPING STATE ===== --}}
        <div class="relative w-full rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-700 qr-preview-dots-bg aspect-[16/10] flex items-center justify-center">
            <div class="absolute inset-0 bg-neutral-100 dark:bg-neutral-800/20 flex items-center justify-center">
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24" style="width: 32px !important; height: 32px !important;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <div class="mt-2.5 space-y-2">
            <div class="h-4 bg-neutral-200 dark:bg-neutral-800 rounded animate-pulse w-3/4"></div>
            <div class="h-3.5 bg-neutral-200 dark:bg-neutral-800 rounded animate-pulse w-5/6"></div>
            <div class="h-3 bg-neutral-200 dark:bg-neutral-800/60 rounded animate-pulse w-1/2"></div>
        </div>
    </div>

    <div x-show="!scraping && passwordProtected" x-cloak>
        {{-- ===== PASSWORD STATE ===== --}}
        <div class="relative w-full rounded-xl overflow-hidden bg-black aspect-[16/10]">
            {{-- Edit button --}}
            <button
                type="button"
                onclick="
                    const tabs = document.querySelectorAll('[role=tab]');
                    tabs.forEach(t => { if (t.textContent.trim().includes('SEO') || t.textContent.trim().includes('Social') || t.getAttribute('aria-label')?.includes('seo')) { t.click(); } });
                "
                class="absolute top-2 right-2 z-10 w-8 h-8 rounded-lg bg-white/90 dark:bg-neutral-900/90 border border-neutral-200 dark:border-neutral-700 shadow-sm flex items-center justify-center hover:bg-white dark:hover:bg-neutral-800 transition cursor-pointer"
                style="width: 32px !important; height: 32px !important;"
                title="{{ __('filament-short-url::default.action_edit') }}"
            >
                <svg class="w-4 h-4 text-neutral-500 dark:text-neutral-400" style="width: 20px !important; height: 20px !important;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                </svg>
            </button>

            {{-- Lock icon centered --}}
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-14 h-14 rounded-full bg-neutral-800/80 flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Password caption --}}
        <div class="mt-2.5">
            <p class="text-sm font-bold text-neutral-800 dark:text-neutral-100">
                {{ __('filament-short-url::default.password_status_active') }}
            </p>
            <p class="text-[12px] text-neutral-500 dark:text-neutral-400 mt-0.5 leading-snug">
                {{ __('filament-short-url::default.password_prompt_preview_desc') }}
            </p>
        </div>
    </div>

    <div x-show="!scraping && !passwordProtected" x-cloak>
        {{-- ===== DEFAULT / IMAGE STATE ===== --}}
        <div class="relative w-full rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-700 qr-preview-dots-bg aspect-[16/10] flex items-center justify-center">

            {{-- Edit button (same style as QR preview: small, top-right) --}}
            <button
                type="button"
                onclick="
                    const tabs = document.querySelectorAll('[role=tab]');
                    tabs.forEach(t => { if (t.textContent.trim().includes('SEO') || t.textContent.trim().includes('Social') || t.getAttribute('aria-label')?.includes('seo')) { t.click(); } });
                "
                class="absolute top-2 right-2 z-10 w-8 h-8 rounded-lg bg-white/90 dark:bg-neutral-900/90 border border-neutral-200 dark:border-neutral-700 shadow-sm flex items-center justify-center hover:bg-white dark:hover:bg-neutral-800 transition cursor-pointer"
                style="width: 32px !important; height: 32px !important;"
                title="{{ __('filament-short-url::default.action_edit') }}"
            >
                <svg class="w-4 h-4 text-neutral-500 dark:text-neutral-400" style="width: 20px !important; height: 20px !important;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                </svg>
            </button>

            @if ($ogImage)
                {{-- OG Image preview --}}
                <img src="{{ $ogImage }}" alt="OG Preview" class="absolute inset-0 w-full h-full object-cover">
            @else
                {{-- Empty state: photo icon + text --}}
                <div class="flex flex-col items-center gap-2 text-neutral-400 dark:text-neutral-500 select-none pointer-events-none">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.3">
                        <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="1.3" stroke="currentColor" fill="none"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16l5-5a2 2 0 012.8 0L16 16m-2-2l1.5-1.5a2 2 0 012.8 0L20 14"/>
                        <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor" stroke="none"/>
                    </svg>
                    <span class="text-[11px] font-medium text-center leading-snug">
                        {{ __('filament-short-url::default.og_empty_state_hint') }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Title / Description caption --}}
        <div class="mt-2.5 space-y-0.5">
            <p class="text-sm font-bold {{ $ogTitle ? 'text-neutral-800 dark:text-neutral-100' : 'text-neutral-400 dark:text-neutral-600' }}">
                {{ $ogTitle ?: __('filament-short-url::default.og_title_placeholder') }}
            </p>
            <p class="text-[12px] {{ $ogDescription ? 'text-neutral-500 dark:text-neutral-400' : 'text-neutral-300 dark:text-neutral-600' }} leading-snug">
                {{ $ogDescription ?: __('filament-short-url::default.og_description_placeholder') }}
            </p>
        </div>
    </div>

</div>
