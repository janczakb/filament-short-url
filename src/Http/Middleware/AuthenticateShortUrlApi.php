<?php

namespace Bjanczak\FilamentShortUrl\Http\Middleware;

use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateShortUrlApi
{
    public function __construct(
        private readonly ShortUrlSettingsManager $mgr,
    ) {}

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

        if (! $apiKey && $auth = $request->header('Authorization')) {
            if (str_starts_with(strtolower($auth), 'bearer ')) {
                $apiKey = substr($auth, 7);
            }
        }

        if (empty($apiKey)) {
            return response()->json([
                'error' => 'Unauthorized. API Key is missing.',
            ], 401);
        }

        $keys = $this->mgr->get('api_keys', []);

        $valid = false;
        $matchedKey = null;
        $hashedInput = hash('sha256', $apiKey);

        foreach ($keys as $keyObj) {
            if (! (bool) ($keyObj['is_active'] ?? false)) {
                continue;
            }

            // Check hashed key (new format) or fall back to plaintext key (legacy format)
            $storedHash = $keyObj['hashed_key'] ?? null;
            if ($storedHash) {
                if (hash_equals($storedHash, $hashedInput)) {
                    $valid = true;
                    $matchedKey = $keyObj;
                    break;
                }
            } else {
                $storedPlain = $keyObj['key'] ?? '';
                if ($storedPlain !== '' && hash_equals($storedPlain, $apiKey)) {
                    $valid = true;
                    $matchedKey = $keyObj;
                    break;
                }
            }
        }

        if (! $valid || ! $matchedKey) {
            return response()->json([
                'error' => 'Unauthorized. Invalid or inactive API Key.',
            ], 401);
        }

        // 1. API Scope Authorization (backward compatible with read-write)
        $scope = $matchedKey['scope'] ?? 'links:read-write';
        if ($scope === 'links:read-only' && ! $request->isMethod('GET')) {
            return response()->json([
                'error' => 'Forbidden. This API key has read-only permissions.',
            ], 403);
        }

        // 2. Per-Key Rate Limiting (default to 60 rpm if unspecified, 0 is Unlimited)
        $rateLimit = isset($matchedKey['rate_limit']) ? (int) $matchedKey['rate_limit'] : 60;
        if ($rateLimit > 0) {
            $keyIdentifier = $matchedKey['hashed_key'] ?? hash('sha256', $apiKey);
            $limiterKey = "fsu_api_key_limit:{$keyIdentifier}";

            if (RateLimiter::tooManyAttempts($limiterKey, $rateLimit)) {
                $retryAfter = RateLimiter::availableIn($limiterKey);

                return response()->json([
                    'error' => 'Too many requests. API key rate limit exceeded.',
                ], 429, [
                    'Retry-After' => $retryAfter,
                ]);
            }

            RateLimiter::hit($limiterKey, 60);
        }

        return $next($request);
    }
}
