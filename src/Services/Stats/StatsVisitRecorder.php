<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services\Stats;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;

/**
 * Hot-path stats side effects for tracked visits — never busts full dashboard caches per click.
 */
class StatsVisitRecorder
{
    public function __construct(
        private readonly StatsScalingProfile $profile,
        private readonly TodayStatsBuffer $todayStatsBuffer,
    ) {}

    public function record(ShortUrl $shortUrl, ShortUrlVisit $visit, bool $isUnique): void
    {
        try {
            $this->todayStatsBuffer->recordVisit($shortUrl, $visit, $isUnique);
        } catch (\Throwable) {
            // Stats side effects must never block visit persistence or webhooks.
        }
    }
}
