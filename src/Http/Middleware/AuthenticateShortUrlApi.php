<?php

namespace Bjanczak\FilamentShortUrl\Http\Middleware;

use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateShortUrlApi
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('filament-short-url.api_enabled', false)) {
            return response()->json([
                'error' => 'The Developer API is currently disabled. Enable it in Short URL Settings → API & Webhooks.',
            ], 503);
        }

        $apiKey = $request->header('X-Api-Key');

        if (!$apiKey && $auth = $request->header('Authorization')) {
            if (str_starts_with(strtolower($auth), 'bearer ')) {
                $apiKey = substr($auth, 7);
            }
        }

        if (empty($apiKey)) {
            return response()->json([
                'error' => 'Unauthorized. API Key is missing.',
            ], 401);
        }

        $mgr = app(ShortUrlSettingsManager::class);
        $keys = $mgr->get('api_keys', []);

        $valid = false;
        foreach ($keys as $keyObj) {
            if (($keyObj['key'] ?? '') === $apiKey && (bool) ($keyObj['is_active'] ?? false)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            return response()->json([
                'error' => 'Unauthorized. Invalid or inactive API Key.',
            ], 401);
        }

        return $next($request);
    }
}
