<div class="flex flex-col items-center justify-center gap-6 px-4 py-10 md:min-h-[500px]">
    <style>
        @keyframes infinite-scroll-y {
            0% {
                transform: translateY(0);
            }
            100% {
                transform: translateY(-50%);
            }
        }
        .animate-infinite-scroll-y {
            animation: infinite-scroll-y 25s linear infinite;
        }
        .animate-infinite-scroll-y:hover {
            animation-play-state: paused;
        }
    </style>

    <!-- Scrolling Mock Cards Container -->
    <div class="animate-fade-in h-40 w-full max-w-xs md:max-w-sm overflow-hidden px-4 [mask-image:linear-gradient(transparent,black_15%,black_85%,transparent)] select-none pointer-events-auto">
        <div class="animate-infinite-scroll-y flex flex-col">
            <!-- Set 1 -->
            <!-- Card 1: YouTube -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/youtube.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">short.io/youtube-tour</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">youtube.com/watch...</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    1.4k
                </span>
            </div>

            <!-- Card 2: Maps -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/google.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">demo.to/monaco-map</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">maps.google.com/place...</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    890
                </span>
            </div>

            <!-- Card 3: WhatsApp -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/whatsapp.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">link.xyz/whatsapp-chat</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">wa.me/385912345678</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    340
                </span>
            </div>

            <!-- Card 4: Brochure PDF -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/dropbox.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">example.com/brochure-pdf</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">dropbox.com/s/spec-brochure...</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    620
                </span>
            </div>

            <!-- Card 5: A/B Split Test -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/facebook.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">short.io/promo-campaign</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium italic truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">A/B Split Traffic</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    2.1k
                </span>
            </div>

            <!-- Set 2 (Duplicated for infinite looping) -->
            <!-- Card 1: YouTube -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/youtube.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">short.io/youtube-tour</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">youtube.com/watch...</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    1.4k
                </span>
            </div>

            <!-- Card 2: Maps -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/google.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">demo.to/monaco-map</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">maps.google.com/place...</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    890
                </span>
            </div>

            <!-- Card 3: WhatsApp -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/whatsapp.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">link.xyz/whatsapp-chat</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">wa.me/385912345678</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    340
                </span>
            </div>

            <!-- Card 4: Brochure PDF -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/dropbox.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">example.com/brochure-pdf</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">dropbox.com/s/spec-brochure...</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    620
                </span>
            </div>

            <!-- Card 5: A/B Split Test -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/facebook.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/link.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">short.io/promo-campaign</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-medium italic truncate whitespace-nowrap max-w-[130px] md:max-w-[180px] mt-0.5">A/B Split Traffic</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2 py-0.5 rounded-full flex items-center gap-1 ml-auto flex-shrink-0">
                    <svg class="size-3 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    2.1k
                </span>
            </div>
        </div>
    </div>

    <!-- Title and Description -->
    <div class="max-w-sm text-pretty text-center px-4">
        <span class="text-base font-semibold text-neutral-900 dark:text-neutral-100">{{ __('filament-short-url::default.empty_state_heading') }}</span>
        <div class="mt-2 text-pretty text-sm text-neutral-500 dark:text-neutral-400">
            {{ __('filament-short-url::default.empty_state_description') }}
        </div>
    </div>

    <!-- Create Button -->
    <div class="flex items-center gap-2">
        <button type="button"
                x-on:click="$wire.mountAction('create')"
                class="group flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-black bg-black text-white hover:bg-neutral-800 dark:border-white dark:bg-white dark:text-black dark:hover:bg-neutral-100 hover:ring-4 hover:ring-neutral-200 dark:hover:ring-neutral-800/50 px-4 text-sm font-semibold transition-all cursor-pointer">
            <svg class="size-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>{{ __('filament-short-url::default.empty_state_action') }}</span>
        </button>
    </div>
</div>
