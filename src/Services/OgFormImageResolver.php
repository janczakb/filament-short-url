<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Support\Facades\Storage;

class OgFormImageResolver
{
    /**
     * Resolve a preview URL for the Filament form (uploaded file or scraped remote URL).
     */
    public function resolvePreviewUrl(mixed $ogImageState, mixed $scrapedUrl = null): ?string
    {
        $uploaded = $this->resolveUploadedUrl($ogImageState);

        if ($uploaded !== null) {
            return $uploaded;
        }

        if (! is_string($scrapedUrl) || blank($scrapedUrl)) {
            return null;
        }

        if (str_starts_with($scrapedUrl, 'http://') || str_starts_with($scrapedUrl, 'https://')) {
            return $scrapedUrl;
        }

        return null;
    }

    /**
     * Whether the form already has a user-uploaded OG image (not just a scraped URL).
     */
    public function hasUploadedImage(mixed $ogImageState): bool
    {
        return $this->resolveUploadedUrl($ogImageState) !== null;
    }

    private function resolveUploadedUrl(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_string($state)) {
            if (str_starts_with($state, 'http://') || str_starts_with($state, 'https://')) {
                return $state;
            }

            return Storage::disk('public')->url($state);
        }

        if (is_array($state)) {
            foreach ($state as $value) {
                $resolved = $this->resolveUploadedUrl($value);

                if ($resolved !== null) {
                    return $resolved;
                }
            }

            return null;
        }

        if (is_object($state) && method_exists($state, 'temporaryUrl')) {
            return $state->temporaryUrl();
        }

        return null;
    }
}
