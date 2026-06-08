<?php

namespace Bjanczak\FilamentShortUrl\Models\Concerns;

use Bjanczak\FilamentShortUrl\Services\Stats\StatsCacheHelper;
use Bjanczak\FilamentShortUrl\Services\StatsSqlHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

trait HasSecurityStats
{
    /**
     * Cached bot/proxy breakdown for the security widget (single aggregated query + daily rollups).
     *
     * @return array{
     *     totalClicks: int,
     *     humanClicks: int,
     *     botClicks: int,
     *     humanPercentage: float,
     *     botPercentage: float,
     *     proxyClicks: int,
     *     proxyPercentage: float
     * }
     */
    public function getSecurityBreakdownStats(?string $dateFrom = null, ?string $dateTo = null, array $filters = []): array
    {
        $dateFromClean = $dateFrom ? Carbon::parse($dateFrom)->toDateString() : null;
        $dateToClean = $dateTo ? Carbon::parse($dateTo)->toDateString() : null;
        $filtersHash = md5(json_encode($filters));

        $cacheKey = "short_url_security_{$this->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all').'_'.$filtersHash;
        $this->registerCacheKey($cacheKey);

        return StatsCacheHelper::remember($cacheKey, function () use ($dateFromClean, $dateToClean, $filters) {
            $today = Carbon::today()->toDateString();
            $effectiveTo = $dateToClean ?? $today;
            $retentionDays = (int) config('filament-short-url.pruning.retention_days', 90);
            $rawCutoff = Carbon::today()->subDays($retentionDays)->toDateString();

            $totalClicks = 0;
            $botClicks = 0;
            $proxyClicks = 0;

            if (empty($filters) && ($dateFromClean === null || $dateFromClean < $rawCutoff)) {
                $prunedEnd = $effectiveTo;
                if ($prunedEnd >= $rawCutoff) {
                    $prunedEnd = Carbon::parse($rawCutoff)->subDay()->toDateString();
                }

                $dailyQuery = DB::table('short_url_daily_stats')
                    ->where('short_url_id', $this->id);

                StatsSqlHelper::applyDailyStatsDateBefore($dailyQuery, $today);
                StatsSqlHelper::applyDailyStatsDateRange($dailyQuery, $dateFromClean, $prunedEnd);

                $sums = $dailyQuery->selectRaw('
                    COALESCE(SUM(all_visits_count), 0) as total,
                    COALESCE(SUM(bot_visits_count), 0) as bots,
                    COALESCE(SUM(proxy_visits_count), 0) as proxies
                ')->first();

                $totalClicks += (int) ($sums->total ?? 0);
                $botClicks += (int) ($sums->bots ?? 0);
                $proxyClicks += (int) ($sums->proxies ?? 0);
            }

            $rawFrom = $dateFromClean !== null && $dateFromClean > $rawCutoff ? $dateFromClean : $rawCutoff;

            if ($effectiveTo >= $rawFrom || ! empty($filters)) {
                $query = DB::table('short_url_visits')
                    ->where('short_url_id', $this->id);

                if (! empty($filters)) {
                    $query->when($dateFromClean, fn ($q) => $q->where('visited_at', '>=', $dateFromClean.' 00:00:00'));
                } else {
                    $query->where('visited_at', '>=', $rawFrom.' 00:00:00');
                }

                if ($dateToClean !== null) {
                    $query->where('visited_at', '<=', $dateToClean.' 23:59:59');
                }

                $this->applyStatsFilters($query, $filters);

                $driver = DB::connection()->getDriverName();
                $botExpr = $driver === 'pgsql'
                    ? 'SUM(CASE WHEN is_bot::int = 1 THEN 1 ELSE 0 END)'
                    : 'SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END)';
                $proxyExpr = $driver === 'pgsql'
                    ? 'SUM(CASE WHEN is_proxy::int = 1 THEN 1 ELSE 0 END)'
                    : 'SUM(CASE WHEN is_proxy = 1 THEN 1 ELSE 0 END)';

                $row = $query->selectRaw("COUNT(*) as total, {$botExpr} as bots, {$proxyExpr} as proxies")->first();

                if (! empty($filters)) {
                    $totalClicks = (int) ($row->total ?? 0);
                    $botClicks = (int) ($row->bots ?? 0);
                    $proxyClicks = (int) ($row->proxies ?? 0);
                } else {
                    $totalClicks += (int) ($row->total ?? 0);
                    $botClicks += (int) ($row->bots ?? 0);
                    $proxyClicks += (int) ($row->proxies ?? 0);
                }
            }

            $humanClicks = max(0, $totalClicks - $botClicks - $proxyClicks);

            return [
                'totalClicks' => $totalClicks,
                'humanClicks' => $humanClicks,
                'botClicks' => $botClicks,
                'humanPercentage' => $totalClicks > 0 ? round(($humanClicks / $totalClicks) * 100, 1) : 0.0,
                'botPercentage' => $totalClicks > 0 ? round(($botClicks / $totalClicks) * 100, 1) : 0.0,
                'proxyClicks' => $proxyClicks,
                'proxyPercentage' => $totalClicks > 0 ? round(($proxyClicks / $totalClicks) * 100, 1) : 0.0,
            ];
        });
    }
}
