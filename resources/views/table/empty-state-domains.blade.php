<div class="flex flex-col items-center justify-center gap-6 px-4 py-10 md:min-h-[550px]">
    <!-- Custom Domain Visual / Logo Grid -->
    <div class="relative flex items-center justify-center h-28 w-full max-w-xs md:max-w-md overflow-hidden rounded-2xl border border-neutral-200/60 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/30 p-6 shadow-inner">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:14px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
        <div class="relative flex items-center gap-4 z-10">
            <!-- Icon 1: Domain Globe -->
            <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-sm transition-transform duration-300 hover:-translate-y-1">
                <!-- Ping green dot in the corner to show activity -->
                <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <svg class="w-6 h-6 text-neutral-600 dark:text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />
                </svg>
            </div>
            
            <!-- Connection Line with flowing green dash animation -->
            <div class="flex items-center justify-center w-12">
                <svg class="w-full h-2" viewBox="0 0 48 8" fill="none">
                    <!-- Base grey dashes -->
                    <line x1="0" y1="4" x2="48" y2="4" stroke="#e5e5e5" class="dark:stroke-neutral-800" stroke-width="2" stroke-linecap="round" stroke-dasharray="4 4" />
                    <!-- Green flowing dashes on top -->
                    <line x1="0" y1="4" x2="48" y2="4" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="4 8" style="animation: fsu-flow-green 1.4s linear infinite;" />
                </svg>
                <style>
                    @keyframes fsu-flow-green {
                        to {
                            stroke-dashoffset: -12;
                        }
                    }
                </style>
            </div>
            
            <!-- Icon 2: Verification Badge -->
            <div class="relative flex items-center justify-center w-12 h-12 rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-sm transition-transform duration-300 hover:-translate-y-1">
                <svg class="w-6 h-6 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Title and Description -->
    <div class="max-w-md text-pretty text-center px-4">
        <span class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            {{ __('filament-short-url::default.empty_state_domain_heading') }}
        </span>
        <div class="mt-2 text-pretty text-sm text-neutral-500 dark:text-neutral-400">
            {{ __('filament-short-url::default.empty_state_domain_description') }}
        </div>
    </div>

    <!-- Create Button -->
    @if(!isset($hideCreateButton) || !$hideCreateButton)
    <div class="flex items-center gap-2">
        <button type="button"
                x-on:click="$wire.mountAction('create')"
                class="group flex h-10 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-black bg-black text-white hover:bg-neutral-800 dark:border-white dark:bg-white dark:text-black dark:hover:bg-neutral-100 hover:ring-4 hover:ring-neutral-200 dark:hover:ring-neutral-800/50 px-4 text-sm font-semibold transition-all cursor-pointer">
            <svg class="size-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>{{ __('filament-short-url::default.empty_state_domain_action') }}</span>
        </button>
    </div>
    @endif
</div>
