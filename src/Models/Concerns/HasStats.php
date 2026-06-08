<?php

namespace Bjanczak\FilamentShortUrl\Models\Concerns;

trait HasStats
{
    use HasSecurityStats;
    use HasStatsCache;
    use HasStatsQueries;
    use HasVisitCounters;
}
