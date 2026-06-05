@php
    $shortUrlsCount = $record->short_urls_count ?? $record->shortUrls()->count();
@endphp

<div class="flex items-center gap-3.5 min-w-0 py-2">
    <!-- Globe Icon -->
    <div class="flex items-center justify-center w-10 h-10 rounded-full border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-950/40 flex-shrink-0">
        <svg class="w-5 h-5 text-neutral-500 dark:text-neutral-450" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
        </svg>
    </div>
    
    <div class="min-w-0">
        <!-- Domain -->
        <div class="flex items-center gap-2">
            <span class="text-[15px] font-bold text-neutral-800 dark:text-neutral-200 truncate leading-tight">
                {{ $record->domain }}
            </span>
        </div>
        <!-- Subtitle -->
        <div class="text-[12px] text-neutral-450 dark:text-neutral-500 mt-1 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 flex-shrink-0 text-gray-400 dark:text-gray-500" 
                 style="transform: scaleY(-1);" 
                 xmlns="http://www.w3.org/2000/svg" 
                 fill="none" 
                 viewBox="0 0 24 24" 
                 stroke-width="2.5" 
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 00-6 6v3" />
            </svg>
            @if($shortUrlsCount === 0)
                <span>{{ __('filament-short-url::default.domain_no_redirects') }}</span>
            @else
                <span>{{ trans_choice('filament-short-url::default.domain_mapped_links', $shortUrlsCount, ['count' => $shortUrlsCount]) }}</span>
            @endif
        </div>
    </div>
</div>
