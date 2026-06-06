<div class="flex flex-col items-center justify-center gap-6 px-4 py-10 md:min-h-[550px]">
    <!-- Folders Visual Grid -->
    <div class="relative flex items-center justify-center h-40 w-full max-w-xs md:max-w-md overflow-hidden rounded-2xl border border-neutral-200/60 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/30 p-6 shadow-inner">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:14px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)]"></div>
        
        <!-- 3D Folder Container -->
        <div class="relative w-48 h-32 flex items-center justify-center z-10" style="perspective: 1000px;">
            <!-- Folder Back -->
            <div class="absolute bottom-2 w-28 h-18 bg-neutral-300 dark:bg-neutral-800 rounded-lg shadow-sm border border-neutral-400/30 dark:border-neutral-700/50" style="transform: translateZ(-30px);">
                <!-- Folder Tab -->
                <div class="absolute -top-2 left-3 w-10 h-3 bg-neutral-300 dark:bg-neutral-800 rounded-t-md border-t border-x border-neutral-400/30 dark:border-neutral-700/50"></div>
            </div>

            <!-- Floating Document/Link Cards (Middle Layer) -->
            <!-- Card 1 -->
            <div class="absolute w-24 h-12 rounded-lg bg-gradient-to-br from-blue-500/90 to-indigo-600/90 text-white shadow-md border border-white/20 flex flex-col justify-between p-2 select-none"
                 style="animation: fsu-folder-card-1 4s infinite cubic-bezier(0.25, 1, 0.5, 1); transform-style: preserve-3d;">
                <div class="flex items-center gap-1.5">
                    <div class="w-3.5 h-3.5 rounded-full bg-white/25 flex items-center justify-center flex-shrink-0">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                    </div>
                    <div class="h-1.5 w-8 bg-white/30 rounded"></div>
                </div>
                <div class="h-1 w-12 bg-white/20 rounded"></div>
            </div>

            <!-- Card 2 -->
            <div class="absolute w-24 h-12 rounded-lg bg-gradient-to-br from-violet-500/90 to-purple-600/90 text-white shadow-md border border-white/20 flex flex-col justify-between p-2 select-none"
                 style="animation: fsu-folder-card-2 4s infinite cubic-bezier(0.25, 1, 0.5, 1); animation-delay: 2s; transform-style: preserve-3d;">
                <div class="flex items-center gap-1.5">
                    <div class="w-3.5 h-3.5 rounded-full bg-white/25 flex items-center justify-center flex-shrink-0">
                        <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                    </div>
                    <div class="h-1.5 w-8 bg-white/30 rounded"></div>
                </div>
                <div class="h-1 w-12 bg-white/20 rounded"></div>
            </div>

            <!-- Folder Front (Glassmorphic & Tilted) -->
            <div class="absolute bottom-2 w-28 h-16 bg-neutral-100/50 dark:bg-neutral-900/50 border border-white/30 dark:border-neutral-800/80 rounded-lg shadow-lg origin-bottom transition-all"
                 style="backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); transform: rotateX(-16deg); transform-style: preserve-3d; animation: fsu-folder-front 4s infinite ease-in-out; box-shadow: 0 12px 20px -8px rgba(0,0,0,0.15), 0 4px 6px -2px rgba(0,0,0,0.05);">
                <!-- Folder lock / branding line -->
                <div class="absolute top-2 left-3 w-6 h-1 bg-neutral-300 dark:bg-neutral-700 rounded-full"></div>
                <div class="absolute bottom-2 right-3 w-3 h-3 rounded-full border border-neutral-300 dark:border-neutral-750 flex items-center justify-center">
                    <div class="w-1 h-1 rounded-full bg-neutral-400 dark:bg-neutral-650"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fsu-folder-front {
            0%, 100% {
                transform: rotateX(-15deg);
            }
            50% {
                transform: rotateX(-22deg);
            }
        }
        @keyframes fsu-folder-card-1 {
            0% {
                transform: translate3d(35px, -60px, 10px) rotate(12deg) scale(0.8);
                opacity: 0;
            }
            15% {
                transform: translate3d(20px, -45px, 20px) rotate(6deg) scale(0.95);
                opacity: 1;
            }
            35% {
                transform: translate3d(0px, -20px, 0px) rotate(0deg) scale(1);
                opacity: 1;
            }
            55% {
                transform: translate3d(0px, 10px, -15px) rotate(-2deg) scale(0.9);
                opacity: 0.8;
            }
            75%, 100% {
                transform: translate3d(0px, 25px, -25px) rotate(-4deg) scale(0.75);
                opacity: 0;
            }
        }
        @keyframes fsu-folder-card-2 {
            0% {
                transform: translate3d(-35px, -60px, 10px) rotate(-12deg) scale(0.8);
                opacity: 0;
            }
            15% {
                transform: translate3d(-20px, -45px, 20px) rotate(-6deg) scale(0.95);
                opacity: 1;
            }
            35% {
                transform: translate3d(0px, -20px, 0px) rotate(0deg) scale(1);
                opacity: 1;
            }
            55% {
                transform: translate3d(0px, 10px, -15px) rotate(2deg) scale(0.9);
                opacity: 0.8;
            }
            75%, 100% {
                transform: translate3d(0px, 25px, -25px) rotate(4deg) scale(0.75);
                opacity: 0;
            }
        }
    </style>

    <!-- Title and Description -->
    <div class="max-w-md text-pretty text-center px-4">
        <span class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
            {{ __('filament-short-url::default.empty_state_folders_heading') }}
        </span>
        <div class="mt-2 text-pretty text-sm text-neutral-500 dark:text-neutral-400">
            {{ __('filament-short-url::default.empty_state_folders_description') }}
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
            <span>{{ __('filament-short-url::default.empty_state_folder_action') }}</span>
        </button>
    </div>
    @endif
</div>
