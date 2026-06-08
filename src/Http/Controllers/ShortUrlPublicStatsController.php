<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Services\ShortUrlPasswordHasher;
use Bjanczak\FilamentShortUrl\Support\HostNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Illuminate\View\View;

class ShortUrlPublicStatsController extends Controller
{
    public function show(Request $request, string $key): JsonResponse|RedirectResponse|Response|View
    {
        $request->validate([
            'password' => 'nullable|string|max:255',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        if ($request->query->has('password')) {
            abort(403, __('filament-short-url::default.public_stats_password_invalid'));
        }

        $shortUrl = $this->resolveShortUrl($request, $key);

        if (! $shortUrl) {
            abort(404, __('filament-short-url::default.short_url_not_found'));
        }

        $statsLimiterKey = 'short_url_public_stats:'.$shortUrl->id.':'.sha1((string) $request->ip());
        if (RateLimiter::tooManyAttempts($statsLimiterKey, 10)) {
            return $this->rateLimitedResponse($request, RateLimiter::availableIn($statsLimiterKey));
        }
        RateLimiter::hit($statsLimiterKey, 60);

        if ($this->wantsJsonResponse($request)) {
            return $this->jsonResponse($request, $shortUrl);
        }

        return $this->htmlResponse($request, $shortUrl);
    }

    private function resolveShortUrl(Request $request, string $key): ?ShortUrl
    {
        $requestHost = HostNormalizer::normalize($request->getHost());
        $appHost = HostNormalizer::normalize(parse_url(config('app.url'), PHP_URL_HOST));

        $query = ShortUrl::query()->where('url_key', $key);

        if ($requestHost && $appHost && strcasecmp($requestHost, $appHost) !== 0) {
            $customDomain = ShortUrlCustomDomain::resolveForHost($requestHost);

            if (! $customDomain) {
                return null;
            }

            $query->where('domain_scope_id', $customDomain->id);
        } else {
            $query->where('domain_scope_id', 0);
        }

        $shortUrl = $query->first();

        if (! $shortUrl || ! $shortUrl->public_stats_enabled) {
            return null;
        }

        return $shortUrl;
    }

    private function wantsJsonResponse(Request $request): bool
    {
        if ($request->query('format') === 'json') {
            return true;
        }

        return $request->expectsJson() || $request->isJson();
    }

    private function jsonResponse(Request $request, ShortUrl $shortUrl): JsonResponse
    {
        if (! $this->verifyProtectedAccess($request, $shortUrl)) {
            return response()->json([
                'error' => __('filament-short-url::default.public_stats_password_invalid'),
            ], 403);
        }

        return response()->json([
            'data' => $this->publicStatsSubset(
                $shortUrl->getCachedStats(
                    dateFrom: $request->query('date_from'),
                    dateTo: $request->query('date_to'),
                )
            ),
        ]);
    }

    private function htmlResponse(Request $request, ShortUrl $shortUrl): RedirectResponse|Response|View
    {
        $sessionKey = $this->sessionKey($shortUrl);

        if (filled($shortUrl->public_stats_password) && ! session()->get($sessionKey)) {
            if ($request->isMethod('POST')) {
                $passwordLimiterKey = 'short_url_public_stats_password:'.$shortUrl->id.':'.sha1((string) $request->ip());

                if (RateLimiter::tooManyAttempts($passwordLimiterKey, 5)) {
                    abort(429, __('filament-short-url::default.public_stats_password_rate_limited'));
                }

                $password = (string) $request->input('password');

                if ($password !== '' && app(ShortUrlPasswordHasher::class)->verify($password, $shortUrl->public_stats_password)) {
                    session()->put($sessionKey, true);
                    RateLimiter::clear($passwordLimiterKey);

                    $query = array_filter($request->only(['date_from', 'date_to']));
                    $target = route('short-url.public-stats', ['key' => $shortUrl->url_key]);

                    if ($query !== []) {
                        $target .= '?'.http_build_query($query);
                    }

                    return redirect()->to($target);
                }

                RateLimiter::hit($passwordLimiterKey, 60);

                return response(view('filament-short-url::public-stats-password', [
                    'shortUrl' => $shortUrl,
                    'errors' => new MessageBag([
                        'password' => __('filament-short-url::default.public_stats_password_invalid'),
                    ]),
                ]))->header('Content-Type', 'text/html');
            }

            return response(view('filament-short-url::public-stats-password', [
                'shortUrl' => $shortUrl,
            ]))->header('Content-Type', 'text/html');
        }

        $stats = $shortUrl->getCachedStats(
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
        );

        return response(view('filament-short-url::public-stats', [
            'shortUrl' => $shortUrl,
            'stats' => $this->publicStatsSubset($stats),
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
        ]))->header('Content-Type', 'text/html');
    }

    private function verifyProtectedAccess(Request $request, ShortUrl $shortUrl): bool
    {
        if (blank($shortUrl->public_stats_password)) {
            return true;
        }

        $password = null;

        if ($request->isMethod('POST')) {
            $password = $request->input('password');
        }

        if ((! is_string($password) || $password === '') && $request->hasHeader('Authorization')) {
            $authorization = (string) $request->header('Authorization');
            if (str_starts_with(strtolower($authorization), 'bearer ')) {
                $password = trim(substr($authorization, 7));
            } else {
                $password = $authorization;
            }
        }

        return is_string($password)
            && $password !== ''
            && app(ShortUrlPasswordHasher::class)->verify($password, $shortUrl->public_stats_password);
    }

    private function sessionKey(ShortUrl $shortUrl): string
    {
        return "short-url-public-stats-{$shortUrl->id}";
    }

    private function rateLimitedResponse(Request $request, int $retryAfter): JsonResponse|Response
    {
        if ($this->wantsJsonResponse($request)) {
            return response()->json([
                'error' => __('filament-short-url::default.public_stats_rate_limited'),
                'retry_after' => $retryAfter,
            ], 429);
        }

        return response(
            view('filament-short-url::public-stats-rate-limited', ['retryAfter' => $retryAfter]),
            429
        )->header('Content-Type', 'text/html');
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>
     */
    private function publicStatsSubset(array $stats): array
    {
        return [
            'totalVisits' => $stats['totalVisits'] ?? 0,
            'uniqueVisits' => $stats['uniqueVisits'] ?? 0,
            'visitsToday' => $stats['visitsToday'] ?? 0,
            'visitsThisWeek' => $stats['visitsThisWeek'] ?? 0,
            'visitsThisMonth' => $stats['visitsThisMonth'] ?? 0,
            'visitsByDay' => $stats['visitsByDay'] ?? [],
            'qrScans' => $stats['qrScans'] ?? 0,
        ];
    }
}
