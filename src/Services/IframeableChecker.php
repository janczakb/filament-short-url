<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class IframeableChecker
{
    private const int TIMEOUT_SECONDS = 5;

    private const int MAX_REDIRECTS = 3;

    public function __construct(
        private readonly OutboundUrlValidator $urlValidator,
    ) {}

    /**
     * Check whether a destination URL is likely embeddable in an iframe.
     */
    public function isIframeable(string $url): bool
    {
        if (! $this->urlValidator->isAllowed($url)) {
            return false;
        }

        try {
            $response = $this->requestWithRedirectValidation($url, 'HEAD');
        } catch (ConnectionException) {
            return false;
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful() && $response->status() !== 405) {
            try {
                $response = $this->requestWithRedirectValidation($url, 'GET', ['Range' => 'bytes=0-0']);
            } catch (Throwable) {
                return false;
            }

            if (! $response->successful()) {
                return false;
            }
        }

        return $this->responseAllowsIframe($response);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function requestWithRedirectValidation(string $url, string $method, array $headers = []): Response
    {
        $currentUrl = $url;

        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            if (! $this->urlValidator->isAllowed($currentUrl)) {
                throw new ConnectionException('Redirect target blocked by outbound URL policy.');
            }

            $pending = Http::timeout(self::TIMEOUT_SECONDS)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders($headers);

            $response = $method === 'HEAD'
                ? $pending->head($currentUrl)
                : $pending->get($currentUrl);

            if (! $response->redirect()) {
                return $response;
            }

            $location = $response->header('Location');

            if (empty($location)) {
                return $response;
            }

            $currentUrl = $this->resolveRedirectUrl($currentUrl, $location);
        }

        throw new ConnectionException('Too many redirects while checking iframeability.');
    }

    private function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return $location;
        }

        $parts = parse_url($baseUrl) ?: [];
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return $scheme.'://'.$host.$location;
        }

        $path = $parts['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');

        return $scheme.'://'.$host.($directory !== '' ? $directory.'/' : '/').ltrim($location, '/');
    }

    private function responseAllowsIframe(Response $response): bool
    {
        $xFrameOptions = strtolower((string) $response->header('X-Frame-Options'));
        if (in_array($xFrameOptions, ['deny', 'sameorigin'], true)) {
            return false;
        }

        $csp = strtolower((string) $response->header('Content-Security-Policy'));
        if ($csp !== '' && str_contains($csp, 'frame-ancestors') && ! str_contains($csp, 'frame-ancestors *')) {
            return false;
        }

        return true;
    }
}
