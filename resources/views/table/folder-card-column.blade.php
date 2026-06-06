@php
    $record = $getRecord();
    $color = $record->color ?? 'gray';
    
    // Map folder color to valid Tailwind classes matching the premium demo design
    $colorClasses = match ($color) {
        'gray' => [
            'border_outer' => 'border-neutral-200 dark:border-neutral-700',
            'bg_inner' => 'bg-neutral-100 dark:bg-neutral-800',
            'text' => 'text-neutral-600 dark:text-neutral-400',
            'badge_bg' => 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-350',
        ],
        'red' => [
            'border_outer' => 'border-red-200 dark:border-red-900/30',
            'bg_inner' => 'bg-red-100 dark:bg-red-950/40',
            'text' => 'text-red-800 dark:text-red-400',
            'badge_bg' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-350',
        ],
        'blue' => [
            'border_outer' => 'border-blue-200 dark:border-blue-900/30',
            'bg_inner' => 'bg-blue-100 dark:bg-blue-950/40',
            'text' => 'text-blue-800 dark:text-blue-400',
            'badge_bg' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-350',
        ],
        'green' => [
            'border_outer' => 'border-emerald-200 dark:border-emerald-900/30',
            'bg_inner' => 'bg-emerald-100 dark:bg-emerald-950/40',
            'text' => 'text-emerald-800 dark:text-emerald-400',
            'badge_bg' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-350',
        ],
        'yellow' => [
            'border_outer' => 'border-amber-200 dark:border-amber-900/30',
            'bg_inner' => 'bg-amber-100 dark:bg-amber-950/40',
            'text' => 'text-amber-800 dark:text-amber-400',
            'badge_bg' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-350',
        ],
        'indigo' => [
            'border_outer' => 'border-indigo-200 dark:border-indigo-900/30',
            'bg_inner' => 'bg-indigo-100 dark:bg-indigo-950/40',
            'text' => 'text-indigo-800 dark:text-indigo-400',
            'badge_bg' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-350',
        ],
        'purple' => [
            'border_outer' => 'border-purple-200 dark:border-purple-900/30',
            'bg_inner' => 'bg-purple-100 dark:bg-purple-950/40',
            'text' => 'text-purple-800 dark:text-purple-400',
            'badge_bg' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-750 dark:text-purple-350',
        ],
        'pink' => [
            'border_outer' => 'border-pink-200 dark:border-pink-900/30',
            'bg_inner' => 'bg-pink-100 dark:bg-pink-950/40',
            'text' => 'text-pink-800 dark:text-pink-400',
            'badge_bg' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-350',
        ],
        default => [
            'border_outer' => 'border-neutral-200 dark:border-neutral-700',
            'bg_inner' => 'bg-neutral-100 dark:bg-neutral-800',
            'text' => 'text-neutral-600 dark:text-neutral-400',
            'badge_bg' => 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-350',
        ],
    };
    
    $linkCount = $record->short_urls_count ?? 0;
    $linkString = $linkCount === 1 ? __('filament-short-url::default.link_count_one') : __('filament-short-url::default.link_count_many', ['count' => $linkCount]);
    if (str_contains($linkString, 'filament-short-url::')) {
        $linkString = $linkCount === 1 ? '1 link' : "{$linkCount} links";
    }
@endphp

<div class="flex flex-col justify-between h-full w-full select-none">

    <!-- Top Row: Icon Container -->
    <div class="flex items-center justify-between z-10 pointer-events-none">
        <div class="border rounded-full bg-white dark:bg-neutral-850 p-0.5 {{ $colorClasses['border_outer'] }}">
            <div class="rounded-full p-2 {{ $colorClasses['bg_inner'] }} {{ $colorClasses['text'] }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Title, Badges, Link count -->
    <div class="z-10 pointer-events-none">
        <span class="flex items-center justify-start gap-1.5 truncate text-sm font-semibold text-neutral-900 dark:text-neutral-100">
            <span class="truncate">{{ $record->name }}</span>
            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded {{ $colorClasses['badge_bg'] }} font-mono">
                /{{ $record->slug }}
            </span>
            @if($record->slug === 'default' || $record->slug === 'links')
                <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                    Default
                </span>
            @endif
        </span>
        <div class="mt-1.5 flex items-center gap-1 text-neutral-500 dark:text-neutral-400">
            <svg height="18" width="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5">
                <g fill="currentColor">
                    <ellipse cx="9" cy="9" fill="none" rx="7.25" ry="3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></ellipse>
                    <ellipse cx="9" cy="9" fill="none" rx="3" ry="7.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></ellipse>
                    <circle cx="9" cy="9" fill="none" r="7.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></circle>
                </g>
            </svg>
            <span class="text-sm font-normal">{{ $linkString }}</span>
        </div>
    </div>
</div>
