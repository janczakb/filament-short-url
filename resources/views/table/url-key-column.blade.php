@php
    $shortUrl = $record->getShortUrl();
    $copiedMsg = __('filament-short-url::default.qr_copied');
    $tooltipCopy = __('filament-short-url::default.action_copy');
    $destHost = parse_url($record->destination_url, PHP_URL_HOST);
@endphp

<div onclick="
    event.preventDefault();
    event.stopPropagation();
    const text = '{{ e($shortUrl) }}';
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
    } else {
        const textCopyArea = document.createElement('textarea');
        textCopyArea.value = text;
        textCopyArea.style.position = 'fixed';
        textCopyArea.style.left = '-999999px';
        textCopyArea.style.top = '-999999px';
        document.body.appendChild(textCopyArea);
        textCopyArea.focus();
        textCopyArea.select();
        try {
            document.execCommand('copy');
        } catch (err) {}
        textCopyArea.remove();
    }
    if (typeof FilamentNotification !== 'undefined') {
        new FilamentNotification()
            .title('{{ e($copiedMsg) }}')
            .success()
            .send();
    } else if (window.Alpine) {
        window.Alpine.store('filament-notifications')?.send({
            status: 'success',
            title: '{{ e($copiedMsg) }}'
        });
    }
" class="flex items-center gap-3 cursor-pointer w-fit">
    <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
        <img src="https://icons.duckduckgo.com/ip2/{{ e($destHost) }}.ico" 
             class="w-full h-full object-contain" 
             onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
    </div>
    <span class="text-[#2a5bd7] text-[16px] font-bold leading-6 break-all line-clamp-1">
        {{ $shortUrl }}
    </span>
    <span title="{{ e($tooltipCopy) }}" class="w-8 h-8 rounded-full flex items-center justify-center bg-[#f4f4f5] hover:bg-[#e4e4e7] dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors flex-shrink-0 focus:outline-none">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
    </span>
</div>
