@php
    $record = $getRecord();
    $color = $record->color ?? 'gray';

    // Map tag color to icon + badge colors — same hex values as the Select options
    $colorMap = match ($color) {
        'red'    => ['icon_bg' => '#fee2e2', 'icon_color' => '#ef4444'],
        'blue'   => ['icon_bg' => '#dbeafe', 'icon_color' => '#3b82f6'],
        'green'  => ['icon_bg' => '#d1fae5', 'icon_color' => '#10b981'],
        'yellow' => ['icon_bg' => '#fef3c7', 'icon_color' => '#f59e0b'],
        'indigo' => ['icon_bg' => '#e0e7ff', 'icon_color' => '#6366f1'],
        'purple' => ['icon_bg' => '#f3e8ff', 'icon_color' => '#a855f7'],
        'pink'   => ['icon_bg' => '#fce7f3', 'icon_color' => '#ec4899'],
        default  => ['icon_bg' => '#f3f4f6', 'icon_color' => '#737373'],
    };

    $linkCount = $record->short_urls_count ?? 0;
    $linkString = $linkCount === 1 ? __('filament-short-url::default.link_count_one') : __('filament-short-url::default.link_count_many', ['count' => $linkCount]);
    if (str_contains($linkString, 'filament-short-url::')) {
        $linkString = $linkCount === 1 ? '1 link' : "{$linkCount} links";
    }
@endphp

<div class="flex items-center justify-between w-full gap-3 select-none py-0.5">
    {{-- Left: icon + name --}}
    <div class="flex items-center gap-3 min-w-0">
        {{-- Colored tag icon --}}
        <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full"
             style="background-color: {{ $colorMap['icon_bg'] }};">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="{{ $colorMap['icon_color'] }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2H7a2 2 0 0 0-2 2v5.586a1 1 0 0 0 .293.707l9.414 9.414a2 2 0 0 0 2.828 0l4.172-4.172a2 2 0 0 0 0-2.828L12.707 2.293A1 1 0 0 0 12 2z"/>
                <circle cx="7.5" cy="7.5" r="1" fill="{{ $colorMap['icon_color'] }}" stroke="none"/>
            </svg>
        </div>

        {{-- Name --}}
        <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate">
            {{ $record->name }}
        </span>
    </div>

    {{-- Right: link count badge --}}
    <div class="flex items-center gap-1.5 flex-shrink-0 mr-2 border border-neutral-200 dark:border-neutral-800 rounded-full px-2.5 py-1 text-neutral-500 dark:text-neutral-400 text-xs font-medium bg-white dark:bg-neutral-900">
        <svg height="14" width="14" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0">
            <g fill="currentColor">
                <ellipse cx="9" cy="9" fill="none" rx="7.25" ry="3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></ellipse>
                <ellipse cx="9" cy="9" fill="none" rx="3" ry="7.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></ellipse>
                <circle cx="9" cy="9" fill="none" r="7.25" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"></circle>
            </g>
        </svg>
        <span>{{ $linkString }}</span>
    </div>
</div>
