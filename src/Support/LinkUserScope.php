<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Support;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LinkUserScope
{
    public static function enabled(): bool
    {
        return (bool) config('filament-short-url.scope_links_to_user', true)
            && config('filament-short-url.user.model')
            && auth()->check();
    }

    /**
     * @param  Builder<ShortUrl>  $query
     * @return Builder<ShortUrl>
     */
    public static function applyToQuery(Builder $query): Builder
    {
        if (! self::enabled()) {
            return $query;
        }

        return $query->where('user_id', auth()->id());
    }

    public static function currentUserId(): ?int
    {
        return auth()->check() ? (int) auth()->id() : null;
    }

    public static function resolveOwnerUserId(Request $request): ?int
    {
        /** @var array<string, mixed>|null $apiKey */
        $apiKey = $request->attributes->get('fsu_api_key');

        if (is_array($apiKey) && ! empty($apiKey['owner_user_id'])) {
            return (int) $apiKey['owner_user_id'];
        }

        return self::currentUserId();
    }
}
