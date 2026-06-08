<?php

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ClientIpExtractor;
use Bjanczak\FilamentShortUrl\Services\RobotsTagApplicator;
use Bjanczak\FilamentShortUrl\Services\ShortUrlRedirectHandler;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Support\HostNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlRedirectController extends Controller
{
    public function __construct(
        private readonly ShortUrlService $service,
        private readonly ShortUrlRedirectHandler $redirectHandler,
        private readonly RobotsTagApplicator $robotsTagApplicator,
    ) {}

    public function __invoke(Request $request, ?string $key = null): Response
    {
        if ($legacyRedirect = $this->redirectHandler->resolveLegacyPrefixedRedirect($request)) {
            return $legacyRedirect;
        }

        $key = $this->redirectHandler->resolveKey($request, $key);
        $shortUrl = $this->redirectHandler->findShortUrl($request, $key);

        if ($inactive = $this->redirectHandler->respondIfInactive($shortUrl)) {
            return $inactive;
        }

        $this->redirectHandler->enforceAccessGuards($key, $request);

        if ($shortUrl->hasPassword()) {
            $queryString = $request->getQueryString();

            return $this->robotsTagApplicator->applyToRedirect(
                $shortUrl,
                redirect()->to(
                    $shortUrl->passwordAuthUrl($request).($queryString ? '?'.$queryString : ''),
                    302
                ),
            );
        }

        $destination = $this->service->resolveRedirectUrl($shortUrl, $request);

        $this->redirectHandler->assertDestinationIsSafe($destination);

        return $this->redirectHandler->buildResponse($shortUrl, $request, $key, $destination);
    }

    /**
     * Serve the Apple App Site Association (AASA) file for iOS Universal Links.
     */
    public function serveAasa(Request $request): Response
    {
        if (! config('filament-short-url.deep_linking.enabled', false)) {
            abort(404);
        }

        $aasaJson = config('filament-short-url.deep_linking.aasa_json');

        if (empty($aasaJson)) {
            abort(404);
        }

        $minified = cache()->remember('fsu:deep-linking:aasa', 604800, function () use ($aasaJson): string {
            try {
                $decoded = json_decode($aasaJson, true, 512, JSON_THROW_ON_ERROR);

                return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\Throwable) {
                return $aasaJson;
            }
        });

        return response($minified, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'public, max-age=604800, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Serve the Asset Links file for Android App Links.
     */
    public function serveAssetLinks(Request $request): Response
    {
        if (! config('filament-short-url.deep_linking.enabled', false)) {
            abort(404);
        }

        $assetlinksJson = config('filament-short-url.deep_linking.assetlinks_json');

        if (empty($assetlinksJson)) {
            abort(404);
        }

        $minified = cache()->remember('fsu:deep-linking:assetlinks', 604800, function () use ($assetlinksJson): string {
            try {
                $decoded = json_decode($assetlinksJson, true, 512, JSON_THROW_ON_ERROR);

                return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\Throwable) {
                return $assetlinksJson;
            }
        });

        return response($minified, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'public, max-age=604800, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * Async endpoint to scrape metadata for a URL.
     */
    public function scrapeMeta(Request $request): JsonResponse
    {
        $url = $request->query('url');
        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }

        $meta = $this->service->scrapeMetaTags($url);

        return response()->json($meta);
    }

    /**
     * Handle stateful password authentication and redirection.
     */
    public function handlePasswordAuth(Request $request, ?string $key = null): Response
    {
        $key = $this->redirectHandler->resolveKey($request, $key, passwordAuth: true);
        $shortUrl = ShortUrl::findByKey($key, HostNormalizer::normalize($request->getHost()));

        if (! $shortUrl || ! $shortUrl->hasPassword()) {
            abort(404);
        }

        if ($inactive = $this->redirectHandler->respondIfInactive($shortUrl)) {
            return $inactive;
        }

        $this->redirectHandler->enforceAccessGuards($key, $request);

        $sessionKey = "short-url-auth-{$shortUrl->id}";

        if (! session()->get($sessionKey)) {
            if ($request->isMethod('POST')) {
                $ipAddress = ClientIpExtractor::getIp($request);
                $passwordLimiterKey = "short_url_password_limit:{$key}:".$ipAddress;

                if (RateLimiter::tooManyAttempts($passwordLimiterKey, 5)) {
                    $retryAfter = RateLimiter::availableIn($passwordLimiterKey);
                    abort(429, 'Too many incorrect password attempts. Please try again in '.$retryAfter.' seconds.', [
                        'Retry-After' => $retryAfter,
                    ]);
                }

                if ($shortUrl->verifyPassword((string) $request->input('password'))) {
                    session()->put($sessionKey, true);
                    RateLimiter::clear($passwordLimiterKey);

                    return redirect()->to($request->fullUrl());
                }

                RateLimiter::hit($passwordLimiterKey, 60);

                return $this->robotsTagApplicator->apply(
                    $shortUrl,
                    response(view('filament-short-url::password-prompt', [
                        'errors' => new MessageBag([
                            'password' => __('filament-short-url::default.password_error'),
                        ]),
                    ]))->header('Content-Type', 'text/html'),
                );
            }

            return $this->robotsTagApplicator->apply(
                $shortUrl,
                response(view('filament-short-url::password-prompt'))
                    ->header('Content-Type', 'text/html'),
            );
        }

        $destination = $this->service->resolveRedirectUrl($shortUrl, $request);

        $this->redirectHandler->assertDestinationIsSafe($destination);

        return $this->redirectHandler->buildResponse($shortUrl, $request, $key, $destination);
    }
}
