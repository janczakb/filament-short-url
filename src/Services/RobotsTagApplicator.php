<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RobotsTagApplicator
{
    /**
     * Apply X-Robots-Tag when search indexing is disabled for the link.
     */
    public function apply(?ShortUrl $shortUrl, Response $response): Response
    {
        if ($shortUrl !== null && ! $shortUrl->do_index) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    public function applyToRedirect(?ShortUrl $shortUrl, RedirectResponse $response): RedirectResponse
    {
        /** @var RedirectResponse $applied */
        $applied = $this->apply($shortUrl, $response);

        return $applied;
    }
}
