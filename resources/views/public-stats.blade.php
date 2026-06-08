@extends('filament-short-url::public-stats-layout')

@section('title', __('filament-short-url::default.public_stats_page_title'))

@section('subtitle')
    {{ __('filament-short-url::default.public_stats_page_subtitle', ['key' => $shortUrl->url_key]) }}
@endsection

@section('content')
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3 rounded-2xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
        <div class="min-w-[140px] flex-1">
            <label for="date_from" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('filament-short-url::default.public_stats_date_from') }}</label>
            <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}"
                   class="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
        </div>
        <div class="min-w-[140px] flex-1">
            <label for="date_to" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ __('filament-short-url::default.public_stats_date_to') }}</label>
            <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}"
                   class="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900">
        </div>
        <button type="submit" class="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950 dark:hover:bg-neutral-200">
            {{ __('filament-short-url::default.public_stats_apply_filter') }}
        </button>
    </form>

    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ([
            'totalVisits' => __('filament-short-url::default.public_stats_total_visits'),
            'uniqueVisits' => __('filament-short-url::default.public_stats_unique_visits'),
            'visitsToday' => __('filament-short-url::default.public_stats_today'),
            'visitsThisWeek' => __('filament-short-url::default.public_stats_this_week'),
            'visitsThisMonth' => __('filament-short-url::default.public_stats_this_month'),
            'qrScans' => __('filament-short-url::default.public_stats_qr_scans'),
        ] as $metric => $label)
            <div class="rounded-2xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
                <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold tabular-nums">{{ number_format((int) ($stats[$metric] ?? 0)) }}</p>
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-neutral-500">{{ __('filament-short-url::default.public_stats_visits_by_day') }}</h2>

        @php($visitsByDay = $stats['visitsByDay'] ?? [])
        @if (empty($visitsByDay))
            <p class="text-sm text-neutral-500">{{ __('filament-short-url::default.public_stats_no_data') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-neutral-200 text-left text-neutral-500 dark:border-neutral-800">
                            <th class="py-2 pr-4 font-semibold">{{ __('filament-short-url::default.public_stats_day') }}</th>
                            <th class="py-2 font-semibold">{{ __('filament-short-url::default.public_stats_visits') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($visitsByDay as $day => $count)
                            <tr class="border-b border-neutral-100 dark:border-neutral-900">
                                <td class="py-2 pr-4">{{ $day }}</td>
                                <td class="py-2 tabular-nums">{{ number_format((int) $count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
