<?php

namespace Bjanczak\FilamentShortUrl\Models\Concerns;

use Bjanczak\FilamentShortUrl\Jobs\IncrementVisitJob;
use Illuminate\Cache\RedisStore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait HasStats
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $memoizedStats = [];

    /**
     * Cache properties to hold preloaded buffered visits for the current request.
     */
    protected static ?array $bufferedTotalVisits = null;

    protected static ?array $bufferedUniqueVisits = null;

    protected static ?array $bufferedQrScans = null;

    /**
     * Get the real-time total visits count.
     */
    public function getRealTimeTotalVisits(): int
    {
        if (config('filament-short-url.counter_buffering.enabled', false)) {
            $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
            $buffered = (int) cache()->get("{$prefix}total:{$this->id}", 0);

            return $this->total_visits + $buffered;
        }

        // Use real-time visit count in cache to keep the cached model instance updated
        $cacheKey = "filament-short-url:visits:{$this->id}";

        return (int) cache()->remember($cacheKey, 3600, fn () => $this->total_visits);
    }

    /**
     * Atomically increment visit counters — single query when unique,
     * to avoid race conditions and two round-trips. Supports write-back caching.
     */
    public function incrementVisits(bool $isUnique = false, bool $isQrScan = false): void
    {
        if (config('filament-short-url.counter_buffering.enabled', false)) {
            $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
            try {
                // Increment atomically in cache (works on Redis, Memcached, Database, File, etc.)
                cache()->increment("{$prefix}total:{$this->id}");

                if ($isUnique) {
                    cache()->increment("{$prefix}unique:{$this->id}");
                }

                if ($isQrScan) {
                    cache()->increment("{$prefix}qr:{$this->id}");
                }

                // Add to dirty IDs list — choose the best strategy for the active store.
                // instanceof check is reliable across named stores, Predis, Relay, and Redis Sentinel.
                $store = cache()->store()->getStore();
                if ($store instanceof RedisStore) {
                    // O(1) set add — atomic, memory-efficient, no lock needed
                    $store->connection()->sadd("{$prefix}dirty_ids", $this->id);
                } else {
                    // For non-Redis stores: acquire an exclusive lock before modifying the
                    // shared dirty_ids array. If the lock cannot be acquired within 2 seconds
                    // (high contention), we fall through to a direct DB increment so no click
                    // is ever silently lost.
                    $lock = cache()->lock("{$prefix}dirty_ids_lock", 2);
                    $registered = $lock->get(function () use ($prefix): bool {
                        $dirtyIds = cache()->get("{$prefix}dirty_ids", []);
                        if (! is_array($dirtyIds)) {
                            $dirtyIds = [];
                        }

                        // Safety cap: prevent unbounded memory growth on non-Redis stores.
                        // On Redis the SADD path above is always used instead.
                        if (count($dirtyIds) >= 50000) {
                            return false; // Signal overflow — caller will fall back to DB write
                        }

                        if (! in_array($this->id, $dirtyIds, true)) {
                            $dirtyIds[] = $this->id;
                            cache()->forever("{$prefix}dirty_ids", $dirtyIds);
                        }

                        return true;
                    });

                    // $registered is null (lock not acquired) or false (cap overflow) — fall back.
                    if (! $registered) {
                        $this->newQuery()->where('id', $this->id)->increment('total_visits', 1,
                            array_filter([
                                'unique_visits' => $isUnique ? DB::raw('unique_visits + 1') : null,
                                'qr_scans' => $isQrScan ? DB::raw('qr_scans + 1') : null,
                            ])
                        );

                        return;
                    }
                }

                return;
            } catch (\Throwable) {
                // Cache backend failed — fall through to the queued DB job below
            }

            // Safe fallback: dispatch an async job so no click is silently lost on cache failure.
            $connection = config('filament-short-url.queue_connection', 'sync');
            dispatch((new IncrementVisitJob($this->id, $isUnique, $isQrScan))->onConnection($connection ?: 'sync'));

            return;
        }

        $updates = [];
        if ($isUnique) {
            $updates['unique_visits'] = DB::raw('unique_visits + 1');
        }
        if ($isQrScan) {
            $updates['qr_scans'] = DB::raw('qr_scans + 1');
        }

        $this->newQuery()
            ->where('id', $this->id)
            ->increment('total_visits', 1, $updates);

        // Keep the real-time cache count incremented.
        // We use add() + increment() instead of has() + increment() to avoid a TOCTOU
        // race where two processes both see the key missing and both try to initialize it.
        $cacheKey = "filament-short-url:visits:{$this->id}";
        try {
            // add() only writes if the key does not exist yet (atomic on all stores)
            if (! cache()->add($cacheKey, $this->total_visits + 1, 3600)) {
                // Key already existed — just increment it
                cache()->increment($cacheKey);
            }
        } catch (\Throwable) {
            // Never let cache errors disrupt redirection or DB counters
        }
    }

    /**
     * Get cached statistics for this short URL.
     *
     * @return array<string, mixed>
     */
    public function getCachedStats(?string $dateFrom = null, ?string $dateTo = null, array $filters = []): array
    {
        $dateFromClean = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : null;
        $dateToClean = $dateTo ? Carbon::parse($dateTo)->toDateString() : null;

        $filtersHash = md5(json_encode($filters));
        $memoKey = ($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$filtersHash;

        if (array_key_exists($memoKey, $this->memoizedStats)) {
            return $this->memoizedStats[$memoKey];
        }

        $cacheTtl = (int) config('filament-short-url.geo_ip.stats_cache_ttl', 300);
        $cacheKey = "short_url_stats_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$filtersHash;
        $this->registerCacheKey($cacheKey);

        if (! empty($filters)) {
            return $this->memoizedStats[$memoKey] = cache()->remember($cacheKey, $cacheTtl, function () use ($dateFromClean, $dateToClean, $filters) {
                $today = Carbon::today()->toDateString();
                $baseQuery = DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false);

                if ($dateFromClean) {
                    $baseQuery->where('visited_at', '>=', $dateFromClean.' 00:00:00');
                }
                if ($dateToClean) {
                    $baseQuery->where('visited_at', '<=', $dateToClean.' 23:59:59');
                }

                $this->applyStatsFilters($baseQuery, $filters);

                $summary = (clone $baseQuery)
                    ->selectRaw('
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT ip_hash) as unique_visits,
                        SUM(CASE WHEN is_qr_scan = 1 THEN 1 ELSE 0 END) as qr_scans
                    ')
                    ->first();

                $totalVisits = (int) ($summary->total_visits ?? 0);
                $uniqueVisitsCount = (int) ($summary->unique_visits ?? 0);
                $qrScans = (int) ($summary->qr_scans ?? 0);

                $driver = DB::connection()->getDriverName();
                $dateExpression = match ($driver) {
                    'sqlite' => "strftime('%Y-%m-%d', visited_at)",
                    'pgsql' => "to_char(visited_at, 'YYYY-MM-DD')",
                    default => "DATE_FORMAT(visited_at, '%Y-%m-%d')",
                };

                $timelineRows = (clone $baseQuery)
                    ->select(DB::raw("{$dateExpression} as day"), DB::raw('COUNT(*) as cnt'))
                    ->groupBy('day')
                    ->orderBy('day', 'asc')
                    ->pluck('cnt', 'day')
                    ->toArray();

                $visitsByDay = [];
                $chartFrom = $dateFromClean ? Carbon::parse($dateFromClean) : now()->subDays(29)->startOfDay();
                $chartTo = $dateToClean ? Carbon::parse($dateToClean) : now()->endOfDay();
                $daysDiff = (int) $chartFrom->diffInDays($chartTo);

                if ($daysDiff > 90) {
                    $monthExpression = match ($driver) {
                        'sqlite' => "strftime('%Y-%m', visited_at)",
                        'pgsql' => "to_char(visited_at, 'YYYY-MM')",
                        default => "DATE_FORMAT(visited_at, '%Y-%m')",
                    };

                    $visitsByDay = (clone $baseQuery)
                        ->select(DB::raw("{$monthExpression} as month_bucket"), DB::raw('COUNT(*) as cnt'))
                        ->groupBy('month_bucket')
                        ->orderBy('month_bucket', 'asc')
                        ->pluck('cnt', 'month_bucket')
                        ->toArray();
                } else {
                    for ($i = $daysDiff; $i >= 0; $i--) {
                        $d = (clone $chartTo)->subDays($i)->format('Y-m-d');
                        $visitsByDay[$d] = 0;
                    }
                    foreach ($timelineRows as $day => $count) {
                        $formattedDay = Carbon::parse($day)->format('Y-m-d');
                        if (isset($visitsByDay[$formattedDay])) {
                            $visitsByDay[$formattedDay] = (int) $count;
                        }
                    }
                }

                $visitsToday = $visitsByDay[$today] ?? 0;
                $startOfWeek = now()->startOfWeek()->toDateString();
                $startOfMonth = now()->startOfMonth()->toDateString();

                $visitsThisWeek = 0;
                $visitsThisMonth = 0;
                foreach ($visitsByDay as $date => $cnt) {
                    if ($date >= $startOfWeek) {
                        $visitsThisWeek += $cnt;
                    }
                    if ($date >= $startOfMonth) {
                        $visitsThisMonth += $cnt;
                    }
                }

                return [
                    'totalVisits' => $totalVisits,
                    'uniqueVisits' => $uniqueVisitsCount,
                    'visitsToday' => $visitsToday,
                    'visitsThisWeek' => $visitsThisWeek,
                    'visitsThisMonth' => $visitsThisMonth,
                    'visitsByDay' => $visitsByDay,
                    'visitsByCountry' => $this->getRawVisitsDistribution($baseQuery, 'country_code'),
                    'visitsByCity' => $this->getRawVisitsCityDistribution($baseQuery),
                    'visitsByDevice' => $this->getRawVisitsDistribution($baseQuery, 'device_type'),
                    'visitsByBrowser' => $this->getRawVisitsDistribution($baseQuery, 'browser'),
                    'visitsByBrowserVersion' => $this->getRawVisitsVersionDistribution($baseQuery, 'browser', 'browser_version'),
                    'visitsByOs' => $this->getRawVisitsDistribution($baseQuery, 'operating_system'),
                    'visitsByOsVersion' => $this->getRawVisitsVersionDistribution($baseQuery, 'operating_system', 'operating_system_version'),
                    'visitsByReferer' => $this->getRawVisitsRefererDistribution($baseQuery),
                    'utmSources' => $this->getRawVisitsDistribution($baseQuery, 'utm_source'),
                    'utmMediums' => $this->getRawVisitsDistribution($baseQuery, 'utm_medium'),
                    'utmCampaigns' => $this->getRawVisitsDistribution($baseQuery, 'utm_campaign'),
                    'utmTerms' => $this->getRawVisitsDistribution($baseQuery, 'utm_term'),
                    'utmContents' => $this->getRawVisitsDistribution($baseQuery, 'utm_content'),
                    'qrScans' => $qrScans,
                    'visitsByLanguage' => $this->getRawVisitsDistribution($baseQuery, 'browser_language'),
                    'visitsByVariant' => $this->getRawVisitsDistribution($baseQuery, 'selected_variant'),
                ];
            });
        }

        return $this->memoizedStats[$memoKey] = cache()->remember($cacheKey, $cacheTtl, function () use ($dateFromClean, $dateToClean) {
            $today = Carbon::today()->toDateString();

            $dailyQuery = $this->dailyStats()->where('date', '<', $today);
            if ($dateFromClean) {
                $dailyQuery->where('date', '>=', $dateFromClean);
            }
            if ($dateToClean && $dateToClean < $today) {
                $dailyQuery->where('date', '<=', $dateToClean);
            }
            $dailyStatsRows = $dailyQuery->toBase()->get();

            $includeToday = ($dateToClean === null || $dateToClean >= $today);
            if ($dateFromClean && $dateFromClean > $today) {
                $includeToday = false;
            }

            $todayRawStats = [];
            if ($includeToday) {
                $baseTodayQuery = DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('visited_at', '>=', $today.' 00:00:00')
                    ->where('is_bot', false)
                    ->where('is_proxy', false);

                $todaySummary = (clone $baseTodayQuery)
                    ->selectRaw('
                        COUNT(*) as total_visits,
                        COUNT(DISTINCT ip_hash) as unique_visits,
                        SUM(CASE WHEN is_qr_scan = 1 THEN 1 ELSE 0 END) as qr_scans
                    ')
                    ->first();

                $todayRawStats = [
                    'totalVisits' => (int) ($todaySummary->total_visits ?? 0),
                    'uniqueVisits' => (int) ($todaySummary->unique_visits ?? 0),
                    'qrScans' => (int) ($todaySummary->qr_scans ?? 0),
                    'visitsByCountry' => $this->getRawVisitsDistribution($baseTodayQuery, 'country_code'),
                    'visitsByCity' => $this->getRawVisitsCityDistribution($baseTodayQuery),
                    'visitsByDevice' => $this->getRawVisitsDistribution($baseTodayQuery, 'device_type'),
                    'visitsByBrowser' => $this->getRawVisitsDistribution($baseTodayQuery, 'browser'),
                    'visitsByOs' => $this->getRawVisitsDistribution($baseTodayQuery, 'operating_system'),
                    'visitsByReferer' => $this->getRawVisitsRefererDistribution($baseTodayQuery),
                    'utmSources' => $this->getRawVisitsDistribution($baseTodayQuery, 'utm_source'),
                    'utmMediums' => $this->getRawVisitsDistribution($baseTodayQuery, 'utm_medium'),
                    'utmCampaigns' => $this->getRawVisitsDistribution($baseTodayQuery, 'utm_campaign'),
                    'visitsByLanguage' => $this->getRawVisitsDistribution($baseTodayQuery, 'browser_language'),
                    'visitsByVariant' => $this->getRawVisitsDistribution($baseTodayQuery, 'selected_variant'),
                ];
            }

            $mergeStats = function (array $base, ?array $additional): array {
                if (empty($additional)) {
                    return $base;
                }
                foreach ($additional as $key => $val) {
                    $base[$key] = ($base[$key] ?? 0) + $val;
                }

                return $base;
            };

            $totalVisits = 0;
            $uniqueVisitsCount = 0;
            $visitsToday = $todayRawStats['totalVisits'] ?? 0;
            $visitsThisWeek = 0;
            $visitsThisMonth = 0;
            $qrScans = 0;

            $visitsByCountry = [];
            $visitsByCity = [];
            $visitsByDevice = [];
            $visitsByBrowser = [];
            $visitsByOs = [];
            $visitsByReferer = [];
            $utmSources = [];
            $utmMediums = [];
            $utmCampaigns = [];
            $visitsByLanguage = [];
            $visitsByVariant = [];

            $startOfWeek = now()->startOfWeek()->toDateString();
            $startOfMonth = now()->startOfMonth()->toDateString();

            foreach ($dailyStatsRows as $row) {
                $totalVisits += $row->visits_count;
                $uniqueVisitsCount += $row->unique_visits_count;
                $qrScans += $row->qr_visits_count ?? 0;

                $rowDate = is_string($row->date) ? $row->date : Carbon::parse($row->date)->toDateString();
                if ($rowDate >= $startOfWeek) {
                    $visitsThisWeek += $row->visits_count;
                }
                if ($rowDate >= $startOfMonth) {
                    $visitsThisMonth += $row->visits_count;
                }

                $visitsByCountry = $mergeStats($visitsByCountry, is_string($row->country_stats) ? json_decode($row->country_stats, true) : (array) $row->country_stats);
                $visitsByCity = $mergeStats($visitsByCity, is_string($row->city_stats) ? json_decode($row->city_stats, true) : (array) $row->city_stats);
                $visitsByDevice = $mergeStats($visitsByDevice, is_string($row->device_stats) ? json_decode($row->device_stats, true) : (array) $row->device_stats);
                $visitsByBrowser = $mergeStats($visitsByBrowser, is_string($row->browser_stats) ? json_decode($row->browser_stats, true) : (array) $row->browser_stats);
                $visitsByOs = $mergeStats($visitsByOs, is_string($row->os_stats) ? json_decode($row->os_stats, true) : (array) $row->os_stats);

                if (! empty($row->referer_stats)) {
                    $refererStats = is_string($row->referer_stats) ? json_decode($row->referer_stats, true) : (array) $row->referer_stats;
                    $groupedHistoricalReferer = [];
                    foreach ($refererStats as $host => $cnt) {
                        $cat = static::resolveRefererCategory($host);
                        $groupedHistoricalReferer[$cat] = ($groupedHistoricalReferer[$cat] ?? 0) + $cnt;
                    }
                    $visitsByReferer = $mergeStats($visitsByReferer, $groupedHistoricalReferer);
                }

                $utmSources = $mergeStats($utmSources, is_string($row->utm_source_stats) ? json_decode($row->utm_source_stats, true) : (array) $row->utm_source_stats);
                $utmMediums = $mergeStats($utmMediums, is_string($row->utm_medium_stats) ? json_decode($row->utm_medium_stats, true) : (array) $row->utm_medium_stats);
                $utmCampaigns = $mergeStats($utmCampaigns, is_string($row->utm_campaign_stats) ? json_decode($row->utm_campaign_stats, true) : (array) $row->utm_campaign_stats);
                $visitsByLanguage = $mergeStats($visitsByLanguage, is_string($row->language_stats) ? json_decode($row->language_stats, true) : (array) $row->language_stats);
                $visitsByVariant = $mergeStats($visitsByVariant, is_string($row->variant_stats) ? json_decode($row->variant_stats, true) : (array) $row->variant_stats);
            }

            if ($includeToday && ! empty($todayRawStats)) {
                $totalVisits += $todayRawStats['totalVisits'];
                $uniqueVisitsCount += $todayRawStats['uniqueVisits'];
                $qrScans += $todayRawStats['qrScans'];

                $visitsThisWeek += $todayRawStats['totalVisits'];
                $visitsThisMonth += $todayRawStats['totalVisits'];

                $visitsByCountry = $mergeStats($visitsByCountry, $todayRawStats['visitsByCountry']);
                $visitsByCity = $mergeStats($visitsByCity, $todayRawStats['visitsByCity']);
                $visitsByDevice = $mergeStats($visitsByDevice, $todayRawStats['visitsByDevice']);
                $visitsByBrowser = $mergeStats($visitsByBrowser, $todayRawStats['visitsByBrowser']);
                $visitsByOs = $mergeStats($visitsByOs, $todayRawStats['visitsByOs']);
                $visitsByReferer = $mergeStats($visitsByReferer, $todayRawStats['visitsByReferer']);
                $utmSources = $mergeStats($utmSources, $todayRawStats['utmSources']);
                $utmMediums = $mergeStats($utmMediums, $todayRawStats['utmMediums']);
                $utmCampaigns = $mergeStats($utmCampaigns, $todayRawStats['utmCampaigns']);
                $visitsByLanguage = $mergeStats($visitsByLanguage, $todayRawStats['visitsByLanguage']);
                $visitsByVariant = $mergeStats($visitsByVariant, $todayRawStats['visitsByVariant']);
            }

            $visitsByDay = [];
            $chartFrom = $dateFromClean ? Carbon::parse($dateFromClean) : now()->subDays(29)->startOfDay();
            $chartTo = $dateToClean ? Carbon::parse($dateToClean) : now()->endOfDay();
            $daysDiff = (int) $chartFrom->diffInDays($chartTo);

            if ($daysDiff > 90) {
                foreach ($dailyStatsRows as $row) {
                    $m = is_string($row->date) ? substr($row->date, 0, 7) : Carbon::parse($row->date)->format('Y-m');
                    $visitsByDay[$m] = ($visitsByDay[$m] ?? 0) + $row->visits_count;
                }
                if ($includeToday) {
                    $mToday = Carbon::parse($today)->format('Y-m');
                    $visitsByDay[$mToday] = ($visitsByDay[$mToday] ?? 0) + ($todayRawStats['totalVisits'] ?? 0);
                }
            } else {
                for ($i = $daysDiff; $i >= 0; $i--) {
                    $d = (clone $chartTo)->subDays($i)->format('Y-m-d');
                    $visitsByDay[$d] = 0;
                }
                foreach ($dailyStatsRows as $row) {
                    $d = is_string($row->date) ? $row->date : Carbon::parse($row->date)->format('Y-m-d');
                    if (isset($visitsByDay[$d])) {
                        $visitsByDay[$d] = $row->visits_count;
                    }
                }
                if ($includeToday && isset($visitsByDay[$today])) {
                    $visitsByDay[$today] = $todayRawStats['totalVisits'] ?? 0;
                }
            }

            arsort($visitsByCountry);
            arsort($visitsByCity);
            arsort($visitsByDevice);
            arsort($visitsByBrowser);
            arsort($visitsByOs);
            arsort($visitsByReferer);
            arsort($utmSources);
            arsort($utmMediums);
            arsort($utmCampaigns);
            arsort($visitsByLanguage);
            arsort($visitsByVariant);

            $visitsByBrowserVersion = $this->getRawVisitsVersionDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'browser', 'browser_version'
            );

            $visitsByOsVersion = $this->getRawVisitsVersionDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'operating_system', 'operating_system_version'
            );

            $utmTerms = $this->getRawVisitsDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'utm_term'
            );

            $utmContents = $this->getRawVisitsDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'utm_content'
            );

            return [
                'totalVisits' => $totalVisits,
                'uniqueVisits' => $uniqueVisitsCount,
                'visitsToday' => $visitsToday,
                'visitsThisWeek' => $visitsThisWeek,
                'visitsThisMonth' => $visitsThisMonth,
                'visitsByDay' => $visitsByDay,
                'visitsByCountry' => array_slice($visitsByCountry, 0, 10, true),
                'visitsByCity' => array_slice($visitsByCity, 0, 10, true),
                'visitsByDevice' => $visitsByDevice,
                'visitsByBrowser' => array_slice($visitsByBrowser, 0, 8, true),
                'visitsByBrowserVersion' => $visitsByBrowserVersion,
                'visitsByOs' => array_slice($visitsByOs, 0, 8, true),
                'visitsByOsVersion' => $visitsByOsVersion,
                'visitsByReferer' => array_slice($visitsByReferer, 0, 10, true),
                'utmSources' => array_slice($utmSources, 0, 8, true),
                'utmMediums' => array_slice($utmMediums, 0, 8, true),
                'utmCampaigns' => array_slice($utmCampaigns, 0, 8, true),
                'utmTerms' => array_slice($utmTerms, 0, 8, true),
                'utmContents' => array_slice($utmContents, 0, 8, true),
                'qrScans' => $qrScans,
                'visitsByLanguage' => array_slice($visitsByLanguage, 0, 10, true),
                'visitsByVariant' => $visitsByVariant,
            ];
        });
    }

    /**
     * Preload all buffered clicks in a single batch query for the entire request.
     */
    protected static function loadAllBufferedVisits(): void
    {
        if (static::$bufferedTotalVisits !== null) {
            return;
        }

        static::$bufferedTotalVisits = [];
        static::$bufferedUniqueVisits = [];
        static::$bufferedQrScans = [];

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return;
        }

        $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
        $dirtyKey = "{$prefix}dirty_ids";

        // 1. Fetch the list of dirty IDs (URLs with pending buffered clicks) in one query
        $dirtyIds = [];
        try {
            if (cache()->getDefaultDriver() === 'redis' && class_exists(Redis::class)) {
                $dirtyIds = Redis::smembers($dirtyKey);
            } else {
                $dirtyIds = cache()->get($dirtyKey, []);
            }
        } catch (\Throwable) {
            // Fallback
        }

        if (empty($dirtyIds)) {
            return;
        }

        $dirtyIds = array_unique(array_filter((array) $dirtyIds));

        // 2. Build array of keys to fetch in a single cache store read
        $totalKeys = [];
        $uniqueKeys = [];
        $qrKeys = [];
        foreach ($dirtyIds as $id) {
            $totalKeys[$id] = "{$prefix}total:{$id}";
            $uniqueKeys[$id] = "{$prefix}unique:{$id}";
            $qrKeys[$id] = "{$prefix}qr:{$id}";
        }

        try {
            // Cache::many() is highly optimized (e.g. 1 database query for database store, or 1 MGET for Redis)
            $totals = cache()->many(array_values($totalKeys));
            $uniques = cache()->many(array_values($uniqueKeys));
            $qrs = cache()->many(array_values($qrKeys));

            foreach ($totalKeys as $id => $key) {
                static::$bufferedTotalVisits[$id] = (int) ($totals[$key] ?? 0);
            }
            foreach ($uniqueKeys as $id => $key) {
                static::$bufferedUniqueVisits[$id] = (int) ($uniques[$key] ?? 0);
            }
            foreach ($qrKeys as $id => $key) {
                static::$bufferedQrScans[$id] = (int) ($qrs[$key] ?? 0);
            }
        } catch (\Throwable) {
            // Fallback
        }
    }

    /**
     * Get the total visits count, merging the database value with any buffered clicks in cache.
     */
    public function getTotalVisitsAttribute(): int
    {
        $dbValue = $this->attributes['total_visits'] ?? 0;

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return $dbValue;
        }

        static::loadAllBufferedVisits();

        $buffered = static::$bufferedTotalVisits[$this->id] ?? 0;

        return $dbValue + $buffered;
    }

    /**
     * Get the unique visits count, merging the database value with any buffered clicks in cache.
     */
    public function getUniqueVisitsAttribute(): int
    {
        $dbValue = $this->attributes['unique_visits'] ?? 0;

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return $dbValue;
        }

        static::loadAllBufferedVisits();

        $buffered = static::$bufferedUniqueVisits[$this->id] ?? 0;

        return $dbValue + $buffered;
    }

    /**
     * Get the QR scans count, merging the database value with any buffered clicks in cache.
     */
    public function getQrScansAttribute(): int
    {
        $dbValue = $this->attributes['qr_scans'] ?? 0;

        if (! config('filament-short-url.counter_buffering.enabled', false)) {
            return $dbValue;
        }

        static::loadAllBufferedVisits();

        $buffered = static::$bufferedQrScans[$this->id] ?? 0;

        return $dbValue + $buffered;
    }

    /**
     * Clear stats and chart caches for this short URL.
     */
    public function clearStatsCache(?string $dateFrom = null, ?string $dateTo = null, array $filters = []): void
    {
        // 1. Fetch tracked keys and forget them
        try {
            $trackerKey = "short_url_cache_keys_{$this->id}";
            $keys = cache()->get($trackerKey, []);
            if (is_array($keys)) {
                foreach ($keys as $k) {
                    cache()->forget($k);
                }
            }
            cache()->forget($trackerKey);
        } catch (\Throwable $e) {
            // ignore
        }

        // 2. Also keep the hardcoded fallback clears just in case
        $dateFromClean = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : null;
        $dateToClean = $dateTo ? Carbon::parse($dateTo)->toDateString() : null;
        $filtersHash = md5(json_encode($filters));

        $cacheKey = "short_url_stats_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$filtersHash;
        cache()->forget($cacheKey);

        $baseCacheKey = "short_url_stats_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.md5(json_encode([]));
        cache()->forget($baseCacheKey);

        foreach (['hourly', 'daily', 'weekly', 'monthly'] as $granularity) {
            foreach (['total', 'unique', 'qr'] as $metric) {
                $chartCacheKey = "short_url_chart_data_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$granularity.'_'.$filtersHash.'_'.$metric;
                cache()->forget($chartCacheKey);

                $baseChartCacheKey = "short_url_chart_data_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$granularity.'_'.md5(json_encode([])).'_'.$metric;
                cache()->forget($baseChartCacheKey);
            }
        }

        $this->memoizedStats = [];
    }

    /**
     * Apply active filter array to a visits query.
     */
    public function applyStatsFilters($query, array $filters): void
    {
        if (empty($filters)) {
            return;
        }

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            switch ($key) {
                case 'country':
                case 'country_code':
                    $query->where('country_code', $value);
                    break;
                case 'city':
                    if (preg_match('/^(.*?)\s*\((.*?)\)$/', $value, $matches)) {
                        $query->where('city', trim($matches[1]))
                            ->where('country_code', trim($matches[2]));
                    } else {
                        $query->where('city', $value);
                    }
                    break;
                case 'browser':
                    $query->where('browser', $value);
                    break;
                case 'operating_system':
                    $query->where('operating_system', $value);
                    break;
                case 'browser_language':
                    $query->where('browser_language', $value);
                    break;
                case 'device':
                case 'device_type':
                    $query->where('device_type', $value);
                    break;
                case 'referrer_category':
                    if ($value === 'Direct / Email / SMS') {
                        $query->where(fn ($q) => $q->whereNull('referer_host')->orWhere('referer_host', '')->orWhere('referer_host', 'direct'));
                    } elseif ($value === 'Twitter / X') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['t.co', 'twitter.com', 'x.com'])
                            ->orWhere('referer_host', 'like', '%.t.co')
                            ->orWhere('referer_host', 'like', '%.twitter.com')
                            ->orWhere('referer_host', 'like', '%.x.com')
                        );
                    } elseif ($value === 'Facebook') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['facebook.com', 'm.facebook.com', 'l.facebook.com', 'lm.facebook.com'])
                            ->orWhere('referer_host', 'like', '%.facebook.com')
                        );
                    } elseif ($value === 'Instagram') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['instagram.com', 'l.instagram.com'])
                            ->orWhere('referer_host', 'like', '%.instagram.com')
                        );
                    } elseif ($value === 'LinkedIn') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['linkedin.com', 'lnkd.in'])
                            ->orWhere('referer_host', 'like', '%.linkedin.com')
                            ->orWhere('referer_host', 'like', '%.lnkd.in')
                        );
                    } elseif ($value === 'YouTube') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['youtube.com', 'youtu.be'])
                            ->orWhere('referer_host', 'like', '%.youtube.com')
                            ->orWhere('referer_host', 'like', '%.youtu.be')
                        );
                    } elseif ($value === 'Pinterest') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['pinterest.com', 'pin.it'])
                            ->orWhere('referer_host', 'like', '%.pinterest.com')
                            ->orWhere('referer_host', 'like', '%.pin.it')
                        );
                    } elseif ($value === 'Google') {
                        $query->where('referer_host', 'like', '%google.%');
                    } elseif ($value === 'Bing') {
                        $query->where('referer_host', 'like', '%bing.%');
                    } elseif ($value === 'Yahoo') {
                        $query->where('referer_host', 'like', '%yahoo.%');
                    } elseif ($value === 'DuckDuckGo') {
                        $query->where('referer_host', 'like', '%duckduckgo.%');
                    } else {
                        $query->where(fn ($q) => $q->where('referer_host', $value)->orWhere('referer_host', 'www.'.$value));
                    }
                    break;
                case 'utm_source':
                    $query->where('utm_source', $value);
                    break;
                case 'utm_medium':
                    $query->where('utm_medium', $value);
                    break;
                case 'utm_campaign':
                    $query->where('utm_campaign', $value);
                    break;
                case 'selected_variant':
                    $query->where('selected_variant', $value);
                    break;
            }
        }
    }

    /**
     * Group by and count a column on the raw visits query, applying filters.
     *
     * @return array<string, int>
     */
    protected function getRawVisitsDistribution($baseQuery, string $column, int $limit = 10): array
    {
        return (clone $baseQuery)
            ->select($column, DB::raw('COUNT(*) as cnt'))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('cnt')
            ->limit($limit)
            ->pluck('cnt', $column)
            ->toArray();
    }

    /**
     * Group by and count city/country_code on the raw visits query.
     *
     * @return array<string, int>
     */
    protected function getRawVisitsCityDistribution($baseQuery, int $limit = 10): array
    {
        $rows = (clone $baseQuery)
            ->select('city', 'country_code', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city', 'country_code')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($rows as $row) {
            $key = "{$row->city} ({$row->country_code})";
            $results[$key] = (int) $row->cnt;
        }

        return $results;
    }

    /**
     * Group by and count referer category on the raw visits query.
     *
     * @return array<string, int>
     */
    protected function getRawVisitsRefererDistribution($baseQuery, int $limit = 10): array
    {
        $rows = (clone $baseQuery)
            ->select('referer_host', DB::raw('COUNT(*) as cnt'))
            ->groupBy('referer_host')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($rows as $row) {
            $host = $row->referer_host ?: 'Direct';
            $category = static::resolveRefererCategory($host);
            $results[$category] = ($results[$category] ?? 0) + (int) $row->cnt;
        }

        arsort($results);

        return array_slice($results, 0, $limit, true);
    }

    /**
     * Register a cache key for stats tracking.
     */
    public function registerCacheKey(string $key): void
    {
        try {
            $trackerKey = "short_url_cache_keys_{$this->id}";
            $keys = cache()->get($trackerKey, []);
            if (! is_array($keys)) {
                $keys = [];
            }
            if (! in_array($key, $keys)) {
                $keys[] = $key;
                cache()->put($trackerKey, $keys, 86400 * 30); // Store for 30 days
            }
        } catch (\Throwable $e) {
            // Ignore cache registration failures
        }
    }

    /**
     * Group by name and version on the raw visits query, applying filters.
     *
     * @return array<string, array<string, int>>
     */
    protected function getRawVisitsVersionDistribution($baseQuery, string $nameColumn, string $versionColumn, int $limit = 5): array
    {
        $rows = (clone $baseQuery)
            ->select($nameColumn, $versionColumn, DB::raw('COUNT(*) as cnt'))
            ->whereNotNull($nameColumn)
            ->where($nameColumn, '!=', '')
            ->groupBy($nameColumn, $versionColumn)
            ->orderByDesc('cnt')
            ->get();

        $results = [];
        foreach ($rows as $row) {
            $name = $row->$nameColumn;
            $version = $row->$versionColumn ?: 'Unknown';
            if (! isset($results[$name])) {
                $results[$name] = [];
            }
            if (count($results[$name]) < $limit) {
                $results[$name][$version] = (int) $row->cnt;
            }
        }

        return $results;
    }

    /**
     * Map a raw host to a clean Referer Category name.
     */
    public static function resolveRefererCategory(?string $host): string
    {
        if (empty($host) || strtolower($host) === 'direct') {
            return 'Direct / Email / SMS';
        }

        $host = strtolower(trim($host));

        $map = [
            't.co' => 'Twitter / X',
            'twitter.com' => 'Twitter / X',
            'x.com' => 'Twitter / X',
            'facebook.com' => 'Facebook',
            'm.facebook.com' => 'Facebook',
            'l.facebook.com' => 'Facebook',
            'lm.facebook.com' => 'Facebook',
            'instagram.com' => 'Instagram',
            'l.instagram.com' => 'Instagram',
            'linkedin.com' => 'LinkedIn',
            'lnkd.in' => 'LinkedIn',
            'youtube.com' => 'YouTube',
            'youtu.be' => 'YouTube',
            'tiktok.com' => 'TikTok',
            'reddit.com' => 'Reddit',
            'pinterest.com' => 'Pinterest',
            'pin.it' => 'Pinterest',
            'google.com' => 'Google',
            'google.pl' => 'Google',
            'google.co.uk' => 'Google',
            'google.de' => 'Google',
            'google.fr' => 'Google',
        ];

        foreach ($map as $domain => $name) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return $name;
            }
        }

        if (str_contains($host, 'google.')) {
            return 'Google';
        }
        if (str_contains($host, 'bing.')) {
            return 'Bing';
        }
        if (str_contains($host, 'yahoo.')) {
            return 'Yahoo';
        }
        if (str_contains($host, 'duckduckgo.')) {
            return 'DuckDuckGo';
        }

        if (str_starts_with($host, 'www.')) {
            return substr($host, 4);
        }

        return $host;
    }
}
