<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

class OutboundUrlValidator
{
    public function __construct(
        private readonly UrlMetaScraper $metaScraper,
    ) {}

    /**
     * Validate that an outbound URL is safe to request (HTTP(S) + SSRF checks).
     */
    public function isAllowed(string $url): bool
    {
        return $this->metaScraper->isAllowedOutboundUrl($url);
    }
}
