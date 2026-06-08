@php
    $publicStatsUrl = $record->getPublicStatsUrl();
    $copiedMsg = __('filament-short-url::default.public_stats_copied');
    $copyBtnText = __('filament-short-url::default.share_copy');
    $inputId = 'public_stats_url_'.$record->id;
@endphp

<div class="space-y-2">
    @if (! $record->public_stats_enabled)
        <p class="text-sm text-amber-600 dark:text-amber-400">
            {{ __('filament-short-url::default.public_stats_save_to_activate') }}
        </p>
    @else
        <p class="text-sm text-emerald-600 dark:text-emerald-400">
            {{ __('filament-short-url::default.public_stats_enabled_status') }}
        </p>
    @endif

    <div class="flex items-center gap-2">
        <input type="text"
               readonly
               value="{{ e($publicStatsUrl) }}"
               id="{{ $inputId }}"
               class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-100">

        <button type="button"
                onclick="
                    const input = document.getElementById('{{ $inputId }}');
                    input.select();
                    navigator.clipboard.writeText(input.value);
                    if (typeof FilamentNotification !== 'undefined') {
                        new FilamentNotification().title('{{ e($copiedMsg) }}').success().send();
                    }
                "
                class="inline-flex flex-shrink-0 items-center justify-center gap-1.5 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-950 dark:hover:bg-white">
            {{ $copyBtnText }}
        </button>

        <a href="{{ $publicStatsUrl }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex flex-shrink-0 items-center justify-center rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
            {{ __('filament-short-url::default.public_stats_open') }}
        </a>
    </div>
</div>
