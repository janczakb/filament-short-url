@php
    $shortUrl = $record->getShortUrl();
    $encodedUrl = urlencode($shortUrl);
@endphp

<div class="flex items-center gap-6 overflow-x-auto pb-4 pt-1 scroll-smooth" style="scrollbar-width: thin; -ms-overflow-style: none;">
    <!-- Messenger -->
    <a href="fb-messenger://share/?link={{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
        <div class="w-11 h-11 rounded-full bg-gradient-to-tr from-[#006aff] via-[#00b2ff] to-[#00d6ff] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.14 2 11.25c0 2.9 1.45 5.48 3.73 7.08v3.67c0 .24.23.4.43.27l4.07-2.3c.57.16 1.17.25 1.77.25 5.52 0 10-4.14 10-9.25S17.52 2 12 2zm1.09 11.95l-2.43-2.6-4.73 2.6 5.19-5.52 2.47 2.63 4.7-2.63-5.2 5.52z"/>
            </svg>
        </div>
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Messenger</span>
    </a>
    <!-- Facebook -->
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
        <div class="w-11 h-11 rounded-full bg-[#1877f2] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
        </div>
        <span class="text-[11px] font-medium text-gray-550 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Facebook</span>
    </a>
    <!-- WhatsApp -->
    <a href="https://api.whatsapp.com/send?text={{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
        <div class="w-11 h-11 rounded-full bg-[#25d366] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.458 5.704 1.459h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </div>
        <span class="text-[11px] font-medium text-gray-550 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">WhatsApp</span>
    </a>
    <!-- Twitter/X -->
    <a href="https://twitter.com/intent/tweet?url={{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
        <div class="w-11 h-11 rounded-full bg-[#0f1419] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
        </div>
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Twitter (X)</span>
    </a>
    <!-- LinkedIn -->
    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
        <div class="w-11 h-11 rounded-full bg-[#0077b5] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
            <svg class="w-5.5 h-5.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.779-1.75-1.75s.784-1.75 1.75-1.75 1.75.779 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
            </svg>
        </div>
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">LinkedIn</span>
    </a>
    <!-- Email -->
    <a href="mailto:?body={{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 group flex-shrink-0 cursor-pointer">
        <div class="w-11 h-11 rounded-full bg-[#6b7280] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform duration-200">
            <svg class="w-5.5 h-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">Email</span>
    </a>
</div>
