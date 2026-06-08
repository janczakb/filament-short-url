<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Support;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiLinkScope
{
    /**
     * Scope link queries to the authenticated API key owner when configured.
     *
     * @return Builder<ShortUrl>
     */
    public static function query(Request $request): Builder
    {
        $query = ShortUrl::query();

        /** @var array<string, mixed>|null $apiKey */
        $apiKey = $request->attributes->get('fsu_api_key');

        if ((bool) config('filament-short-url.scope_links_to_user', true)) {
            if (! is_array($apiKey) || empty($apiKey['owner_user_id'])) {
                return $query->whereRaw('0 = 1');
            }

            $query->where('user_id', (int) $apiKey['owner_user_id']);
        }

        return $query;
    }

    public static function find(Request $request, string|int $idOrKey): ShortUrl
    {
        $query = static::query($request)->with(['pixels', 'customDomain', 'tags', 'folder']);

        if (is_numeric($idOrKey)) {
            return $query->findOrFail((int) $idOrKey);
        }

        $link = $query->where('url_key', $idOrKey)->first();

        if (! $link) {
            abort(404, __('filament-short-url::default.short_url_not_found'));
        }

        return $link;
    }
}
