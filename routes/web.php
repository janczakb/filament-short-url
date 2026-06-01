<?php

use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlRedirectController;
use Illuminate\Support\Facades\Route;

Route::get(
    config('filament-short-url.route_prefix', 's').'/{key}',
    ShortUrlRedirectController::class
)
    ->name('short-url.redirect')
    ->where('key', '[a-zA-Z0-9_-]+')
    ->middleware(config('filament-short-url.middleware', ['web', 'throttle:120,1']));
