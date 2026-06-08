<?php

namespace Bjanczak\FilamentShortUrl\Services\Stats;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\StatsSqlHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FilteredStatsCollector
{
    private const DISTRIBUTION_LIMIT = 10;

    private const VERSION_LIMIT = 5;

    public function __construct(private ShortUrl $shortUrl) {}

    /**
     * @return array<string, mixed>
     */
    public function collect(?string $dateFrom, ?string $dateTo, array $filters): array
    {
        $today = Carbon::today()->toDateString();
        $dateToClean = $dateTo ?? $today;
        $retentionDays = (int) config('filament-short-url.pruning.retention_days', 90);
        $rawCutoff = Carbon::today()->subDays($retentionDays)->toDateString();
        $activeFilterCount = count(CrossDimensionalStatsEngine::normalizeFilters($filters));

        $chartFrom = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : now()->subDays(29)->startOfDay();
        $chartTo = Carbon::parse($dateToClean)->endOfDay();
        $daysDiff = (int) $chartFrom->diffInDays($chartTo);
        $useMonthlyBuckets = $daysDiff > 90;

        $state = $this->emptyState($chartFrom, $chartTo, $daysDiff, $useMonthlyBuckets);

        $prunedTotal = 0;
        $prunedQr = 0;
        $prunedUnique = 0;
        $dailyEnd = $dateToClean >= $today
            ? Carbon::yesterday()->toDateString()
            : $dateToClean;

        if ($dailyEnd !== null && ($dateFrom === null || $dateFrom <= $dailyEnd)) {
            [$prunedTotal, $prunedQr, $prunedUnique] = $this->mergePrunedDailyStats(
                $state,
                $dateFrom,
                $dailyEnd,
                $filters,
                $useMonthlyBuckets,
            );
        }

        $rawFrom = $dateFrom !== null && $dateFrom > $rawCutoff ? $dateFrom : $rawCutoff;
        $rawTotal = 0;
        $rawUnique = 0;
        $rawQr = 0;

        if ($dateToClean >= $rawFrom) {
            [$rawTotal, $rawUnique, $rawQr] = $this->scanRawWindow(
                $state,
                $rawFrom,
                $dateToClean,
                $filters,
                $useMonthlyBuckets,
                $activeFilterCount,
            );
        }

        return $this->buildResult(
            $state,
            $chartFrom,
            $chartTo,
            $daysDiff,
            $useMonthlyBuckets,
            $today,
            $prunedTotal + $rawTotal,
            $prunedQr + $rawQr,
            $prunedUnique + $rawUnique,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyState(Carbon $chartFrom, Carbon $chartTo, int $daysDiff, bool $useMonthlyBuckets): array
    {
        $visitsByDay = [];

        if ($useMonthlyBuckets) {
            $current = $chartFrom->copy()->startOfMonth();
            while ($current->lte($chartTo)) {
                $visitsByDay[$current->format('Y-m')] = 0;
                $current->addMonth();
            }
        } else {
            for ($i = $daysDiff; $i >= 0; $i--) {
                $d = $chartTo->copy()->subDays($i)->format('Y-m-d');
                $visitsByDay[$d] = 0;
            }
        }

        return [
            'visitsByDay' => $visitsByDay,
            'visitsByCountry' => [],
            'visitsByCity' => [],
            'visitsByDevice' => [],
            'visitsByBrowser' => [],
            'visitsByBrowserVersion' => [],
            'visitsByOs' => [],
            'visitsByOsVersion' => [],
            'visitsByReferer' => [],
            'utmSources' => [],
            'utmMediums' => [],
            'utmCampaigns' => [],
            'utmTerms' => [],
            'utmContents' => [],
            'visitsByLanguage' => [],
            'visitsByVariant' => [],
            'todayVisits' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: int, 1: int, 2: int}
     */
    private function mergePrunedDailyStats(
        array &$state,
        ?string $dateFrom,
        string $dateTo,
        array $filters,
        bool $useMonthlyBuckets,
    ): array {
        if (! CrossDimensionalStatsEngine::supportsDailyCrossRead($filters)) {
            return [0, 0, 0];
        }

        $today = Carbon::today()->toDateString();
        $query = DB::table('short_url_daily_stats')
            ->where('short_url_id', $this->shortUrl->id);

        StatsSqlHelper::applyDailyStatsDateBefore($query, $today);
        StatsSqlHelper::applyDailyStatsDateRange($query, $dateFrom, $dateTo);

        $rows = $query->get();
        $total = 0;
        $qr = 0;
        $unique = 0;

        foreach ($rows as $row) {
            $merged = CrossDimensionalStatsEngine::mergeDailyCrossIntoState($state, $filters, $row);
            $total += $merged['total'];
            $qr += $merged['qr'];

            if ($filters === []) {
                $unique += (int) ($row->unique_visits_count ?? 0);
            }

            $rowDate = is_string($row->date) ? $row->date : Carbon::parse($row->date)->toDateString();
            $bucket = $useMonthlyBuckets ? substr($rowDate, 0, 7) : $rowDate;

            if (array_key_exists($bucket, $state['visitsByDay'])) {
                $state['visitsByDay'][$bucket] += $merged['total'];
            }
        }

        return [$total, $qr, $unique];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: int, 1: int, 2: int}
     */
    private function scanRawWindow(
        array &$state,
        string $rawFrom,
        string $dateTo,
        array $filters,
        bool $useMonthlyBuckets,
        int $activeFilterCount,
    ): array {
        $today = Carbon::today()->toDateString();

        $baseQuery = DB::table('short_url_visits')
            ->where('short_url_id', $this->shortUrl->id)
            ->where('is_bot', false)
            ->where('is_proxy', false)
            ->where('visited_at', '>=', $rawFrom.' 00:00:00')
            ->where('visited_at', '<=', $dateTo.' 23:59:59');

        $this->shortUrl->applyStatsFilters($baseQuery, $filters);

        $unique = (int) (clone $baseQuery)->distinct()->count('ip_hash');

        if ($activeFilterCount >= 2 || ! CrossDimensionalStatsEngine::supportsDailyCrossRead($filters)) {
            $summary = (clone $baseQuery)
                ->selectRaw('
                    COUNT(*) as total_visits,
                    SUM(CASE WHEN is_qr_scan = 1 THEN 1 ELSE 0 END) as qr_scans
                ')
                ->first();

            $cursorQuery = (clone $baseQuery)
                ->select($this->cursorColumns())
                ->orderBy('id')
                ->cursor();

            foreach ($cursorQuery as $row) {
                $this->accumulateVisitRowIntoState($state, $row, $useMonthlyBuckets, $today);
            }

            return [
                (int) ($summary->total_visits ?? 0),
                $unique,
                (int) ($summary->qr_scans ?? 0),
            ];
        }

        $rawTotal = 0;
        $rawQr = 0;
        $gapStart = $rawFrom;

        if ($activeFilterCount === 1) {
            $yesterday = Carbon::yesterday()->toDateString();
            if ($dateTo >= $yesterday) {
                $hasYesterdayDaily = DB::table('short_url_daily_stats')
                    ->where('short_url_id', $this->shortUrl->id)
                    ->where('date', $yesterday)
                    ->exists();

                if (! $hasYesterdayDaily && $yesterday >= $rawFrom) {
                    $gapStart = $yesterday;
                } else {
                    $gapStart = $today;
                }
            } else {
                $gapStart = $dateTo;
            }
        }

        if ($dateTo >= $gapStart) {
            $gapQuery = (clone $baseQuery)
                ->where('visited_at', '>=', $gapStart.' 00:00:00');

            $gapSummary = (clone $gapQuery)
                ->selectRaw('
                    COUNT(*) as total_visits,
                    SUM(CASE WHEN is_qr_scan = 1 THEN 1 ELSE 0 END) as qr_scans
                ')
                ->first();

            $rawTotal = (int) ($gapSummary->total_visits ?? 0);
            $rawQr = (int) ($gapSummary->qr_scans ?? 0);

            foreach ((clone $gapQuery)->select($this->cursorColumns())->orderBy('id')->cursor() as $row) {
                $this->accumulateVisitRowIntoState($state, $row, $useMonthlyBuckets, $today);
            }
        }

        return [$rawTotal, $unique, $rawQr];
    }

    /**
     * @return list<string>
     */
    private function cursorColumns(): array
    {
        return [
            'country_code',
            'city',
            'device_type',
            'browser',
            'browser_version',
            'operating_system',
            'operating_system_version',
            'referer_host',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'browser_language',
            'selected_variant',
            'visited_at',
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function accumulateVisitRowIntoState(array &$state, object $row, bool $useMonthlyBuckets, string $today): void
    {
        $visitedAt = Carbon::parse($row->visited_at);
        $bucket = $useMonthlyBuckets ? $visitedAt->format('Y-m') : $visitedAt->toDateString();

        if (array_key_exists($bucket, $state['visitsByDay'])) {
            $state['visitsByDay'][$bucket]++;
        }

        if ($bucket === $today || (! $useMonthlyBuckets && $visitedAt->toDateString() === $today)) {
            $state['todayVisits']++;
        }

        if (! empty($row->country_code)) {
            $code = strtoupper(trim($row->country_code));
            if ($code !== '') {
                $state['visitsByCountry'][$code] = ($state['visitsByCountry'][$code] ?? 0) + 1;
            }
        }

        if (! empty($row->city)) {
            $cityKey = $row->country_code
                ? "{$row->city} ({$row->country_code})"
                : $row->city;
            $state['visitsByCity'][$cityKey] = ($state['visitsByCity'][$cityKey] ?? 0) + 1;
        }

        if (! empty($row->device_type)) {
            $state['visitsByDevice'][$row->device_type] = ($state['visitsByDevice'][$row->device_type] ?? 0) + 1;
        }

        if (! empty($row->browser)) {
            $state['visitsByBrowser'][$row->browser] = ($state['visitsByBrowser'][$row->browser] ?? 0) + 1;
            $version = $row->browser_version ?: 'Unknown';
            $state['visitsByBrowserVersion'][$row->browser][$version] =
                ($state['visitsByBrowserVersion'][$row->browser][$version] ?? 0) + 1;
        }

        if (! empty($row->operating_system)) {
            $state['visitsByOs'][$row->operating_system] = ($state['visitsByOs'][$row->operating_system] ?? 0) + 1;
            $osVersion = $row->operating_system_version ?: 'Unknown';
            $state['visitsByOsVersion'][$row->operating_system][$osVersion] =
                ($state['visitsByOsVersion'][$row->operating_system][$osVersion] ?? 0) + 1;
        }

        $refererCategory = ShortUrl::resolveRefererCategory($row->referer_host ?: 'Direct');
        $state['visitsByReferer'][$refererCategory] = ($state['visitsByReferer'][$refererCategory] ?? 0) + 1;

        foreach ([
            'utmSources' => $row->utm_source,
            'utmMediums' => $row->utm_medium,
            'utmCampaigns' => $row->utm_campaign,
            'utmTerms' => $row->utm_term,
            'utmContents' => $row->utm_content,
            'visitsByLanguage' => $row->browser_language,
            'visitsByVariant' => $row->selected_variant,
        ] as $key => $value) {
            if (! empty($value)) {
                $state[$key][$value] = ($state[$key][$value] ?? 0) + 1;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function buildResult(
        array $state,
        Carbon $chartFrom,
        Carbon $chartTo,
        int $daysDiff,
        bool $useMonthlyBuckets,
        string $today,
        int $totalVisits,
        int $qrScans,
        int $uniqueVisits,
    ): array {
        $visitsByDay = $state['visitsByDay'];
        $startOfWeek = now()->startOfWeek()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $visitsThisWeek = 0;
        $visitsThisMonth = 0;

        foreach ($visitsByDay as $date => $cnt) {
            if ($useMonthlyBuckets) {
                continue;
            }
            if ($date >= $startOfWeek) {
                $visitsThisWeek += $cnt;
            }
            if ($date >= $startOfMonth) {
                $visitsThisMonth += $cnt;
            }
        }

        return [
            'totalVisits' => $totalVisits,
            'uniqueVisits' => $uniqueVisits,
            'visitsToday' => $visitsByDay[$today] ?? ($useMonthlyBuckets ? 0 : ($state['todayVisits'] ?? 0)),
            'visitsThisWeek' => $visitsThisWeek,
            'visitsThisMonth' => $visitsThisMonth,
            'visitsByDay' => $visitsByDay,
            'visitsByCountry' => $this->topDistribution($state['visitsByCountry']),
            'visitsByCity' => $this->topDistribution($state['visitsByCity']),
            'visitsByDevice' => $this->topDistribution($state['visitsByDevice']),
            'visitsByBrowser' => $this->topDistribution($state['visitsByBrowser']),
            'visitsByBrowserVersion' => $this->topVersionDistribution($state['visitsByBrowserVersion']),
            'visitsByOs' => $this->topDistribution($state['visitsByOs']),
            'visitsByOsVersion' => $this->topVersionDistribution($state['visitsByOsVersion']),
            'visitsByReferer' => $this->topDistribution($state['visitsByReferer']),
            'utmSources' => $this->topDistribution($state['utmSources']),
            'utmMediums' => $this->topDistribution($state['utmMediums']),
            'utmCampaigns' => $this->topDistribution($state['utmCampaigns']),
            'utmTerms' => $this->topDistribution($state['utmTerms']),
            'utmContents' => $this->topDistribution($state['utmContents']),
            'qrScans' => $qrScans,
            'visitsByLanguage' => $this->topDistribution($state['visitsByLanguage']),
            'visitsByVariant' => $this->topDistribution($state['visitsByVariant']),
        ];
    }

    /**
     * @param  array<string, int>  $distribution
     * @return array<string, int>
     */
    private function topDistribution(array $distribution): array
    {
        arsort($distribution);

        return array_slice($distribution, 0, self::DISTRIBUTION_LIMIT, true);
    }

    /**
     * @param  array<string, array<string, int>>  $distribution
     * @return array<string, array<string, int>>
     */
    private function topVersionDistribution(array $distribution): array
    {
        $results = [];

        foreach ($distribution as $name => $versions) {
            arsort($versions);
            $results[$name] = array_slice($versions, 0, self::VERSION_LIMIT, true);
        }

        return $results;
    }
}
