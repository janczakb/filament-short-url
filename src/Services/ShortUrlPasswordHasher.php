<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Support\Facades\Hash;

class ShortUrlPasswordHasher
{
    public function hash(string $plain): string
    {
        return Hash::make($plain);
    }

    public function isHashed(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$2a$')
            || str_starts_with($value, '$2b$')
            || str_starts_with($value, '$argon2');
    }

    public function verify(string $plain, ?string $stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        if ($this->isHashed($stored)) {
            return Hash::check($plain, $stored);
        }

        // Legacy plaintext passwords — verify and allow re-hash on save.
        return hash_equals($stored, $plain);
    }
}
