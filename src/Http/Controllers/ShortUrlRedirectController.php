<?php

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Jobs\TrackShortUrlVisitJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Services\AppLinkingEngine;
use Bjanczak\FilamentShortUrl\Services\ClientIpExtractor;
use Bjanczak\FilamentShortUrl\Services\ProxyDetectionService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\UserAgentParser;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlRedirectController extends Controller
{
    public function __construct(
        private readonly ShortUrlService $service,
        private readonly ProxyDetectionService $proxyDetector,
        private readonly UserAgentParser $uaParser,
    ) {}

    public function __invoke(Request $request, ?string $key = null): Response
    {
        if (empty($key)) {
            $key = $request->path();
        }

        $host = $request->getHost();
        $mainDomain = parse_url(config('app.url'), PHP_URL_HOST);
        $isCustomDomain = $host && strcasecmp($host, $mainDomain) !== 0;

        if ($isCustomDomain) {
            // Use a short-lived cache shared with ShortUrl::findByKey() to avoid two
            // separate DB round-trips for the same custom domain on a single request.
            $customDomain = cache()->remember(
                "filament-short-url:custom-domain:{$host}",
                300, // 5 minutes — invalidated by ShortUrlCustomDomain model events
                fn () => ShortUrlCustomDomain::where('domain', $host)
                    ->where('is_active', true)
                    ->first()
            );

            if (! $customDomain) {
                abort(404);
            }
        } else {
            $prefix = config('filament-short-url.route_prefix');
            if (! empty($prefix) && ! $request->route()?->named('short-url.redirect')) {
                abort(404);
            }
        }

        if (str_contains($key, '/')) {
            abort(404);
        }

        $shortUrl = ShortUrl::findByKey($key, $host);

        // 404 if not found
        if (! $shortUrl) {
            abort(404);
        }

        // Redirect to custom expiration URL if defined, otherwise 410 Gone if disabled or expired
        if (! $shortUrl->isActive()) {
            if ($shortUrl->isExpired() || ($shortUrl->deactivated_at && $shortUrl->deactivated_at->isPast())) {
                $expiredKey = "fsu:expired-webhook-sent:{$shortUrl->id}";
                if (cache()->add($expiredKey, true, 86400 * 30)) {
                    $shortUrl->dispatchWebhook('expired');
                }
            }

            if ($shortUrl->expiration_redirect_url && $shortUrl->is_enabled) {
                return redirect()->away($shortUrl->expiration_redirect_url, 302);
            }

            return response(view('filament-short-url::expired', [
                'shortUrl' => $shortUrl,
            ]), 410)->header('Content-Type', 'text/html');
        }

        // 1. VPN/Proxy & Bot Blocking Check
        if (config('filament-short-url.vpn_detection.enabled', false) && config('filament-short-url.vpn_detection.block_action') === 'block_with_403') {
            $ipAddress = ClientIpExtractor::getIp($request);
            $detection = $this->proxyDetector->detect($ipAddress);
            if ($detection['is_proxy'] || $detection['is_bot']) {
                abort(403, 'Access denied. VPN, Proxy, or automated scraping connection detected.');
            }
        }

        // 2. Rate Limiting Check
        if (config('filament-short-url.rate_limiting.enabled', false)) {
            $maxAttempts = (int) config('filament-short-url.rate_limiting.max_attempts', 60);
            $decaySeconds = (int) config('filament-short-url.rate_limiting.decay_seconds', 60);
            $ipAddress = ClientIpExtractor::getIp($request);
            $limiterKey = "short_url_limit:{$key}:".$ipAddress;

            if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
                $retryAfter = RateLimiter::availableIn($limiterKey);
                abort(429, 'Too many requests. Please try again in '.$retryAfter.' seconds.', [
                    'Retry-After' => $retryAfter,
                ]);
            }
            RateLimiter::hit($limiterKey, $decaySeconds);
        }

        // 3. Password Protection Check
        if (! empty($shortUrl->password)) {
            $queryString = $request->getQueryString();

            return redirect()->to(
                route('short-url.password-auth', ['key' => $key]).($queryString ? '?'.$queryString : ''),
                302
            );
        }

        // 4. Resolve Destination URL (evaluating targeting rules and forwarding query parameters)
        $destination = $this->service->resolveRedirectUrl($shortUrl, $request);

        // App Linking / Deep Links Auto-Open Check
        if ($shortUrl->auto_open_app_mobile) {
            $deviceType = $this->uaParser->getDeviceType($request->userAgent() ?? '');

            if ($deviceType === 'mobile' || $deviceType === 'tablet') {
                $matchedApp = AppLinkingEngine::matchApp($destination);
                if ($matchedApp !== null) {
                    $deepLink = AppLinkingEngine::convertToScheme($destination, $matchedApp);
                    $activePixels = $shortUrl->pixels->where('is_active', true);

                    return response(view('filament-short-url::app-redirect', [
                        'destination' => $destination,
                        'deepLink' => $deepLink,
                        'appId' => $matchedApp,
                        'pixels' => $activePixels,
                    ]))->header('Content-Type', 'text/html');
                }
            }
        }

        // 5. Warning / Intermediate Page Check
        if ($shortUrl->show_warning_page && ! $request->has('confirmed')) {
            return response(view('filament-short-url::warning', ['destinationUrl' => $destination]))
                ->header('Content-Type', 'text/html');
        }

        // 6. Track Visit
        if ($shortUrl->track_visits) {
            try {
                $connection = config('filament-short-url.queue_connection', 'sync');
                $ipAddress = ClientIpExtractor::getIp($request);
                $countryCode = ClientIpExtractor::getCountryCode($request);
                $city = ClientIpExtractor::getCity($request);

                $isQrScan = (bool) ($request->query('source') === 'qr' || $request->query('qr') === '1');
                $languages = $request->getLanguages();
                $browserLanguage = null;
                if (! empty($languages)) {
                    $parts = explode('-', str_replace('_', '-', $languages[0]));
                    $browserLanguage = strtolower(trim($parts[0]));
                    if (strlen($browserLanguage) > 5) {
                        $browserLanguage = substr($browserLanguage, 0, 5);
                    }
                }

                $selectedVariant = app()->bound('resolved_ab_variant') ? app('resolved_ab_variant') : null;

                $job = new TrackShortUrlVisitJob(
                    shortUrlId: $shortUrl->id,
                    ipAddress: $ipAddress,
                    userAgent: $request->userAgent() ?? '',
                    refererUrl: $request->header('Referer'),
                    countryCode: $countryCode,
                    city: $city,
                    utmSource: $request->query('utm_source'),
                    utmMedium: $request->query('utm_medium'),
                    utmCampaign: $request->query('utm_campaign'),
                    utmTerm: $request->query('utm_term'),
                    utmContent: $request->query('utm_content'),
                    isQrScan: $isQrScan,
                    browserLanguage: $browserLanguage,
                    selectedVariant: $selectedVariant,
                );

                if ($connection) {
                    dispatch($job->onConnection($connection));
                } else {
                    dispatch($job->onConnection('sync'));
                }
            } catch (\Throwable $e) {
                // Never let tracking failures block the redirection of the user!
                Log::error('[FilamentShortUrl] Redirect tracking failed', [
                    'url_key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Atomically disable single-use URLs — prevents race condition under concurrent load
        if ($shortUrl->single_use) {
            $affected = ShortUrl::where('id', $shortUrl->id)
                ->where('is_enabled', true)
                ->update(['is_enabled' => false]);

            // Another request beat us to it — this visit should 410
            if ($affected === 0) {
                return response(view('filament-short-url::expired', [
                    'shortUrl' => $shortUrl,
                ]), 410)->header('Content-Type', 'text/html');
            }

            // Clear ALL cache key variants for this url_key.
            // The redirect cache uses host-suffixed keys (e.g. "filament-short-url:{key}:{host}").
            // We must clear every variant to prevent a stale is_enabled=true cache entry
            // on a different host from allowing the link to be re-used after it is disabled.
            // This mirrors the logic in ShortUrl::saved().
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $hostsToForget = array_unique(array_filter([
                'default',
                $appHost,
                $request->getHost(),
                $shortUrl->custom_domain_id && $shortUrl->customDomain
                    ? $shortUrl->customDomain->domain
                    : null,
            ]));

            foreach ($hostsToForget as $h) {
                cache()->forget("filament-short-url:{$shortUrl->url_key}:{$h}");
            }
        }

        $isBot = $this->uaParser->getDeviceType($request->userAgent() ?? '') === 'robot';
        $hasCustomOg = ! empty($shortUrl->og_title) || ! empty($shortUrl->og_description) || ! empty($shortUrl->og_image);

        if ($shortUrl->is_cloaked || ($isBot && $hasCustomOg)) {
            $activePixels = $shortUrl->pixels->where('is_active', true);
            $pixelFired = $request->has('pixel_fired') || $request->has('confirmed');

            if ($activePixels->isNotEmpty() && ! $pixelFired && ! $isBot) {
                $queryString = $request->getQueryString();
                $append = 'pixel_fired=1';
                $nextUrl = $request->url().'?'.($queryString ? $queryString.'&'.$append : $append);

                return response(view('filament-short-url::pixel-loading', [
                    'destination' => $nextUrl,
                    'pixels' => $activePixels,
                ]))->header('Content-Type', 'text/html');
            }

            return response(view('filament-short-url::redirect-html', [
                'shortUrl' => $shortUrl,
                'destination' => $destination,
                'isBot' => $isBot,
            ]))->header('Content-Type', 'text/html');
        }

        $activePixels = $shortUrl->pixels->where('is_active', true);

        if ($activePixels->isNotEmpty() && ! $request->has('confirmed')) {
            return response(view('filament-short-url::pixel-loading', [
                'destination' => $destination,
                'pixels' => $activePixels,
            ]))->header('Content-Type', 'text/html');
        }

        return redirect()->away($destination, $shortUrl->redirect_status_code);
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
    public function scrapeMeta(Request $request): \Illuminate\Http\JsonResponse
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
        if (empty($key)) {
            $key = $request->path();
            $prefix = config('filament-short-url.route_prefix', 's').'-auth/';
            if (str_starts_with($key, $prefix)) {
                $key = substr($key, strlen($prefix));
            }
        }

        $host = $request->getHost();
        $shortUrl = ShortUrl::findByKey($key, $host);

        if (! $shortUrl || empty($shortUrl->password)) {
            abort(404);
        }

        if (! $shortUrl->isActive()) {
            if ($shortUrl->isExpired() || ($shortUrl->deactivated_at && $shortUrl->deactivated_at->isPast())) {
                $expiredKey = "fsu:expired-webhook-sent:{$shortUrl->id}";
                if (cache()->add($expiredKey, true, 86400 * 30)) {
                    $shortUrl->dispatchWebhook('expired');
                }
            }

            if ($shortUrl->expiration_redirect_url && $shortUrl->is_enabled) {
                return redirect()->away($shortUrl->expiration_redirect_url, 302);
            }

            return response(view('filament-short-url::expired', [
                'shortUrl' => $shortUrl,
            ]), 410)->header('Content-Type', 'text/html');
        }

        // VPN/Proxy & Bot Blocking Check
        if (config('filament-short-url.vpn_detection.enabled', false) && config('filament-short-url.vpn_detection.block_action') === 'block_with_403') {
            $ipAddress = ClientIpExtractor::getIp($request);
            $detection = $this->proxyDetector->detect($ipAddress);
            if ($detection['is_proxy'] || $detection['is_bot']) {
                abort(403, 'Access denied. VPN, Proxy, or automated scraping connection detected.');
            }
        }

        // Rate Limiting Check
        if (config('filament-short-url.rate_limiting.enabled', false)) {
            $maxAttempts = (int) config('filament-short-url.rate_limiting.max_attempts', 60);
            $decaySeconds = (int) config('filament-short-url.rate_limiting.decay_seconds', 60);
            $ipAddress = ClientIpExtractor::getIp($request);
            $limiterKey = "short_url_limit:{$key}:".$ipAddress;

            if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
                $retryAfter = RateLimiter::availableIn($limiterKey);
                abort(429, 'Too many requests. Please try again in '.$retryAfter.' seconds.', [
                    'Retry-After' => $retryAfter,
                ]);
            }
            RateLimiter::hit($limiterKey, $decaySeconds);
        }

        $sessionKey = "short-url-auth-{$shortUrl->id}";
        if (! session()->get($sessionKey)) {
            if ($request->isMethod('POST')) {
                // Password brute force protection
                $ipAddress = ClientIpExtractor::getIp($request);
                $passwordLimiterKey = "short_url_password_limit:{$key}:".$ipAddress;

                if (RateLimiter::tooManyAttempts($passwordLimiterKey, 5)) { // Max 5 attempts
                    $retryAfter = RateLimiter::availableIn($passwordLimiterKey);
                    abort(429, 'Too many incorrect password attempts. Please try again in '.$retryAfter.' seconds.', [
                        'Retry-After' => $retryAfter,
                    ]);
                }

                $submitted = $request->input('password');
                if ($submitted === $shortUrl->password) {
                    session()->put($sessionKey, true);
                    RateLimiter::clear($passwordLimiterKey);

                    // Redirect to the same URL to process final redirection steps
                    return redirect()->to($request->fullUrl());
                }

                RateLimiter::hit($passwordLimiterKey, 60); // 1 minute decay

                $errors = new MessageBag([
                    'password' => __('filament-short-url::default.password_error'),
                ]);

                return response(view('filament-short-url::password-prompt', ['errors' => $errors]))
                    ->header('Content-Type', 'text/html');
            }

            return response(view('filament-short-url::password-prompt'))
                ->header('Content-Type', 'text/html');
        }

        // Resolve Destination URL
        $destination = $this->service->resolveRedirectUrl($shortUrl, $request);

        // App Linking / Deep Links Auto-Open Check
        if ($shortUrl->auto_open_app_mobile) {
            $deviceType = $this->uaParser->getDeviceType($request->userAgent() ?? '');

            if ($deviceType === 'mobile' || $deviceType === 'tablet') {
                $matchedApp = AppLinkingEngine::matchApp($destination);
                if ($matchedApp !== null) {
                    $deepLink = AppLinkingEngine::convertToScheme($destination, $matchedApp);
                    $activePixels = $shortUrl->pixels->where('is_active', true);

                    return response(view('filament-short-url::app-redirect', [
                        'destination' => $destination,
                        'deepLink' => $deepLink,
                        'appId' => $matchedApp,
                        'pixels' => $activePixels,
                    ]))->header('Content-Type', 'text/html');
                }
            }
        }

        // Warning / Intermediate Page Check
        if ($shortUrl->show_warning_page && ! $request->has('confirmed')) {
            return response(view('filament-short-url::warning', ['destinationUrl' => $destination]))
                ->header('Content-Type', 'text/html');
        }

        // Track Visit
        if ($shortUrl->track_visits) {
            try {
                $connection = config('filament-short-url.queue_connection', 'sync');
                $ipAddress = ClientIpExtractor::getIp($request);
                $countryCode = ClientIpExtractor::getCountryCode($request);
                $city = ClientIpExtractor::getCity($request);

                $isQrScan = (bool) ($request->query('source') === 'qr' || $request->query('qr') === '1');
                $languages = $request->getLanguages();
                $browserLanguage = null;
                if (! empty($languages)) {
                    $parts = explode('-', str_replace('_', '-', $languages[0]));
                    $browserLanguage = strtolower(trim($parts[0]));
                    if (strlen($browserLanguage) > 5) {
                        $browserLanguage = substr($browserLanguage, 0, 5);
                    }
                }

                $selectedVariant = app()->bound('resolved_ab_variant') ? app('resolved_ab_variant') : null;

                $job = new TrackShortUrlVisitJob(
                    shortUrlId: $shortUrl->id,
                    ipAddress: $ipAddress,
                    userAgent: $request->userAgent() ?? '',
                    refererUrl: $request->header('Referer'),
                    countryCode: $countryCode,
                    city: $city,
                    utmSource: $request->query('utm_source'),
                    utmMedium: $request->query('utm_medium'),
                    utmCampaign: $request->query('utm_campaign'),
                    utmTerm: $request->query('utm_term'),
                    utmContent: $request->query('utm_content'),
                    isQrScan: $isQrScan,
                    browserLanguage: $browserLanguage,
                    selectedVariant: $selectedVariant,
                );

                if ($connection) {
                    dispatch($job->onConnection($connection));
                } else {
                    dispatch($job->onConnection('sync'));
                }
            } catch (\Throwable $e) {
                Log::error('[FilamentShortUrl] Redirect tracking failed', [
                    'url_key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Atomically disable single-use URLs
        if ($shortUrl->single_use) {
            $affected = ShortUrl::where('id', $shortUrl->id)
                ->where('is_enabled', true)
                ->update(['is_enabled' => false]);

            if ($affected === 0) {
                return response(view('filament-short-url::expired', [
                    'shortUrl' => $shortUrl,
                ]), 410)->header('Content-Type', 'text/html');
            }

            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $hostsToForget = array_unique(array_filter([
                'default',
                $appHost,
                $request->getHost(),
                $shortUrl->custom_domain_id && $shortUrl->customDomain
                    ? $shortUrl->customDomain->domain
                    : null,
            ]));

            foreach ($hostsToForget as $h) {
                cache()->forget("filament-short-url:{$shortUrl->url_key}:{$h}");
            }
        }

        $isBot = $this->uaParser->getDeviceType($request->userAgent() ?? '') === 'robot';
        $hasCustomOg = ! empty($shortUrl->og_title) || ! empty($shortUrl->og_description) || ! empty($shortUrl->og_image);

        if ($shortUrl->is_cloaked || ($isBot && $hasCustomOg)) {
            $activePixels = $shortUrl->pixels->where('is_active', true);
            $pixelFired = $request->has('pixel_fired') || $request->has('confirmed');

            if ($activePixels->isNotEmpty() && ! $pixelFired && ! $isBot) {
                $queryString = $request->getQueryString();
                $append = 'pixel_fired=1';
                $nextUrl = $request->url().'?'.($queryString ? $queryString.'&'.$append : $append);

                return response(view('filament-short-url::pixel-loading', [
                    'destination' => $nextUrl,
                    'pixels' => $activePixels,
                ]))->header('Content-Type', 'text/html');
            }

            return response(view('filament-short-url::redirect-html', [
                'shortUrl' => $shortUrl,
                'destination' => $destination,
                'isBot' => $isBot,
            ]))->header('Content-Type', 'text/html');
        }

        $activePixels = $shortUrl->pixels->where('is_active', true);

        if ($activePixels->isNotEmpty() && ! $request->has('confirmed')) {
            return response(view('filament-short-url::pixel-loading', [
                'destination' => $destination,
                'pixels' => $activePixels,
            ]))->header('Content-Type', 'text/html');
        }

        return redirect()->away($destination, $shortUrl->redirect_status_code);
    }
}
