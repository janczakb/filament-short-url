<?php

use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlApiController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlFolderApiController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlLiveFeedStreamController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlLogoController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlPublicStatsController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlRedirectController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlTagApiController;
use Bjanczak\FilamentShortUrl\Http\Controllers\ShortUrlUtilityController;
use Bjanczak\FilamentShortUrl\Http\Middleware\AuthenticateShortUrlApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

Route::match(
    ['GET', 'POST'],
    config('filament-short-url.route_prefix', 's').'/public-stats/{key}',
    [ShortUrlPublicStatsController::class, 'show']
)
    ->name('short-url.public-stats')
    ->where('key', '[a-zA-Z0-9_-]+')
    ->middleware(array_merge(['web'], ['throttle:60,1']));

Route::match(['GET', 'POST'], 'short-url/public-stats/{key}', [ShortUrlPublicStatsController::class, 'show'])
    ->where('key', '[a-zA-Z0-9_-]+')
    ->middleware(array_merge(['web'], ['throttle:60,1']));

Route::prefix('api/short-url')
    ->middleware([
        AuthenticateShortUrlApi::class,
    ])
    ->group(function () {
        Route::get('links/exists', [ShortUrlApiController::class, 'exists']);
        Route::get('links/random', [ShortUrlApiController::class, 'random']);
        Route::get('links/info', [ShortUrlApiController::class, 'info']);
        Route::put('links/upsert', [ShortUrlApiController::class, 'upsert']);
        Route::post('links/bulk', [ShortUrlApiController::class, 'bulkStore']);
        Route::post('links/bulk-delete', [ShortUrlApiController::class, 'bulkDestroy']);
        Route::patch('links/bulk-update', [ShortUrlApiController::class, 'bulkUpdate']);
        Route::get('tags', [ShortUrlTagApiController::class, 'index']);
        Route::post('tags', [ShortUrlTagApiController::class, 'store']);
        Route::match(['PUT', 'PATCH'], 'tags/{id}', [ShortUrlTagApiController::class, 'update']);
        Route::delete('tags/{id}', [ShortUrlTagApiController::class, 'destroy']);
        Route::get('folders', [ShortUrlFolderApiController::class, 'index']);
        Route::post('folders', [ShortUrlFolderApiController::class, 'store']);
        Route::match(['PUT', 'PATCH'], 'folders/{id}', [ShortUrlFolderApiController::class, 'update']);
        Route::delete('folders/{id}', [ShortUrlFolderApiController::class, 'destroy']);
        Route::get('links', [ShortUrlApiController::class, 'index']);
        Route::post('links', [ShortUrlApiController::class, 'store']);
        Route::get('links/{idOrKey}/visits/export', [ShortUrlApiController::class, 'exportVisits']);
        Route::get('links/{idOrKey}', [ShortUrlApiController::class, 'show']);
        Route::get('links/{idOrKey}/stats', [ShortUrlApiController::class, 'stats']);
        Route::get('links/{idOrKey}/visits', [ShortUrlApiController::class, 'visits']);
        Route::match(['PUT', 'PATCH'], 'links/{idOrKey}', [ShortUrlApiController::class, 'update']);
        Route::delete('links/{idOrKey}', [ShortUrlApiController::class, 'destroy']);
    });

Route::get('short-url/logo/{filename}', [ShortUrlLogoController::class, 'serveLogo'])
    ->name('short-url.logo');

Route::middleware(['web', 'auth', 'throttle:60,1'])
    ->get('short-url/live-feed/{shortUrl}/stream', ShortUrlLiveFeedStreamController::class)
    ->name('short-url.live-feed.stream');

Route::middleware(['web', 'auth', 'throttle:30,1'])
    ->post('short-url/log-error', function (Request $request) {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'file' => 'nullable|string|max:500',
            'line' => 'nullable|integer|min:0',
            'col' => 'nullable|integer|min:0',
            'stack' => 'nullable|string|max:8000',
        ]);

        Log::error('[FilamentShortUrl JS] '.$validated['message'], [
            'file' => $validated['file'] ?? null,
            'line' => $validated['line'] ?? null,
            'col' => $validated['col'] ?? null,
            'stack' => $validated['stack'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['status' => 'ok']);
    })
    ->name('short-url.log-error');

Route::middleware(['web', 'auth'])
    ->get('short-url/scrape-meta', [ShortUrlRedirectController::class, 'scrapeMeta'])
    ->name('short-url.scrape-meta');

Route::middleware(['web', 'auth'])
    ->post('short-url/check-iframeable', [ShortUrlUtilityController::class, 'checkIframeable'])
    ->name('short-url.check-iframeable');

if (config('filament-short-url.enable_fallback_route', true)) {
    Route::fallback(ShortUrlRedirectController::class)
        ->middleware(config('filament-short-url.middleware', ['throttle:120,1']));
}
