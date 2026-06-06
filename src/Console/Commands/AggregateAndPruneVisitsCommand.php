<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        // 1. Find the oldest and newest visit dates before today using highly optimized min/max index scanning
        $oldestVisit = ShortUrlVisit::where('visited_at', '<', $today)->min('visited_at');
        if (! $oldestVisit) {
            $dates = [];
        } else {
            $latestVisit = ShortUrlVisit::where('visited_at', '<', $today)->max('visited_at');
            $startCarbon = Carbon::parse($oldestVisit)->startOfDay();
            $endCarbon = Carbon::parse($latestVisit)->startOfDay();

            $dates = [];
            $current = $startCarbon->copy();
            while ($current->lte($endCarbon)) {
                $dates[] = $current->toDateString();
                $current->addDay();
            }
        }

        if (empty($dates)) {
            $this->info('No historical visits to aggregate.');
        } else {
            $this->info('Found '.count($dates).' days to aggregate.');

            foreach ($dates as $date) {
                // Wrap date aggregation in a database transaction for data integrity
                DB::transaction(function () use ($date): void {
                    $nextDate = Carbon::parse($date)->addDay()->toDateString();
                    $start = $date.' 00:00:00';
                    $end = $nextDate.' 00:00:00';

                    // Driver-aware boolean count: MySQL/SQLite store booleans as TINYINT (= 1),
                    // PostgreSQL uses a native boolean type (cast to int for aggregation).
                    $driver = DB::connection()->getDriverName();
                    $qrExpr = $driver === 'pgsql'
                        ? 'count(case when is_qr_scan::int = 1 then 1 end) as qr_scans'
                        : 'count(case when is_qr_scan = 1 then 1 end) as qr_scans';

                    $totals = DB::table('short_url_visits')
                        ->where('visited_at', '>=', $start)
                        ->where('visited_at', '<', $end)
                        ->select([
                            'short_url_id',
                            DB::raw('count(*) as total'),
                            DB::raw('count(distinct ip_hash) as uniques'),
                            DB::raw($qrExpr),
                        ])
                        ->groupBy('short_url_id')
                        ->get();

                    if ($totals->isEmpty()) {
                        return;
                    }

                    $statsByUrl = [];
                    foreach ($totals as $row) {
                        $statsByUrl[$row->short_url_id] = [
                            'total' => (int) $row->total,
                            'uniques' => (int) $row->uniques,
                            'qr_scans' => (int) $row->qr_scans,
                            'device_stats' => [],
                            'browser_stats' => [],
                            'os_stats' => [],
                            'country_stats' => [],
                            'city_stats' => [],
                            'referer_stats' => [],
                            'utm_source_stats' => [],
                            'utm_medium_stats' => [],
                            'utm_campaign_stats' => [],
                            'language_stats' => [],
                            'variant_stats' => [],
                        ];
                    }

                    // Helper to fetch and populate category stats natively in database GROUP BY
                    $populateStats = function (string $column, string $statsKey) use ($start, $end, &$statsByUrl): void {
                        $urlIds = array_keys($statsByUrl);
                        if (empty($urlIds)) {
                            return;
                        }

                        $query = DB::table('short_url_visits')
                            ->where('visited_at', '>=', $start)
                            ->where('visited_at', '<', $end)
                            ->whereIn('short_url_id', $urlIds)
                            ->whereNotNull($column)
                            ->where($column, '<>', '');

                        if ($statsKey === 'city_stats') {
                            $query->select(['short_url_id', 'city', 'country_code', DB::raw('count(*) as count')])
                                ->groupBy(['short_url_id', 'city', 'country_code']);
                        } else {
                            $query->select(['short_url_id', $column, DB::raw('count(*) as count')])
                                ->groupBy(['short_url_id', $column]);
                        }

                        $rows = $query->get();

                        foreach ($rows as $row) {
                            $urlId = $row->short_url_id;
                            if (! isset($statsByUrl[$urlId])) {
                                continue;
                            }

                            if ($statsKey === 'city_stats') {
                                $cityVal = $row->city;
                                $countryCode = $row->country_code;
                                $val = $countryCode ? "{$cityVal} ({$countryCode})" : $cityVal;
                            } else {
                                $val = $row->$column;
                            }

                            $statsByUrl[$urlId][$statsKey][$val] = (int) $row->count;
                        }
                    };

                    // Populate all categories via 11 quick indexed database aggregations
                    $populateStats('device_type', 'device_stats');
                    $populateStats('browser', 'browser_stats');
                    $populateStats('operating_system', 'os_stats');
                    $populateStats('country_code', 'country_stats');
                    $populateStats('city', 'city_stats');
                    $populateStats('referer_host', 'referer_stats');
                    $populateStats('utm_source', 'utm_source_stats');
                    $populateStats('utm_medium', 'utm_medium_stats');
                    $populateStats('utm_campaign', 'utm_campaign_stats');
                    $populateStats('browser_language', 'language_stats');
                    $populateStats('selected_variant', 'variant_stats');

                    // Write aggregated stats to ShortUrlDailyStats
                    foreach ($statsByUrl as $urlId => $s) {
                        ShortUrlDailyStats::updateOrCreate([
                            'short_url_id' => $urlId,
                            'date' => $date,
                        ], [
                            'visits_count' => $s['total'],
                            'unique_visits_count' => $s['uniques'],
                            'device_stats' => $s['device_stats'],
                            'browser_stats' => $s['browser_stats'],
                            'os_stats' => $s['os_stats'],
                            'country_stats' => $s['country_stats'],
                            'city_stats' => $s['city_stats'],
                            'referer_stats' => $s['referer_stats'],
                            'utm_source_stats' => $s['utm_source_stats'],
                            'utm_medium_stats' => $s['utm_medium_stats'],
                            'utm_campaign_stats' => $s['utm_campaign_stats'],
                            'qr_visits_count' => $s['qr_scans'],
                            'language_stats' => $s['language_stats'],
                            'variant_stats' => $s['variant_stats'],
                        ]);
                    }
                });

                $this->info("Aggregated stats for {$date}.");
            }
        }

        // 2. Prune old visits if enabled
        if (config('filament-short-url.pruning.enabled', true)) {
            $retentionDays = (int) config('filament-short-url.pruning.retention_days', 90);
            $cutoff = Carbon::now()->subDays($retentionDays)->toDateTimeString();

            $deleted = ShortUrlVisit::where('visited_at', '<', $cutoff)->delete();

            $this->info("Successfully pruned {$deleted} raw visit records older than {$retentionDays} days.");
        }

        // 3. Prune old temporary logo files (older than 24 hours)
        $disk = Storage::disk('public');
        if ($disk->exists('short-urls/tmp')) {
            $files = $disk->files('short-urls/tmp');
            $now = time();
            $prunedCount = 0;

            foreach ($files as $file) {
                $lastModified = $disk->lastModified($file);
                if (($now - $lastModified) > 86400) { // 86400 seconds = 24 hours
                    $disk->delete($file);
                    $prunedCount++;
                }
            }

            if ($prunedCount > 0) {
                $this->info("Successfully pruned {$prunedCount} temporary logo files older than 24 hours.");
            }
        }

        return 0;
    }
}
