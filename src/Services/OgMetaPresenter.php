<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Support\Facades\Storage;

class OgMetaPresenter
{
    private const int DEFAULT_IMAGE_WIDTH = 1200;

    private const int DEFAULT_IMAGE_HEIGHT = 630;

    /**
     * Build Open Graph metadata for redirect-html rendering.
     *
     * @return array{
     *     title: string,
     *     description: ?string,
     *     image_url: ?string,
     *     image_width: ?int,
     *     image_height: ?int,
     *     canonical_url: string,
     *     site_name: string,
     *     short_url: string,
     * }
     */
    public function forShortUrl(ShortUrl $shortUrl): array
    {
        $title = $shortUrl->og_title ?: ($shortUrl->title ?: $shortUrl->url_key);
        $imageUrl = $this->resolveAbsoluteImageUrl($shortUrl->og_image);
        [$imageWidth, $imageHeight] = $this->resolveImageDimensions($shortUrl->og_image, $imageUrl);

        return [
            'title' => $title,
            'description' => filled($shortUrl->og_description) ? $shortUrl->og_description : null,
            'image_url' => $imageUrl,
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
            'canonical_url' => $shortUrl->getShortUrl(),
            'site_name' => (string) (config('filament-short-url.site_name') ?: config('app.name')),
            'short_url' => $shortUrl->getShortUrl(),
        ];
    }

    private function resolveAbsoluteImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $this->ensureHttps($path);
        }

        $relativeUrl = Storage::disk('public')->url($path);

        return $this->ensureHttps(url($relativeUrl));
    }

    private function ensureHttps(string $url): string
    {
        if (! str_starts_with($url, 'http://')) {
            return $url;
        }

        if (app()->isProduction()) {
            return 'https://'.substr($url, 7);
        }

        return $url;
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function resolveImageDimensions(?string $path, ?string $imageUrl): array
    {
        if (blank($path) || $imageUrl === null) {
            return [null, null];
        }

        if (! str_starts_with($path, 'http://') && ! str_starts_with($path, 'https://')) {
            $disk = Storage::disk('public');

            if ($disk->exists($path)) {
                $absolutePath = $disk->path($path);
                $size = @getimagesize($absolutePath);

                if (is_array($size)) {
                    return [(int) $size[0], (int) $size[1]];
                }
            }
        }

        return [self::DEFAULT_IMAGE_WIDTH, self::DEFAULT_IMAGE_HEIGHT];
    }
}
