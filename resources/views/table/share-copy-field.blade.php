@php
    $shortUrl = $record->getShortUrl();
    $shareCopiedMsg = __('filament-short-url::default.share_copied');
    $shareCopyBtnText = __('filament-short-url::default.share_copy');
@endphp

<div class="flex items-center gap-2 relative mt-2">
    <input type="text" 
           readonly 
           value="{{ e($shortUrl) }}" 
           id="share_link_input_{{ $record->id }}"
           class="flex-1 min-w-0 block w-full px-3.5 py-2.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 text-gray-900 dark:text-gray-100 text-sm focus:ring-primary-500 focus:border-primary-500 dark:focus:ring-primary-400 dark:focus:border-primary-400 focus:outline-none">
    
    <button onclick="
        const input = document.getElementById('share_link_input_{{ $record->id }}');
        input.select();
        navigator.clipboard.writeText(input.value);
        if (typeof FilamentNotification !== 'undefined') {
            new FilamentNotification()
                .title('{{ e($shareCopiedMsg) }}')
                .success()
                .send();
        } else if (typeof Alpine !== 'undefined') {
            Alpine.store('filament-notifications')?.send({
                status: 'success',
                title: '{{ e($shareCopiedMsg) }}'
            });
        }
    " 
    class="flex-shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-gray-100 dark:hover:bg-white text-white dark:text-gray-950 font-semibold text-sm rounded-lg shadow-sm hover:shadow transition duration-200">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        <span>{{ $shareCopyBtnText }}</span>
    </button>
</div>
