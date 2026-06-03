<?php

use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlApiController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlRedirectController;
use Bjanczak\FilamentShortUrl\Http\Middleware\AuthenticateShortUrlApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

Route::post('admin/short-url/upload-logo', [ShortUrlApiController::class, 'uploadLogo'])
    ->name('short-url.upload-logo')
    ->middleware(['web']);

Route::post('admin/short-url/log-debug', function (Request $request) {
    Log::info('QR DESIGNER JS DEBUG: '.json_encode($request->all()));

    return response()->json(['status' => 'ok']);
})->middleware(['web']);

Route::get('short-url/logo/{filename}', [ShortUrlApiController::class, 'serveLogo'])
    ->name('short-url.logo');
