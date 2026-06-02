<?php

use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlApiController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlRedirectController;
use Bjanczak\FilamentShortUrl\Http\Middleware\AuthenticateShortUrlApi;
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
    ->middleware([AuthenticateShortUrlApi::class])
    ->group(function () {
        Route::get('links', [ShortUrlApiController::class, 'index']);
        Route::post('links', [ShortUrlApiController::class, 'store']);
        Route::delete('links/{id}', [ShortUrlApiController::class, 'destroy']);
    });
