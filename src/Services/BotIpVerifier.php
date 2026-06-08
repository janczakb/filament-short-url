<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Support\Facades\Cache;

class BotIpVerifier
{
    /**
     * Verify Googlebot using reverse DNS lookup (cached).
     */
    public function isVerifiedGoogleBot(?string $ip, ?string $userAgent): bool
    {
        if ($ip === null || $ip === '' || $userAgent === null) {
            return false;
        }

        if (stripos($userAgent, 'googlebot') === false) {
            return false;
        }

        $cacheKey = 'fsu:bot-ip-verify:google:'.hash('sha256', $ip);

        return Cache::remember($cacheKey, 86400, function () use ($ip): bool {
            $hostname = @gethostbyaddr($ip);

            if ($hostname === false || $hostname === $ip) {
                return false;
            }

            if (! str_ends_with(strtolower($hostname), '.googlebot.com') && ! str_ends_with(strtolower($hostname), '.google.com')) {
                return false;
            }

            $forward = @gethostbyname($hostname);

            return $forward === $ip;
        });
    }
}
