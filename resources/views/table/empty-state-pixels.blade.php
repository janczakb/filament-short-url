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
            animation: infinite-scroll-y 22s linear infinite;
        }
        .animate-infinite-scroll-y:hover {
            animation-play-state: paused;
        }
    </style>

    <!-- Scrolling Mock Pixels Container -->
    <div class="animate-fade-in h-40 w-full max-w-xs md:max-w-sm overflow-hidden px-4 [mask-image:linear-gradient(transparent,black_15%,black_85%,transparent)] select-none pointer-events-auto">
        <div class="animate-infinite-scroll-y flex flex-col">
            <!-- Set 1 -->
            <!-- Card 1: Meta -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/facebook.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">Meta Ads Pixel</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: 9876543210</div>
                </div>
                <span class="text-[10px] font-bold bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    Meta
                </span>
            </div>

            <!-- Card 2: Google -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/google.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">Google Analytics 4</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: G-9X8Y7Z6W5V</div>
                </div>
                <span class="text-[10px] font-bold bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    Google
                </span>
            </div>

            <!-- Card 3: LinkedIn -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/linkedin.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">LinkedIn Insight Tag</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: 1029384756</div>
                </div>
                <span class="text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    LinkedIn
                </span>
            </div>

            <!-- Card 4: TikTok -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/tiktok.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">TikTok Pixel</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: C1234567890</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    TikTok
                </span>
            </div>

            <!-- Set 2 (Duplicated for infinite looping) -->
            <!-- Card 1: Meta -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/facebook.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">Meta Ads Pixel</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: 9876543210</div>
                </div>
                <span class="text-[10px] font-bold bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    Meta
                </span>
            </div>

            <!-- Card 2: Google -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/google.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">Google Analytics 4</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: G-9X8Y7Z6W5V</div>
                </div>
                <span class="text-[10px] font-bold bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    Google
                </span>
            </div>

            <!-- Card 3: LinkedIn -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/linkedin.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">LinkedIn Insight Tag</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: 1029384756</div>
                </div>
                <span class="text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    LinkedIn
                </span>
            </div>

            <!-- Card 4: TikTok -->
            <div class="flex items-center gap-3.5 p-3 rounded-xl border border-neutral-200/80 dark:border-neutral-800 bg-white/95 dark:bg-neutral-900/95 shadow-sm hover:scale-[1.02] hover:border-neutral-300 dark:hover:border-neutral-700 transition-all duration-200 mb-3">
                <div class="flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8">
                    <img src="https://icons.duckduckgo.com/ip2/tiktok.com.ico" 
                         class="w-full h-full object-contain" 
                         onerror="this.src='https://heroicons.com/24/outline/funnel.svg'" />
                </div>
                <div class="min-w-0 flex-1 text-left">
                    <div class="text-[13px] font-bold text-neutral-800 dark:text-neutral-200 leading-tight truncate whitespace-nowrap">TikTok Pixel</div>
                    <div class="text-[11px] text-neutral-450 dark:text-neutral-500 font-mono truncate whitespace-nowrap mt-0.5">ID: C1234567890</div>
                </div>
                <span class="text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 px-2.5 py-0.5 rounded-full ml-auto flex-shrink-0">
                    TikTok
                </span>
            </div>
        </div>
    </div>

    <!-- Title and Description -->
    <div class="max-w-sm text-pretty text-center px-4">
        <span class="text-base font-semibold text-neutral-900 dark:text-neutral-100">{{ __('filament-short-url::default.empty_state_pixel_heading') }}</span>
        <div class="mt-2 text-pretty text-sm text-neutral-500 dark:text-neutral-400">
            {{ __('filament-short-url::default.empty_state_pixel_description') }}
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
            <span>{{ __('filament-short-url::default.empty_state_pixel_action') }}</span>
        </button>
    </div>
</div>
