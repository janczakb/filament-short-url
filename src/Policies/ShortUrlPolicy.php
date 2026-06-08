<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Policies;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Contracts\Auth\Authenticatable;

class ShortUrlPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return $this->owns($user, $shortUrl);
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return $this->owns($user, $shortUrl);
    }

    public function delete(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return $this->owns($user, $shortUrl);
    }

    public function restore(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return $this->owns($user, $shortUrl);
    }

    public function forceDelete(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        return $this->owns($user, $shortUrl);
    }

    public function manageSettings(Authenticatable $user): bool
    {
        return $this->viewAny($user);
    }

    private function owns(Authenticatable $user, ShortUrl $shortUrl): bool
    {
        if (! (bool) config('filament-short-url.scope_links_to_user', true)) {
            return true;
        }

        if (! config('filament-short-url.user.model')) {
            return true;
        }

        if ($shortUrl->user_id === null) {
            return false;
        }

        return (int) $shortUrl->user_id === (int) $user->getAuthIdentifier();
    }
}
