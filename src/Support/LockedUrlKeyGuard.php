<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Support;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Validation\ValidationException;

class LockedUrlKeyGuard
{
    public static function isEnabled(): bool
    {
        return (bool) config('filament-short-url.lock_url_key', false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitizeSaveData(array $data, ?ShortUrl $record = null): array
    {
        if (! self::isEnabled() || ! $record?->exists) {
            return $data;
        }

        $data['url_key'] = $record->url_key;
        $data['custom_domain_id'] = $record->custom_domain_id;

        return $data;
    }

    public static function assertModelCanPersistKeyChanges(ShortUrl $model): void
    {
        if (! self::isEnabled() || ! $model->exists) {
            return;
        }

        if ($model->isDirty('url_key') && $model->getOriginal('url_key') !== $model->url_key) {
            throw ValidationException::withMessages([
                'url_key' => __('filament-short-url::default.url_key_locked_error'),
            ]);
        }

        if ($model->isDirty('custom_domain_id')) {
            $original = $model->getOriginal('custom_domain_id');
            $current = $model->custom_domain_id;

            if ((int) ($original ?? 0) !== (int) ($current ?? 0)) {
                throw ValidationException::withMessages([
                    'custom_domain_id' => __('filament-short-url::default.custom_domain_locked_error'),
                ]);
            }
        }
    }
}
