<?php

namespace Bjanczak\FilamentShortUrl\Models\Concerns;

use Bjanczak\FilamentShortUrl\Services\Stats\FilteredStatsCollector;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsCacheHelper;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsScalingProfile;
use Bjanczak\FilamentShortUrl\Services\Stats\TodayStatsBuffer;
use Bjanczak\FilamentShortUrl\Services\StatsSqlHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait HasStatsCache
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $memoizedStats = [];

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

        if (! empty($filters)) {
            $cacheKey = "short_url_stats_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$filtersHash;
            $this->registerCacheKey($cacheKey);

            return $this->memoizedStats[$memoKey] = StatsCacheHelper::remember($cacheKey, function () use ($dateFromClean, $dateToClean, $filters) {
                return (new FilteredStatsCollector($this))->collect($dateFromClean, $dateToClean, $filters);
            });
        }

        $today = Carbon::today()->toDateString();
        $includeToday = ($dateToClean === null || $dateToClean >= $today);
        if ($dateFromClean && $dateFromClean > $today) {
            $includeToday = false;
        }

        $historicalKey = "short_url_stats_hist_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all');
        $this->registerCacheKey($historicalKey);

        $historical = StatsCacheHelper::remember(
            $historicalKey,
            fn () => $this->buildHistoricalStatsPayload($dateFromClean, $dateToClean),
        );

        if (! $includeToday) {
            return $this->memoizedStats[$memoKey] = $this->finalizeHistoricalStatsPayload($historical, $dateFromClean, $dateToClean);
        }

        return $this->memoizedStats[$memoKey] = $this->mergeTodayIntoStatsPayload(
            $historical,
            $this->resolveTodayRawStats(),
            $dateFromClean,
            $dateToClean,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildHistoricalStatsPayload(?string $dateFromClean, ?string $dateToClean): array
    {
        $today = Carbon::today()->toDateString();

        $dailyQuery = $this->dailyStats()->getQuery();
        StatsSqlHelper::applyDailyStatsDateBefore($dailyQuery, $today);
        StatsSqlHelper::applyDailyStatsDateRange(
            $dailyQuery,
            $dateFromClean ?: null,
            ($dateToClean && $dateToClean < $today) ? $dateToClean : null,
        );
        $dailyStatsRows = $dailyQuery->toBase()->get();

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
        $utmTerms = [];
        $utmContents = [];
        $visitsByBrowserVersion = [];
        $visitsByOsVersion = [];

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
            $utmTerms = $mergeStats($utmTerms, is_string($row->utm_terms) ? json_decode($row->utm_terms, true) : (array) $row->utm_terms);
            $utmContents = $mergeStats($utmContents, is_string($row->utm_contents) ? json_decode($row->utm_contents, true) : (array) $row->utm_contents);
            $visitsByBrowserVersion = $mergeStats($visitsByBrowserVersion, is_string($row->browser_versions) ? json_decode($row->browser_versions, true) : (array) $row->browser_versions);
            $visitsByOsVersion = $mergeStats($visitsByOsVersion, is_string($row->os_versions) ? json_decode($row->os_versions, true) : (array) $row->os_versions);
            $visitsByLanguage = $mergeStats($visitsByLanguage, is_string($row->language_stats) ? json_decode($row->language_stats, true) : (array) $row->language_stats);
            $visitsByVariant = $mergeStats($visitsByVariant, is_string($row->variant_stats) ? json_decode($row->variant_stats, true) : (array) $row->variant_stats);
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
        }

        return [
            'totalVisits' => $totalVisits,
            'uniqueVisits' => $uniqueVisitsCount,
            'visitsThisWeek' => $visitsThisWeek,
            'visitsThisMonth' => $visitsThisMonth,
            'visitsByDay' => $visitsByDay,
            'visitsByCountry' => $visitsByCountry,
            'visitsByCity' => $visitsByCity,
            'visitsByDevice' => $visitsByDevice,
            'visitsByBrowser' => $visitsByBrowser,
            'visitsByBrowserVersion' => $visitsByBrowserVersion,
            'visitsByOs' => $visitsByOs,
            'visitsByOsVersion' => $visitsByOsVersion,
            'visitsByReferer' => $visitsByReferer,
            'utmSources' => $utmSources,
            'utmMediums' => $utmMediums,
            'utmCampaigns' => $utmCampaigns,
            'utmTerms' => $utmTerms,
            'utmContents' => $utmContents,
            'qrScans' => $qrScans,
            'visitsByLanguage' => $visitsByLanguage,
            'visitsByVariant' => $visitsByVariant,
        ];
    }

    /**
     * @param  array<string, mixed>  $historical
     * @return array<string, mixed>
     */
    protected function finalizeHistoricalStatsPayload(array $historical, ?string $dateFromClean, ?string $dateToClean): array
    {
        $visitsByCountry = $historical['visitsByCountry'] ?? [];
        $visitsByCity = $historical['visitsByCity'] ?? [];
        $visitsByDevice = $historical['visitsByDevice'] ?? [];
        $visitsByBrowser = $historical['visitsByBrowser'] ?? [];
        $visitsByOs = $historical['visitsByOs'] ?? [];
        $visitsByReferer = $historical['visitsByReferer'] ?? [];
        $utmSources = $historical['utmSources'] ?? [];
        $utmMediums = $historical['utmMediums'] ?? [];
        $utmCampaigns = $historical['utmCampaigns'] ?? [];
        $utmTerms = $historical['utmTerms'] ?? [];
        $utmContents = $historical['utmContents'] ?? [];
        $visitsByLanguage = $historical['visitsByLanguage'] ?? [];
        $visitsByVariant = $historical['visitsByVariant'] ?? [];
        $visitsByBrowserVersion = $historical['visitsByBrowserVersion'] ?? [];
        $visitsByOsVersion = $historical['visitsByOsVersion'] ?? [];

        arsort($visitsByCountry);
        arsort($visitsByCity);
        arsort($visitsByDevice);
        arsort($visitsByBrowser);
        arsort($visitsByOs);
        arsort($visitsByReferer);
        arsort($utmSources);
        arsort($utmMediums);
        arsort($utmCampaigns);
        arsort($utmTerms);
        arsort($utmContents);
        arsort($visitsByLanguage);
        arsort($visitsByVariant);

        if (empty($visitsByBrowserVersion)) {
            $visitsByBrowserVersion = $this->getRawVisitsVersionDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'browser', 'browser_version'
            );
        }

        if (empty($visitsByOsVersion)) {
            $visitsByOsVersion = $this->getRawVisitsVersionDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'operating_system', 'operating_system_version'
            );
        }

        if (empty($utmTerms)) {
            $utmTerms = $this->getRawVisitsDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'utm_term'
            );
        }

        if (empty($utmContents)) {
            $utmContents = $this->getRawVisitsDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'utm_content'
            );
        }

        return [
            'totalVisits' => (int) ($historical['totalVisits'] ?? 0),
            'uniqueVisits' => (int) ($historical['uniqueVisits'] ?? 0),
            'visitsToday' => 0,
            'visitsThisWeek' => (int) ($historical['visitsThisWeek'] ?? 0),
            'visitsThisMonth' => (int) ($historical['visitsThisMonth'] ?? 0),
            'visitsByDay' => $historical['visitsByDay'] ?? [],
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
            'qrScans' => (int) ($historical['qrScans'] ?? 0),
            'visitsByLanguage' => array_slice($visitsByLanguage, 0, 10, true),
            'visitsByVariant' => $visitsByVariant,
        ];
    }

    /**
     * @param  array<string, mixed>  $historical
     * @param  array<string, mixed>  $todayRawStats
     * @return array<string, mixed>
     */
    protected function mergeTodayIntoStatsPayload(
        array $historical,
        array $todayRawStats,
        ?string $dateFromClean,
        ?string $dateToClean,
    ): array {
        $today = Carbon::today()->toDateString();
        $mergeStats = function (array $base, ?array $additional): array {
            if (empty($additional)) {
                return $base;
            }
            foreach ($additional as $key => $val) {
                $base[$key] = ($base[$key] ?? 0) + $val;
            }

            return $base;
        };

        $visitsToday = (int) ($todayRawStats['totalVisits'] ?? 0);
        $totalVisits = (int) ($historical['totalVisits'] ?? 0) + $visitsToday;
        $uniqueVisitsCount = (int) ($historical['uniqueVisits'] ?? 0) + (int) ($todayRawStats['uniqueVisits'] ?? 0);
        $qrScans = (int) ($historical['qrScans'] ?? 0) + (int) ($todayRawStats['qrScans'] ?? 0);
        $visitsThisWeek = (int) ($historical['visitsThisWeek'] ?? 0) + $visitsToday;
        $visitsThisMonth = (int) ($historical['visitsThisMonth'] ?? 0) + $visitsToday;

        $visitsByCountry = $mergeStats($historical['visitsByCountry'] ?? [], $todayRawStats['visitsByCountry'] ?? []);
        $visitsByCity = $mergeStats($historical['visitsByCity'] ?? [], $todayRawStats['visitsByCity'] ?? []);
        $visitsByDevice = $mergeStats($historical['visitsByDevice'] ?? [], $todayRawStats['visitsByDevice'] ?? []);
        $visitsByBrowser = $mergeStats($historical['visitsByBrowser'] ?? [], $todayRawStats['visitsByBrowser'] ?? []);
        $visitsByOs = $mergeStats($historical['visitsByOs'] ?? [], $todayRawStats['visitsByOs'] ?? []);
        $visitsByReferer = $mergeStats($historical['visitsByReferer'] ?? [], $todayRawStats['visitsByReferer'] ?? []);
        $utmSources = $mergeStats($historical['utmSources'] ?? [], $todayRawStats['utmSources'] ?? []);
        $utmMediums = $mergeStats($historical['utmMediums'] ?? [], $todayRawStats['utmMediums'] ?? []);
        $utmCampaigns = $mergeStats($historical['utmCampaigns'] ?? [], $todayRawStats['utmCampaigns'] ?? []);
        $visitsByLanguage = $mergeStats($historical['visitsByLanguage'] ?? [], $todayRawStats['visitsByLanguage'] ?? []);
        $visitsByVariant = $mergeStats($historical['visitsByVariant'] ?? [], $todayRawStats['visitsByVariant'] ?? []);
        $utmTerms = $historical['utmTerms'] ?? [];
        $utmContents = $historical['utmContents'] ?? [];
        $visitsByBrowserVersion = $historical['visitsByBrowserVersion'] ?? [];
        $visitsByOsVersion = $historical['visitsByOsVersion'] ?? [];

        $visitsByDay = $historical['visitsByDay'] ?? [];
        $chartFrom = $dateFromClean ? Carbon::parse($dateFromClean) : now()->subDays(29)->startOfDay();
        $chartTo = $dateToClean ? Carbon::parse($dateToClean) : now()->endOfDay();
        $daysDiff = (int) $chartFrom->diffInDays($chartTo);

        if ($daysDiff > 90) {
            $mToday = Carbon::parse($today)->format('Y-m');
            $visitsByDay[$mToday] = ($visitsByDay[$mToday] ?? 0) + $visitsToday;
        } elseif (isset($visitsByDay[$today])) {
            $visitsByDay[$today] = $visitsToday;
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
        arsort($utmTerms);
        arsort($utmContents);
        arsort($visitsByLanguage);
        arsort($visitsByVariant);

        if (empty($visitsByBrowserVersion)) {
            $visitsByBrowserVersion = $this->getRawVisitsVersionDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'browser', 'browser_version'
            );
        }

        if (empty($visitsByOsVersion)) {
            $visitsByOsVersion = $this->getRawVisitsVersionDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'operating_system', 'operating_system_version'
            );
        }

        if (empty($utmTerms)) {
            $utmTerms = $this->getRawVisitsDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'utm_term'
            );
        }

        if (empty($utmContents)) {
            $utmContents = $this->getRawVisitsDistribution(
                DB::table('short_url_visits')
                    ->where('short_url_id', $this->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'))
                    ->when($dateToClean, fn ($q) => $q->where('visited_at', '<=', $dateToClean.' 23:59:59')),
                'utm_content'
            );
        }

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
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveTodayRawStats(): array
    {
        $today = Carbon::today()->toDateString();
        $profile = app(StatsScalingProfile::class);
        $cacheKey = 'filament-short-url:stats:today:sql:'.$this->id.':'.$today;

        $dimensional = StatsCacheHelper::remember(
            $cacheKey,
            function () use ($today) {
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

                return [
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
            },
            $profile->todaySqlMicroCacheTtl(),
        );

        if ($profile->usesRedisTodayBuffer()) {
            $redisSummary = app(TodayStatsBuffer::class)->getTodaySummary((int) $this->id, $today);

            if ($redisSummary !== null) {
                return array_merge($dimensional, [
                    'totalVisits' => $redisSummary['totalVisits'],
                    'uniqueVisits' => $redisSummary['uniqueVisits'],
                    'qrScans' => $redisSummary['qrScans'],
                ]);
            }
        }

        return $dimensional;
    }

    /**
     * Clear stats and chart caches for this short URL.
     */
    public function clearStatsCache(?string $dateFrom = null, ?string $dateTo = null, array $filters = []): void
    {
        try {
            $trackerKey = "short_url_cache_keys_{$this->id}";
            $keys = cache()->get($trackerKey, []);
            if (is_array($keys)) {
                foreach ($keys as $k) {
                    cache()->forget($k);
                }
            }
            cache()->forget($trackerKey);
        } catch (\Throwable) {
            // ignore
        }

        $dateFromClean = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : null;
        $dateToClean = $dateTo ? Carbon::parse($dateTo)->toDateString() : null;
        $filtersHash = md5(json_encode($filters));

        $historicalKey = "short_url_stats_hist_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all');
        StatsCacheHelper::forget($historicalKey);

        $cacheKey = "short_url_stats_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$filtersHash;
        StatsCacheHelper::forget($cacheKey);

        $baseCacheKey = "short_url_stats_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.md5(json_encode([]));
        StatsCacheHelper::forget($baseCacheKey);

        $todaySqlKey = 'filament-short-url:stats:today:sql:'.$this->id.':'.Carbon::today()->toDateString();
        StatsCacheHelper::forget($todaySqlKey);

        try {
            app(TodayStatsBuffer::class)->clearToday((int) $this->id);
        } catch (\Throwable) {
            // ignore
        }

        foreach (['hourly', 'daily', 'weekly', 'monthly'] as $granularity) {
            foreach (['total', 'unique', 'qr'] as $metric) {
                $chartCacheKey = "short_url_chart_data_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$granularity.'_'.$filtersHash.'_'.$metric;
                StatsCacheHelper::forget($chartCacheKey);

                $baseChartCacheKey = "short_url_chart_data_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$granularity.'_'.md5(json_encode([])).'_'.$metric;
                StatsCacheHelper::forget($baseChartCacheKey);
            }
        }

        $this->memoizedStats = [];
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
                if (count($keys) >= 200) {
                    array_shift($keys);
                }
                $keys[] = $key;
                cache()->put($trackerKey, $keys, 86400 * 30);
            }
        } catch (\Throwable) {
            // Ignore cache registration failures
        }
    }
}
