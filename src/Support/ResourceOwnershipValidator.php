<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;

class ResourceOwnershipValidator
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function ownershipClosure(string $modelClass, ?int $ownerUserId, ?string $notOwnedMessage = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($modelClass, $ownerUserId, $notOwnedMessage): void {
            if ($value === null || $value === '') {
                return;
            }

            /** @var Model|null $record */
            $record = $modelClass::query()->whereKey((int) $value)->first();

            if (! $record) {
                $fail(__('validation.exists', ['attribute' => $attribute]));

                return;
            }

            if ($ownerUserId === null || ! isset($record->user_id) || $record->user_id === null) {
                return;
            }

            if ((int) $record->user_id !== $ownerUserId) {
                $fail($notOwnedMessage ?? __('filament-short-url::default.resource_not_owned_error'));
            }
        };
    }
}
