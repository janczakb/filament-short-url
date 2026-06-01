<?php

namespace Bjanczak\FilamentShortUrl\Events;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a short URL is visited and the visit has been recorded.
 *
 * @example
 * ```php
 * Event::listen(ShortUrlVisited::class, function (ShortUrlVisited $event) {
 *     logger($event->shortUrl->url_key.' visited from '.$event->visit->country);
 * });
 * ```
 */
class ShortUrlVisited
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ShortUrl $shortUrl,
        public readonly ShortUrlVisit $visit,
    ) {}
}
