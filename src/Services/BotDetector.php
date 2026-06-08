<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Http\Request;

class BotDetector
{
    public function __construct(
        private readonly BotIpVerifier $botIpVerifier,
    ) {}

    /** @var list<string>|null */
    private ?array $userAgentContains = null;

    /** @var list<string>|null */
    private ?array $userAgentRegex = null;

    /** @var list<string>|null */
    private ?array $refererContains = null;

    /**
     * Determine whether the request is from a crawler, preview bot, or automated client.
     */
    public function isBot(Request $request): bool
    {
        if ($request->query->has('bot')) {
            if ($this->allowsDebugBotQuery($request)) {
                return true;
            }
        }

        if ($request->isMethod('HEAD')) {
            return true;
        }

        $userAgent = $request->userAgent();

        if ($this->isBotUserAgent($userAgent)) {
            if (config('filament-short-url.bot_detection.verify_google_bot_ip', false)
                && stripos((string) $userAgent, 'googlebot') !== false) {
                return $this->botIpVerifier->isVerifiedGoogleBot(
                    $request->ip(),
                    $userAgent,
                );
            }

            return true;
        }

        return $this->isBotReferer($request->header('Referer'));
    }

    private function allowsDebugBotQuery(Request $request): bool
    {
        if (app()->environment('local', 'testing')) {
            return true;
        }

        $secret = config('filament-short-url.bot_detection.debug_secret');

        return is_string($secret)
            && $secret !== ''
            && hash_equals($secret, (string) $request->query('bot'));
    }

    public function isBotUserAgent(?string $userAgent): bool
    {
        if ($userAgent === null || $userAgent === '') {
            return false;
        }

        $normalized = strtolower($userAgent);

        foreach ($this->userAgentContainsPatterns() as $pattern) {
            if (str_contains($normalized, strtolower($pattern))) {
                return true;
            }
        }

        foreach ($this->userAgentRegexPatterns() as $pattern) {
            if (@preg_match($pattern, $userAgent) === 1) {
                return true;
            }
        }

        return false;
    }

    public function isBotReferer(?string $referer): bool
    {
        if ($referer === null || $referer === '') {
            return false;
        }

        $normalized = strtolower($referer);

        foreach ($this->refererContainsPatterns() as $pattern) {
            if (str_contains($normalized, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function userAgentContainsPatterns(): array
    {
        return $this->userAgentContains ??= config('filament-short-url.bot_detection.user_agent_contains', []);
    }

    /**
     * @return list<string>
     */
    private function userAgentRegexPatterns(): array
    {
        return $this->userAgentRegex ??= config('filament-short-url.bot_detection.user_agent_regex', []);
    }

    /**
     * @return list<string>
     */
    private function refererContainsPatterns(): array
    {
        return $this->refererContains ??= config('filament-short-url.bot_detection.referer_contains', []);
    }
}
