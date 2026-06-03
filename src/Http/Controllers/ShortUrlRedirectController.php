<?php

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Jobs\TrackShortUrlVisitJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ClientIpExtractor;
use Bjanczak\FilamentShortUrl\Services\ProxyDetectionService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
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
    ) {}

    public function __invoke(Request $request, string $key): Response
    {
        $shortUrl = ShortUrl::findByKey($key);

        // 404 if not found
        if (! $shortUrl) {
            abort(404);
        }

        // Redirect to custom expiration URL if defined, otherwise 410 Gone if disabled or expired
        if (! $shortUrl->isActive()) {
            if ($shortUrl->expiration_redirect_url) {
                return redirect()->away($shortUrl->expiration_redirect_url, 302);
            }

            abort(410);
        }

        // 1. VPN/Proxy & Bot Blocking Check
        if (config('filament-short-url.vpn_detection.enabled', false) && config('filament-short-url.vpn_detection.block_action') === 'block_with_403') {
            $ipAddress = ClientIpExtractor::getIp($request);
            $proxyDetector = app(ProxyDetectionService::class);
            $detection = $proxyDetector->detect($ipAddress);
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
            $sessionKey = "short-url-auth-{$shortUrl->id}";
            if (! session()->get($sessionKey)) {
                if ($request->isMethod('POST')) {
                    $submitted = $request->input('password');
                    if ($submitted === $shortUrl->password) {
                        session()->put($sessionKey, true);

                        return redirect()->to($request->fullUrl());
                    }

                    $errors = new MessageBag([
                        'password' => __('filament-short-url::default.password_error') ?? 'Incorrect password.',
                    ]);

                    return response(view('filament-short-url::password-prompt', ['errors' => $errors]))
                        ->header('Content-Type', 'text/html');
                }

                return response(view('filament-short-url::password-prompt'))
                    ->header('Content-Type', 'text/html');
            }
        }

        // 4. Resolve Destination URL (evaluating targeting rules)
        $destination = $shortUrl->resolveDestinationUrl($request);

        // Forward query parameters if configured
        if ($shortUrl->forward_query_params) {
            $queryParams = $request->query();
            // Remove routing/auth parameters so they don't leak to destination
            unset($queryParams['confirmed'], $queryParams['password']);

            if (! empty($queryParams)) {
                $separator = str_contains($destination, '?') ? '&' : '?';
                $destination .= $separator.http_build_query($queryParams);
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

                $job = new TrackShortUrlVisitJob(
                    shortUrl: $shortUrl,
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
                abort(410);
            }

            // Manually forget cache since DB-level update does not trigger Eloquent events
            cache()->forget("filament-short-url:{$shortUrl->url_key}");
        }

        if (! empty($shortUrl->pixel_meta_id) || ! empty($shortUrl->pixel_google_id) || ! empty($shortUrl->pixel_linkedin_id)) {
            return response(view('filament-short-url::pixel-loading', [
                'destination' => $destination,
                'pixelMetaId' => $shortUrl->pixel_meta_id,
                'pixelGoogleId' => $shortUrl->pixel_google_id,
                'pixelLinkedinId' => $shortUrl->pixel_linkedin_id,
            ]))->header('Content-Type', 'text/html');
        }

        return redirect()->away($destination, $shortUrl->redirect_status_code);
    }
}
