<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShortUrlGlobalOverview extends BaseWidget
{
    /** Disable automatic Livewire polling — cache is invalidated via model events instead. */
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    /** Cache key for link counts (forever, busted on create/delete). */
    public const LINKS_CACHE_KEY = 'filament-short-url:global-links';

    /** Cache key for click trends (short TTL, time-based data). */
    public const CLICKS_CACHE_KEY = 'filament-short-url:global-clicks';

    protected function getStats(): array
    {
        $linkData = $this->getLinkStats();
        $clickData = $this->getClickStats();

        $clicksTrend = $clickData['prev7Clicks'] > 0
            ? round((($clickData['last7Clicks'] - $clickData['prev7Clicks']) / $clickData['prev7Clicks']) * 100, 1)
            : ($clickData['last7Clicks'] > 0 ? 100.0 : 0.0);

        $uniquesTrend = $clickData['prev7Uniques'] > 0
            ? round((($clickData['last7Uniques'] - $clickData['prev7Uniques']) / $clickData['prev7Uniques']) * 100, 1)
            : ($clickData['last7Uniques'] > 0 ? 100.0 : 0.0);

        return [
            Stat::make(__('filament-short-url::default.navigation_label'), number_format($linkData['totalLinks']))
                ->description($linkData['activeLinks'].' active · '.$linkData['activeRatio'].'%')
                ->descriptionIcon('heroicon-m-link')
                ->icon('heroicon-o-link')
                ->color('gray'),

            Stat::make(__('filament-short-url::default.stats_card_total'), number_format($clickData['totalVisits']))
                ->description(abs($clicksTrend).'% '.($clicksTrend >= 0 ? 'up' : 'down').' vs last 7 days')
                ->descriptionIcon($clicksTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($clickData['clicksChart'] ?? [])
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color($clicksTrend >= 0 ? 'success' : 'danger'),

            Stat::make(__('filament-short-url::default.stats_card_unique'), number_format($clickData['uniqueVisits']))
                ->description(abs($uniquesTrend).'% '.($uniquesTrend >= 0 ? 'up' : 'down').' vs last 7 days')
                ->descriptionIcon($uniquesTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($clickData['uniquesChart'] ?? [])
                ->icon('heroicon-o-user-group')
                ->color($uniquesTrend >= 0 ? 'success' : 'danger'),
        ];
    }

    /**
     * Link counts cached forever — invalidated only when a ShortUrl is created or deleted.
     *
     * @return array{totalLinks: int, activeLinks: int, activeRatio: int}
     */
    protected function getLinkStats(): array
    {
        return Cache::rememberForever(self::LINKS_CACHE_KEY, function () {
            $agg = ShortUrl::query()
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN is_enabled THEN 1 ELSE 0 END) as active'),
                ])
                ->first();

            $total = (int) ($agg->total ?? 0);
            $active = (int) ($agg->active ?? 0);

            return [
                'totalLinks' => $total,
                'activeLinks' => $active,
                'activeRatio' => $total > 0 ? round(($active / $total) * 100) : 0,
            ];
        });
    }

    /**
     * Click/visit stats cached with a short TTL (time-based data changes frequently).
     *
     * @return array{totalVisits: int, uniqueVisits: int, last7Clicks: int, prev7Clicks: int, last7Uniques: int, prev7Uniques: int}
     */
    protected function getClickStats(): array
    {
        $ttl = (int) config('filament-short-url.geo_ip.stats_cache_ttl', 300);

        return Cache::remember(self::CLICKS_CACHE_KEY, $ttl, function () {
            $agg = ShortUrl::query()
                ->select([
                    DB::raw('SUM(total_visits) as total_visits'),
                    DB::raw('SUM(unique_visits) as unique_visits'),
                ])
                ->first();

            $last7Clicks = ShortUrlVisit::where('visited_at', '>=', now()->subDays(7))->count();
            $prev7Clicks = ShortUrlVisit::where('visited_at', '>=', now()->subDays(14))
                ->where('visited_at', '<', now()->subDays(7))
                ->count();

            $last7Uniques = ShortUrlVisit::where('visited_at', '>=', now()->subDays(7))
                ->distinct('ip_hash')
                ->count('ip_hash');

            $prev7Uniques = ShortUrlVisit::whereBetween('visited_at', [now()->subDays(14), now()->subDays(7)])
                ->distinct('ip_hash')
                ->count('ip_hash');

            // Fetch last 7 days chart data via database-level aggregation to avoid loading raw models into memory
            $driver = DB::connection()->getDriverName();
            $dateExpression = match ($driver) {
                'sqlite' => "strftime('%Y-%m-%d', visited_at)",
                'pgsql' => "to_char(visited_at, 'YYYY-MM-DD')",
                default => "DATE_FORMAT(visited_at, '%Y-%m-%d')",
            };

            $dailyClicks = ShortUrlVisit::select(DB::raw("{$dateExpression} as date"), DB::raw('COUNT(*) as count'))
                ->where('visited_at', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $dailyUniques = ShortUrlVisit::select(DB::raw("{$dateExpression} as date"), DB::raw('COUNT(DISTINCT ip_hash) as count'))
                ->where('visited_at', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $clicksChart = [];
            $uniquesChart = [];
            for ($i = 6; $i >= 0; $i--) {
                $targetDate = now()->subDays($i)->toDateString();
                $clicksChart[] = (int) ($dailyClicks[$targetDate] ?? 0);
                $uniquesChart[] = (int) ($dailyUniques[$targetDate] ?? 0);
            }

            return [
                'totalVisits' => (int) ($agg->total_visits ?? 0),
                'uniqueVisits' => (int) ($agg->unique_visits ?? 0),
                'last7Clicks' => $last7Clicks,
                'prev7Clicks' => $prev7Clicks,
                'last7Uniques' => $last7Uniques,
                'prev7Uniques' => $prev7Uniques,
                'clicksChart' => $clicksChart,
                'uniquesChart' => $uniquesChart,
            ];
        });
    }
}
