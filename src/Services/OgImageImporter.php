<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OgImageImporter
{
    private const int MAX_BYTES = 4_194_304;

    private const int TIMEOUT_SECONDS = 5;

    private const int MAX_REDIRECTS = 5;

    private const string USER_AGENT = 'Mozilla/5.0 (compatible; FilamentShortUrl/1.0; +https://github.com/janczakb/filament-short-url)';

    public function __construct(
        private readonly UrlMetaScraper $metaScraper,
        private readonly OgImageProcessor $imageProcessor,
        private readonly RedirectUrlResolver $redirectUrlResolver,
    ) {}

    /**
     * Download a remote OG image safely and store it for the FileUpload field.
     */
    public function importFromUrl(string $url): ?string
    {
        if (! $this->metaScraper->isScrapableUrl($url)) {
            return null;
        }

        $binary = $this->fetchImageBinary($url);

        if ($binary === null) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'og-import');

        if ($tempPath === false) {
            return null;
        }

        try {
            if (file_put_contents($tempPath, $binary) === false) {
                return null;
            }

            if (@getimagesize($tempPath) === false) {
                return null;
            }

            return $this->imageProcessor->storeWebpFromPath($tempPath, ShortUrlTempStorage::ROOT);
        } finally {
            @unlink($tempPath);
        }
    }

    private function fetchImageBinary(string $url): ?string
    {
        $currentUrl = $url;

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            if (! $this->metaScraper->isScrapableUrl($currentUrl)) {
                return null;
            }

            try {
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->withUserAgent(self::USER_AGENT)
                    ->withOptions(['allow_redirects' => false])
                    ->get($currentUrl);
            } catch (ConnectionException) {
                return null;
            } catch (Throwable) {
                return null;
            }

            if ($response->redirect()) {
                $location = $response->header('Location');

                if (! is_string($location) || $location === '') {
                    return null;
                }

                $currentUrl = $this->redirectUrlResolver->resolve($currentUrl, $location);

                continue;
            }

            if (! $response->successful()) {
                return null;
            }

            if (! $this->isAllowedImageContentType($response->header('Content-Type'))) {
                return null;
            }

            $body = $response->body();

            if ($body === '' || strlen($body) > self::MAX_BYTES) {
                return null;
            }

            return $body;
        }

        return null;
    }

    private function isAllowedImageContentType(?string $contentType): bool
    {
        if ($contentType === null || $contentType === '') {
            return true;
        }

        $mime = strtolower(trim(explode(';', $contentType)[0]));

        return in_array($mime, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif',
            'application/octet-stream',
        ], true);
    }
}
