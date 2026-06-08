@extends('filament-short-url::public-stats-layout')

@section('title', __('filament-short-url::default.public_stats_password_title'))

@section('content')
    <div class="mx-auto flex w-full max-w-[360px] flex-col gap-6">
        <div class="text-center">
            <p class="text-xl font-medium">{{ __('filament-short-url::default.public_stats_password_title') }}</p>
            <p class="mt-1 text-sm text-neutral-500">{{ __('filament-short-url::default.public_stats_password_description') }}</p>
        </div>

        <form method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label for="password" class="mb-1 block text-sm font-medium">{{ __('filament-short-url::default.password_placeholder') }}</label>
                <input type="password" name="password" id="password" required autofocus
                       class="w-full rounded-lg border border-neutral-300 bg-neutral-50 px-3.5 py-2.5 text-sm focus:border-neutral-500 focus:outline-none dark:border-neutral-700 dark:bg-neutral-900">
                @if(isset($errors) && $errors->has('password'))
                    <p class="mt-1 text-sm text-red-600">{{ $errors->first('password') }}</p>
                @endif
            </div>
            <button type="submit" class="rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-950">
                {{ __('filament-short-url::default.public_stats_password_submit') }}
            </button>
        </form>
    </div>
@endsection
