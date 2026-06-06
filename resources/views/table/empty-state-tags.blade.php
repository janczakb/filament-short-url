<div class="flex flex-col items-center justify-center gap-6 px-4 py-10 md:min-h-[550px]">
    <!-- Tags Visual Grid -->
    <div class="relative flex items-center justify-center h-40 w-full max-w-xs md:max-w-md overflow-hidden rounded-2xl border border-neutral-200/60 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/30 p-6 shadow-inner">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:14px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
        
        <!-- Orbiting Tags Container -->
        <div class="relative w-64 h-32 flex items-center justify-center z-10">
            <!-- Orbit Path Visual -->
            <svg class="absolute w-56 h-20 opacity-20 dark:opacity-30 text-neutral-300 dark:text-neutral-700" viewBox="0 0 220 80" fill="none">
                <ellipse cx="110" cy="40" rx="100" ry="32" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 4" />
            </svg>

            <!-- Central URL Card -->
            <div class="relative w-36 bg-white dark:bg-neutral-900 border border-neutral-200/80 dark:border-neutral-800 rounded-xl px-2.5 py-2 shadow-md flex items-center gap-2 select-none"
                 style="animation: fsu-tag-center-card-float 4s infinite ease-in-out; z-index: 10;">
                <div class="w-5 h-5 rounded-lg bg-purple-500/10 dark:bg-purple-500/20 flex items-center justify-center text-purple-600 dark:text-purple-400 flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                </div>
                <div class="flex flex-col overflow-hidden">
                    <span class="text-[10px] font-bold text-neutral-800 dark:text-neutral-200 font-mono leading-none truncate max-w-[85px] block" title="{{ parse_url(config('app.url'), PHP_URL_HOST) ?? request()->getHost() }}/{{ __('filament-short-url::default.empty_state_tags_mock_promo') }}">
                        {{ parse_url(config('app.url'), PHP_URL_HOST) ?? request()->getHost() }}/{{ __('filament-short-url::default.empty_state_tags_mock_promo') }}
                    </span>
                    <span class="text-[7.5px] text-neutral-400 dark:text-neutral-500 font-sans mt-0.5">{{ __('filament-short-url::default.empty_state_tags_mock_redirecting') }}</span>
                </div>
            </div>

            <!-- Orbiting Badges -->
            <!-- Tag 1: promo (Purple) -->
            <div class="absolute px-2 py-0.5 rounded-full text-[9px] font-semibold bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50 shadow-sm flex items-center gap-1 select-none"
                 style="animation: fsu-tag-orbit 8s infinite linear; animation-delay: 0s;">
                <span class="text-purple-400 font-bold">#</span>
                <span>{{ __('filament-short-url::default.empty_state_tags_mock_promo') }}</span>
            </div>

            <!-- Tag 2: social (Teal) -->
            <div class="absolute px-2 py-0.5 rounded-full text-[9px] font-semibold bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800/50 shadow-sm flex items-center gap-1 select-none"
                 style="animation: fsu-tag-orbit 8s infinite linear; animation-delay: -2.66s;">
                <span class="text-teal-400 font-bold">#</span>
                <span>{{ __('filament-short-url::default.empty_state_tags_mock_social') }}</span>
            </div>

            <!-- Tag 3: dev (Amber) -->
            <div class="absolute px-2 py-0.5 rounded-full text-[9px] font-semibold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50 shadow-sm flex items-center gap-1 select-none"
                 style="animation: fsu-tag-orbit 8s infinite linear; animation-delay: -5.33s;">
                <span class="text-amber-400 font-bold">#</span>
                <span>{{ __('filament-short-url::default.empty_state_tags_mock_dev') }}</span>
            </div>
        </div>
    </div>

    <style>
        @keyframes fsu-tag-center-card-float {
            0%, 100% {
                transform: translateY(0) rotate(-1deg);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            }
            50% {
                transform: translateY(-6px) rotate(1deg);
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            }
        }
        @keyframes fsu-tag-orbit {
            0% {
                transform: translate3d(-100px, 0px, 0px) scale(0.9);
                z-index: 2;
                opacity: 0.8;
            }
            4% {
                z-index: 20;
            }
            25% {
                transform: translate3d(0px, 32px, 0px) scale(1.05);
                z-index: 20;
                opacity: 1;
            }
            46% {
                z-index: 20;
            }
            50% {
                transform: translate3d(100px, 0px, 0px) scale(0.9);
                z-index: 2;
                opacity: 0.8;
            }
            54% {
                z-index: 2;
            }
            75% {
                transform: translate3d(0px, -32px, 0px) scale(0.8);
                z-index: 2;
                opacity: 0.5;
            }
            96% {
                z-index: 2;
            }
            100% {
                transform: translate3d(-100px, 0px, 0px) scale(0.9);
                z-index: 2;
                opacity: 0.8;
            }
        }
    </style>

    <!-- Title and Description -->
    <div class="max-w-md text-pretty text-center px-4">
        <span class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            {{ __('filament-short-url::default.empty_state_tags_heading') }}
        </span>
        <div class="mt-2 text-pretty text-sm text-neutral-500 dark:text-neutral-400">
            {{ __('filament-short-url::default.empty_state_tags_description') }}
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
            <span>{{ __('filament-short-url::default.empty_state_tag_action') }}</span>
        </button>
    </div>
    @endif
</div>
