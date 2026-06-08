<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTempStorage;
use Bjanczak\FilamentShortUrl\Services\Stats\CrossDimensionalStatsEngine;
use Bjanczak\FilamentShortUrl\Services\StatsSqlHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateAndPruneVisitsCommand extends Command
{
    /** @var string */
    protected $signature = 'short-url:aggregate-and-prune';

    /** @var string */
    protected $description = 'Aggregate past days visits into daily stats and prune old raw visit logs';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();
        $driver = DB::connection()->getDriverName();
        $dateExpression = match ($driver) {
            'pgsql' => 'visited_at::date',
            'sqlsrv' => 'CAST(visited_at AS DATE)',
            default => 'DATE(visited_at)',
        };

        $lastAggregationDate = DB::table('short_url_settings')
            ->where('key', 'last_aggregation_date')
            ->value('value');

        $candidateDates = DB::table('short_url_visits')
            ->where('visited_at', '<', $today)
            ->where('is_bot', false)
            ->where('is_proxy', false)
            ->when($lastAggregationDate, fn ($query) => $query->where('visited_at', '>', Carbon::parse($lastAggregationDate)->endOfDay()))
            ->selectRaw("{$dateExpression} as aggregate_date")
            ->distinct()
            ->orderBy('aggregate_date')
            ->pluck('aggregate_date')
            ->map(function ($value): string {
                return Carbon::parse($value)->toDateString();
            })
            ->all();

        $dates = [];
        foreach ($candidateDates as $date) {
            $nextDate = Carbon::parse($date)->addDay()->toDateString();
            $start = $date.' 00:00:00';
            $end = $nextDate.' 00:00:00';

            $rawCount = (int) DB::table('short_url_visits')
                ->where('visited_at', '>=', $start)
                ->where('visited_at', '<', $end)
                ->where('is_bot', false)
                ->where('is_proxy', false)
                ->count();

            $dailyCountQuery = DB::table('short_url_daily_stats');
            StatsSqlHelper::applyDailyStatsDateEquals($dailyCountQuery, $date);
            $dailyCount = (int) $dailyCountQuery->sum('visits_count');

            $missingCrossRollupsQuery = DB::table('short_url_daily_stats');
            StatsSqlHelper::applyDailyStatsDateEquals($missingCrossRollupsQuery, $date);
            $missingCrossRollups = $missingCrossRollupsQuery
                ->where(function ($query): void {
                    $query->whereNull('cross_dimensional_stats')
                        ->orWhereNull('cross_filter_pairs');
                })
                ->exists();

            if ($dailyCount === 0 || $rawCount !== $dailyCount || $missingCrossRollups) {
                $dates[] = $date;
            }
        }

        if (empty($dates)) {
            $this->info('No historical visits to aggregate.');
        } else {
            $this->info('Found '.count($dates).' days to aggregate.');

            $maxAggregatedDate = null;
            $affectedShortUrlIds = [];

            foreach ($dates as $date) {
                DB::transaction(function () use ($date, &$affectedShortUrlIds): void {
                    $nextDate = Carbon::parse($date)->addDay()->toDateString();
                    $start = $date.' 00:00:00';
                    $end = $nextDate.' 00:00:00';

                    $driver = DB::connection()->getDriverName();
                    $qrExpr = $driver === 'pgsql'
                        ? 'count(case when is_qr_scan::int = 1 then 1 end) as qr_scans'
                        : 'count(case when is_qr_scan = 1 then 1 end) as qr_scans';
                    $botExpr = $driver === 'pgsql'
                        ? 'count(case when is_bot::int = 1 then 1 end) as bot_visits'
                        : 'count(case when is_bot = 1 then 1 end) as bot_visits';
                    $proxyExpr = $driver === 'pgsql'
                        ? 'count(case when is_proxy::int = 1 then 1 end) as proxy_visits'
                        : 'count(case when is_proxy = 1 then 1 end) as proxy_visits';

                    $totals = DB::table('short_url_visits')
                        ->where('visited_at', '>=', $start)
                        ->where('visited_at', '<', $end)
                        ->where('is_bot', false)
                        ->where('is_proxy', false)
                        ->select([
                            'short_url_id',
                            DB::raw('count(*) as total'),
                            DB::raw('count(distinct ip_hash) as uniques'),
                            DB::raw($qrExpr),
                        ])
                        ->groupBy('short_url_id')
                        ->get();

                    $securityTotals = DB::table('short_url_visits')
                        ->where('visited_at', '>=', $start)
                        ->where('visited_at', '<', $end)
                        ->select([
                            'short_url_id',
                            DB::raw('count(*) as all_visits'),
                            DB::raw($botExpr),
                            DB::raw($proxyExpr),
                        ])
                        ->groupBy('short_url_id')
                        ->get()
                        ->keyBy('short_url_id');

                    if ($totals->isEmpty() && $securityTotals->isEmpty()) {
                        return;
                    }

                    $statsByUrl = [];

                    foreach ($totals as $row) {
                        $statsByUrl[$row->short_url_id] = array_merge(CrossDimensionalStatsEngine::emptyBucket(), [
                            'total' => (int) $row->total,
                            'uniques' => (int) $row->uniques,
                            'qr_scans' => (int) $row->qr_scans,
                            'all_visits' => 0,
                            'bot_visits' => 0,
                            'proxy_visits' => 0,
                        ]);
                    }

                    foreach ($securityTotals as $urlId => $secRow) {
                        if (! isset($statsByUrl[$urlId])) {
                            $statsByUrl[$urlId] = array_merge(CrossDimensionalStatsEngine::emptyBucket(), [
                                'total' => 0,
                                'uniques' => 0,
                                'qr_scans' => 0,
                                'all_visits' => (int) $secRow->all_visits,
                                'bot_visits' => (int) $secRow->bot_visits,
                                'proxy_visits' => (int) $secRow->proxy_visits,
                            ]);

                            continue;
                        }

                        $statsByUrl[$urlId]['all_visits'] = (int) $secRow->all_visits;
                        $statsByUrl[$urlId]['bot_visits'] = (int) $secRow->bot_visits;
                        $statsByUrl[$urlId]['proxy_visits'] = (int) $secRow->proxy_visits;
                    }

                    $cursor = DB::table('short_url_visits')
                        ->where('visited_at', '>=', $start)
                        ->where('visited_at', '<', $end)
                        ->where('is_bot', false)
                        ->where('is_proxy', false)
                        ->select([
                            'short_url_id',
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
                            'is_qr_scan',
                        ])
                        ->orderBy('id')
                        ->cursor();

                    foreach ($cursor as $row) {
                        $urlId = $row->short_url_id;

                        if (! isset($statsByUrl[$urlId])) {
                            $statsByUrl[$urlId] = array_merge(CrossDimensionalStatsEngine::emptyBucket(), [
                                'total' => 0,
                                'uniques' => 0,
                                'qr_scans' => 0,
                                'all_visits' => 0,
                                'bot_visits' => 0,
                                'proxy_visits' => 0,
                            ]);
                        }

                        CrossDimensionalStatsEngine::accumulateHumanVisit($row, $statsByUrl[$urlId]);
                    }

                    foreach ($statsByUrl as $urlId => $bucket) {
                        $exported = CrossDimensionalStatsEngine::exportForPersistence($bucket);
                        $payload = [
                            'visits_count' => $bucket['total'],
                            'unique_visits_count' => $bucket['uniques'],
                            'all_visits_count' => $bucket['all_visits'],
                            'bot_visits_count' => $bucket['bot_visits'],
                            'proxy_visits_count' => $bucket['proxy_visits'],
                            'qr_visits_count' => $bucket['qr_scans'],
                            ...$exported,
                        ];

                        $existingDaily = ShortUrlDailyStats::query()
                            ->where('short_url_id', $urlId);

                        StatsSqlHelper::applyDailyStatsDateEquals($existingDaily, $date);
                        $existingDaily = $existingDaily->first();

                        if ($existingDaily !== null) {
                            $existingDaily->update($payload);
                        } else {
                            ShortUrlDailyStats::query()->create([
                                'short_url_id' => $urlId,
                                'date' => $date,
                                ...$payload,
                            ]);
                        }

                        $affectedShortUrlIds[] = (int) $urlId;
                    }
                });

                $this->info("Aggregated stats for {$date}.");
                $maxAggregatedDate = $date;
            }

            if ($maxAggregatedDate !== null) {
                DB::table('short_url_settings')->updateOrInsert(
                    ['key' => 'last_aggregation_date'],
                    [
                        'value' => $maxAggregatedDate,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $affectedShortUrlIds = array_values(array_unique($affectedShortUrlIds));
            if (! empty($affectedShortUrlIds)) {
                ShortUrl::whereIn('id', $affectedShortUrlIds)
                    ->get()
                    ->each(function (ShortUrl $shortUrl): void {
                        $shortUrl->clearStatsCache();
                    });
            }
        }

        if (config('filament-short-url.pruning.enabled', true)) {
            $retentionDays = (int) config('filament-short-url.pruning.retention_days', 90);
            $cutoff = Carbon::now()->subDays($retentionDays)->toDateTimeString();

            $deleted = 0;
            do {
                $chunkDeleted = ShortUrlVisit::where('visited_at', '<', $cutoff)
                    ->orderBy('id')
                    ->limit(5000)
                    ->delete();
                $deleted += $chunkDeleted;
            } while ($chunkDeleted > 0);

            $this->info("Successfully pruned {$deleted} raw visit records older than {$retentionDays} days.");
        }

        $prunedCount = app(ShortUrlTempStorage::class)->pruneBucketsOlderThanHours(24);

        if ($prunedCount > 0) {
            $this->info("Successfully pruned {$prunedCount} temporary files older than 24 hours.");
        }

        return 0;
    }
}
