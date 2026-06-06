<?php

use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlApiController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlLogoController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlRedirectController;
use Bjanczak\FilamentShortUrl\Http\Middleware\AuthenticateShortUrlApi;
use Illuminate\Support\Facades\Route;

Route::get('/.well-known/apple-app-site-association', [ShortUrlRedirectController::class, 'serveAasa'])
    ->middleware(['web']);
Route::get('/apple-app-site-association', [ShortUrlRedirectController::class, 'serveAasa'])
    ->middleware(['web']);
Route::get('/.well-known/assetlinks.json', [ShortUrlRedirectController::class, 'serveAssetLinks'])
    ->middleware(['web']);

Route::match(
    ['GET', 'POST'],
    config('filament-short-url.route_prefix', 's').'/{key}',
    ShortUrlRedirectController::class
)
    ->name('short-url.redirect')
    ->where('key', '[a-zA-Z0-9_-]+')
    ->middleware(config('filament-short-url.middleware', ['throttle:120,1']));

Route::match(
    ['GET', 'POST'],
    config('filament-short-url.route_prefix', 's').'-auth/{key}',
    [ShortUrlRedirectController::class, 'handlePasswordAuth']
)
    ->name('short-url.password-auth')
    ->where('key', '[a-zA-Z0-9_-]+')
    ->middleware(array_merge(['web'], config('filament-short-url.middleware', ['throttle:120,1'])));

Route::prefix('api/short-url')
    ->middleware([
        AuthenticateShortUrlApi::class,
    ])
    ->group(function () {
        Route::get('links', [ShortUrlApiController::class, 'index']);
        Route::post('links', [ShortUrlApiController::class, 'store']);
        Route::get('links/{idOrKey}', [ShortUrlApiController::class, 'show']);
        Route::get('links/{idOrKey}/stats', [ShortUrlApiController::class, 'stats']);
        Route::match(['PUT', 'PATCH'], 'links/{idOrKey}', [ShortUrlApiController::class, 'update']);
        Route::delete('links/{idOrKey}', [ShortUrlApiController::class, 'destroy']);
    });

Route::post('admin/short-url/upload-logo', [ShortUrlLogoController::class, 'uploadLogo'])
    ->name('short-url.upload-logo')
    ->middleware(['web']);

Route::get('short-url/logo/{filename}', [ShortUrlLogoController::class, 'serveLogo'])
    ->name('short-url.logo');

if (config('filament-short-url.enable_fallback_route', true)) {
    Route::fallback(ShortUrlRedirectController::class)
        ->middleware(config('filament-short-url.middleware', ['throttle:120,1']));
}
