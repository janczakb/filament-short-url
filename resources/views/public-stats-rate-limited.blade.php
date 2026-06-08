@extends('filament-short-url::public-stats-layout')

@section('title', __('filament-short-url::default.public_stats_rate_limited'))

@section('content')
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center dark:border-amber-900 dark:bg-amber-950/40">
        <p class="text-lg font-semibold">{{ __('filament-short-url::default.public_stats_rate_limited') }}</p>
        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
            {{ __('filament-short-url::default.public_stats_retry_in', ['seconds' => $retryAfter]) }}
        </p>
    </div>
@endsection
