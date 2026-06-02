<?php

use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlRedirectController;
use Illuminate\Support\Facades\Route;

Route::match(
    ['GET', 'POST'],
    config('filament-short-url.route_prefix', 's').'/{key}',
    ShortUrlRedirectController::class
)
    ->name('short-url.redirect')
    ->where('key', '[a-zA-Z0-9_-]+')
    ->middleware(config('filament-short-url.middleware', ['web', 'throttle:120,1']));

Route::prefix('api/short-url')
    ->middleware([\Bjanczak\FilamentShortUrl\Http\Middleware\AuthenticateShortUrlApi::class])
    ->group(function () {
        Route::get('links', [\Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlApiController::class, 'index']);
        Route::post('links', [\Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlApiController::class, 'store']);
        Route::delete('links/{id}', [\Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlApiController::class, 'destroy']);
    });
