<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Support;

use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Closure;
use Illuminate\Http\Request;

class CustomDomainValidator
{
    public static function ownershipClosure(?int $ownerUserId): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ownerUserId): void {
            if ($value === null || $value === '') {
                return;
            }

            $domain = ShortUrlCustomDomain::query()
                ->where('id', (int) $value)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->first();

            if (! $domain) {
                $fail(__('validation.exists', ['attribute' => $attribute]));

                return;
            }

            if ($ownerUserId === null || $domain->user_id === null) {
                return;
            }

            if ((int) $domain->user_id !== $ownerUserId) {
                $fail(__('filament-short-url::default.custom_domain_not_owned_error'));
            }
        };
    }

    public static function ownerUserIdFromRequest(Request $request): ?int
    {
        return LinkUserScope::resolveOwnerUserId($request);
    }
}
