<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Jobs\TrackShortUrlVisitJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Support\HostNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ShortUrlRedirectHandler
{
    public function __construct(
        private readonly ShortUrlService $service,
        private readonly ProxyDetectionService $proxyDetector,
        private readonly UserAgentParser $uaParser,
        private readonly BotDetector $botDetector,
        private readonly OgMetaPresenter $ogMetaPresenter,
        private readonly RobotsTagApplicator $robotsTagApplicator,
        private readonly SafeBrowsingService $safeBrowsingService,
        private readonly VisitSlotReservation $visitSlotReservation,
    ) {}

    /**
     * Resolve the URL key from the incoming request path.
     */
    public function resolveKey(Request $request, ?string $key, bool $passwordAuth = false): string
    {
        if (! empty($key)) {
            return $key;
        }

        $path = $request->path();

        if ($passwordAuth) {
            $prefix = config('filament-short-url.route_prefix', 's').'-auth/';
            if (str_starts_with($path, $prefix)) {
                return substr($path, strlen($prefix));
            }
        }

        return $path;
    }

    /**
     * Redirect legacy prefixed paths on custom domains (e.g. /s/key → /key).
     */
    public function resolveLegacyPrefixedRedirect(Request $request): ?Response
    {
        $host = HostNormalizer::normalize($request->getHost());
        $mainDomain = HostNormalizer::normalize(parse_url(config('app.url'), PHP_URL_HOST));

        if (! $host || ! $mainDomain || strcasecmp($host, $mainDomain) === 0) {
            return null;
        }

        $prefix = trim((string) config('filament-short-url.route_prefix', 's'), '/');
        if ($prefix === '') {
            return null;
        }

        $path = trim($request->path(), '/');
        $prefixWithSlash = $prefix.'/';

        if (! str_starts_with($path, $prefixWithSlash)) {
            return null;
        }

        $key = substr($path, strlen($prefixWithSlash));
        if ($key === '' || str_contains($key, '/')) {
            return null;
        }

        $queryString = $request->getQueryString();
        $target = '/'.$key.($queryString ? '?'.$queryString : '');

        return redirect()->to($target, 301);
    }

    /**
     * Validate host routing rules and load the short URL model.
     */
    public function findShortUrl(Request $request, string $key): ShortUrl
    {
        $host = HostNormalizer::normalize($request->getHost());
        $mainDomain = HostNormalizer::normalize(parse_url(config('app.url'), PHP_URL_HOST));
        $isCustomDomain = $host && strcasecmp($host, $mainDomain) !== 0;
        $resolvedDomain = false;

        if ($isCustomDomain) {
            $customDomain = cache()->remember(
                "filament-short-url:custom-domain:{$host}",
                300,
                fn () => ShortUrlCustomDomain::resolveForHost($host)
            );

            if (! $customDomain) {
                abort(404);
            }

            $resolvedDomain = $customDomain;
        } elseif (! empty(config('filament-short-url.route_prefix')) && ! $request->route()?->named('short-url.redirect')) {
            abort(404);
        }

        if (str_contains($key, '/')) {
            abort(404);
        }

        if (HostNormalizer::isReservedFallbackKey($key)) {
            abort(404);
        }

        $shortUrl = ShortUrl::findByKey($key, $host, $resolvedDomain);

        if (! $shortUrl) {
            abort(404);
        }

        return $shortUrl;
    }

    /**
     * Handle inactive or expired links.
     */
    public function respondIfInactive(ShortUrl $shortUrl): ?Response
    {
        if ($shortUrl->isActive()) {
            return null;
        }

        if ($shortUrl->isExpired() || ($shortUrl->deactivated_at && $shortUrl->deactivated_at->isPast())) {
            $expiredKey = "fsu:expired-webhook-sent:{$shortUrl->id}";
            if (cache()->add($expiredKey, true, 86400 * 30)) {
                $shortUrl->dispatchWebhook('expired');
            }
        }

        if ($shortUrl->expiration_redirect_url && $shortUrl->is_enabled) {
            return $this->robotsTagApplicator->applyToRedirect(
                $shortUrl,
                redirect()->away($shortUrl->expiration_redirect_url, 302),
            );
        }

        return $this->robotsTagApplicator->apply(
            $shortUrl,
            response(view('filament-short-url::expired', [
                'shortUrl' => $shortUrl,
            ]), 410)->header('Content-Type', 'text/html'),
        );
    }

    /**
     * Enforce VPN/proxy blocking and rate limiting.
     */
    public function enforceAccessGuards(string $key, Request $request): void
    {
        $proxyChecked = (bool) $request->attributes->get('fsu_proxy_checked', false);

        if (config('filament-short-url.vpn_detection.enabled', false) && config('filament-short-url.vpn_detection.block_action') === 'block_with_403') {
            if (! $proxyChecked) {
                $ipAddress = ClientIpExtractor::getIp($request);
                $detection = $this->proxyDetector->detect($ipAddress);
                $request->attributes->set('fsu_proxy_detection', $detection);
                $request->attributes->set('fsu_proxy_checked', true);
            } else {
                $detection = $request->attributes->get('fsu_proxy_detection', [
                    'is_proxy' => false,
                    'is_bot' => false,
                ]);
            }

            if ($detection['is_proxy'] || $detection['is_bot']) {
                if ($this->botDetector->isBot($request)) {
                    // Social preview crawlers often resolve from hosting/datacenter IPs.
                    // Allow them through so OG/cloaked pages still render for link previews.
                } else {
                    abort(403, __('filament-short-url::default.redirect_vpn_blocked'));
                }
            }
        }

        if (! config('filament-short-url.rate_limiting.enabled', false)) {
            return;
        }

        $maxAttempts = (int) config('filament-short-url.rate_limiting.max_attempts', 60);
        $decaySeconds = (int) config('filament-short-url.rate_limiting.decay_seconds', 60);
        $ipAddress = ClientIpExtractor::getIp($request);
        $limiterKey = "short_url_limit:{$key}:".$ipAddress;

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($limiterKey);
            abort(429, __('filament-short-url::default.redirect_rate_limited', ['seconds' => $retryAfter]), [
                'Retry-After' => $retryAfter,
            ]);
        }

        RateLimiter::hit($limiterKey, $decaySeconds);
    }

    public function assertDestinationIsSafe(string $destination): void
    {
        if (! $this->safeBrowsingService->isSafeCached($destination)) {
            abort(403, __('filament-short-url::default.redirect_destination_blocked'));
        }
    }

    /**
     * Build the final redirect, cloaked page, or OG preview response.
     */
    public function buildResponse(ShortUrl $shortUrl, Request $request, string $key, string $destination): Response
    {
        if (! $shortUrl->relationLoaded('pixels')) {
            $shortUrl->load('pixels');
        }

        if ($shortUrl->show_warning_page && ! $request->has('confirmed')) {
            return $this->robotsTagApplicator->apply(
                $shortUrl,
                response(view('filament-short-url::warning', ['destinationUrl' => $destination]))
                    ->header('Content-Type', 'text/html'),
            );
        }

        $singleUseResponse = $this->handleSingleUse($shortUrl, $request);

        if ($singleUseResponse !== null) {
            return $singleUseResponse;
        }

        if ($maxVisitsResponse = $this->enforceVisitLimits($shortUrl, $request)) {
            return $maxVisitsResponse;
        }

        $this->dispatchVisitTracking($shortUrl, $request, $key);

        if ($shortUrl->auto_open_app_mobile) {
            $appRedirect = $this->tryAppLinkRedirect($shortUrl, $request, $destination);

            if ($appRedirect !== null) {
                return $appRedirect;
            }
        }

        return $this->buildDestinationResponse($shortUrl, $request, $destination);
    }

    private function tryAppLinkRedirect(ShortUrl $shortUrl, Request $request, string $destination): ?Response
    {
        $deviceType = $this->uaParser->getDeviceType($request->userAgent() ?? '');

        if ($deviceType !== 'mobile' && $deviceType !== 'tablet') {
            return null;
        }

        $matchedApp = AppLinkingEngine::matchApp($destination);

        if ($matchedApp === null) {
            return null;
        }

        return $this->robotsTagApplicator->apply(
            $shortUrl,
            response(view('filament-short-url::app-redirect', [
                'destination' => $destination,
                'deepLink' => AppLinkingEngine::convertToScheme($destination, $matchedApp),
                'appId' => $matchedApp,
                'pixels' => $shortUrl->pixels->where('is_active', true),
            ]))->header('Content-Type', 'text/html'),
        );
    }

    private function dispatchVisitTracking(ShortUrl $shortUrl, Request $request, string $key): void
    {
        if (! $shortUrl->track_visits) {
            return;
        }

        try {
            $connection = config('filament-short-url.queue_connection', 'sync');
            $ipAddress = ClientIpExtractor::getIp($request);
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
                shortUrlId: $shortUrl->id,
                ipAddress: $ipAddress,
                userAgent: $request->userAgent() ?? '',
                refererUrl: $request->header('Referer'),
                countryCode: ClientIpExtractor::getCountryCode($request),
                city: ClientIpExtractor::getCity($request),
                utmSource: $request->query('utm_source'),
                utmMedium: $request->query('utm_medium'),
                utmCampaign: $request->query('utm_campaign'),
                utmTerm: $request->query('utm_term'),
                utmContent: $request->query('utm_content'),
                isQrScan: (bool) ($request->query('source') === 'qr' || $request->query('qr') === '1'),
                browserLanguage: $browserLanguage,
                selectedVariant: $request->attributes->get('fsu_resolved_ab_variant'),
                precomputedProxyDetection: $request->attributes->get('fsu_proxy_detection'),
                skipTotalIncrement: $this->visitSlotReservation->shouldSkipTotalIncrementInJob($shortUrl, $request),
            );

            dispatch($job->onConnection($connection ?: 'sync'));
        } catch (\Throwable $e) {
            Log::error('[FilamentShortUrl] Redirect tracking failed', [
                'url_key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleSingleUse(ShortUrl $shortUrl, Request $request): ?Response
    {
        if (! $shortUrl->single_use) {
            return null;
        }

        if ($this->botDetector->isBot($request)) {
            return null;
        }

        $affected = ShortUrl::where('id', $shortUrl->id)
            ->where('is_enabled', true)
            ->update(['is_enabled' => false]);

        if ($affected === 0) {
            return $this->robotsTagApplicator->apply(
                $shortUrl,
                response(view('filament-short-url::expired', [
                    'shortUrl' => $shortUrl,
                ]), 410)->header('Content-Type', 'text/html'),
            );
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

        foreach ($hostsToForget as $host) {
            cache()->forget("filament-short-url:{$shortUrl->url_key}:{$host}");
        }

        return null;
    }

    private function enforceVisitLimits(ShortUrl $shortUrl, Request $request): ?Response
    {
        if ($shortUrl->max_visits === null) {
            return null;
        }

        if ($this->visitSlotReservation->tryReserve($shortUrl, $request)) {
            $this->notifyIfVisitLimitReached($shortUrl);

            return null;
        }

        $shortUrl->refresh();

        if ($inactive = $this->respondIfInactive($shortUrl)) {
            return $inactive;
        }

        return $this->robotsTagApplicator->apply(
            $shortUrl,
            response(view('filament-short-url::expired', [
                'shortUrl' => $shortUrl,
            ]), 410)->header('Content-Type', 'text/html'),
        );
    }

    private function notifyIfVisitLimitReached(ShortUrl $shortUrl): void
    {
        if ($shortUrl->max_visits === null) {
            return;
        }

        $shortUrl->refresh();

        if ($shortUrl->getRealTimeTotalVisits() < $shortUrl->max_visits) {
            return;
        }

        $limitReachedKey = "fsu:limit-reached-webhook-sent:{$shortUrl->id}";

        if (cache()->add($limitReachedKey, true, 86400 * 30)) {
            $shortUrl->dispatchWebhook('limit_reached');
        }
    }

    private function buildDestinationResponse(ShortUrl $shortUrl, Request $request, string $destination): Response
    {
        $isBot = $this->botDetector->isBot($request);
        $hasCustomOg = ! empty($shortUrl->og_title) || ! empty($shortUrl->og_description) || ! empty($shortUrl->og_image);

        if ($shortUrl->is_cloaked || ($isBot && $hasCustomOg)) {
            return $this->respondWithOgHtml($shortUrl, $request, $destination, $isBot);
        }

        $activePixels = $shortUrl->pixels->where('is_active', true);

        if ($activePixels->isNotEmpty() && ! $request->has('confirmed')) {
            return $this->robotsTagApplicator->apply(
                $shortUrl,
                response(view('filament-short-url::pixel-loading', [
                    'destination' => $destination,
                    'pixels' => $activePixels,
                ]))->header('Content-Type', 'text/html'),
            );
        }

        return $this->robotsTagApplicator->applyToRedirect(
            $shortUrl,
            redirect()->away($destination, $shortUrl->redirect_status_code),
        );
    }

    private function respondWithOgHtml(ShortUrl $shortUrl, Request $request, string $destination, bool $isBot): Response
    {
        $activePixels = $shortUrl->pixels->where('is_active', true);
        $pixelFired = $request->has('pixel_fired') || $request->has('confirmed');

        if ($activePixels->isNotEmpty() && ! $pixelFired && ! $isBot) {
            $queryString = $request->getQueryString();
            $append = 'pixel_fired=1';
            $nextUrl = $request->url().'?'.($queryString ? $queryString.'&'.$append : $append);

            return $this->robotsTagApplicator->apply(
                $shortUrl,
                response(view('filament-short-url::pixel-loading', [
                    'destination' => $nextUrl,
                    'pixels' => $activePixels,
                ]))->header('Content-Type', 'text/html'),
            );
        }

        return $this->robotsTagApplicator->apply(
            $shortUrl,
            response(view('filament-short-url::redirect-html', [
                'shortUrl' => $shortUrl,
                'destination' => $destination,
                'isBot' => $isBot,
                'ogMeta' => $this->ogMetaPresenter->forShortUrl($shortUrl),
            ]))->header('Content-Type', 'text/html'),
        );
    }
}
