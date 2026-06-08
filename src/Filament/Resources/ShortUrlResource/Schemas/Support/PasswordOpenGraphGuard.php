<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Component;

class PasswordOpenGraphGuard
{
    public static function isFormPasswordProtected(Get $get): bool
    {
        return (bool) $get('password_active_flag') || filled($get('password'));
    }

    public static function isSaveDataPasswordProtected(array $data): bool
    {
        return filled($data['password'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitizeRecordDataForFill(array $data): array
    {
        if (! self::isSaveDataPasswordProtected($data)) {
            return $data;
        }

        return self::stripOpenGraphKeys($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitizeSaveData(array $data): array
    {
        if (! self::isSaveDataPasswordProtected($data)) {
            return $data;
        }

        return self::stripOpenGraphKeys($data);
    }

    public static function clearFormState(Set $set, ?Component $livewire = null): void
    {
        $set('og_title', null);
        $set('og_description', null);
        $set('og_image', null);
        $set('og_image_scraped', null);
        $set('is_scraping', false);

        if ($livewire !== null) {
            $livewire->js('window.fsuDispatchScraping(false); window.dispatchEvent(new CustomEvent("fsu-password-protection-changed", { detail: { protected: true } }))');
        }
    }

    public static function purgeOpenGraphMetadata(ShortUrl $shortUrl): void
    {
        $shortUrl->purgeOpenGraphMetadata();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function stripOpenGraphKeys(array $data): array
    {
        $data['og_title'] = null;
        $data['og_description'] = null;
        $data['og_image'] = null;

        return $data;
    }
}
