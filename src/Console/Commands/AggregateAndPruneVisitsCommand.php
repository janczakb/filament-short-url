<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
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

        // 1. Find all unique dates before today that have visits (optimized range and compatible DATE extract)
        $dates = ShortUrlVisit::where('visited_at', '<', $today)
            ->selectRaw("{$dateExpression} as visit_date")
            ->distinct()
            ->pluck('visit_date')
            ->toArray();

        if (empty($dates)) {
            $this->info('No historical visits to aggregate.');
        } else {
            $this->info('Found '.count($dates).' days to aggregate.');

            foreach ($dates as $date) {
                // Wrap date aggregation in a database transaction for data integrity
                DB::transaction(function () use ($date): void {
                    // Accumulate stats per short_url_id using chunked reads — avoids loading
                    // potentially millions of rows into PHP memory at once.
                    $statsByUrl = [];
                    $nextDate = Carbon::parse($date)->addDay()->toDateString();

                    ShortUrlVisit::where('visited_at', '>=', $date.' 00:00:00')
                        ->where('visited_at', '<', $nextDate.' 00:00:00')
                        ->chunk(1000, function ($chunk) use (&$statsByUrl): void {
                            foreach ($chunk as $visit) {
                                $urlId = $visit->short_url_id;

                                if (! isset($statsByUrl[$urlId])) {
                                    $statsByUrl[$urlId] = [
                                        'total' => 0,
                                        'ip_hashes' => [],
                                        'device_stats' => [],
                                        'browser_stats' => [],
                                        'os_stats' => [],
                                        'country_stats' => [],
                                        'city_stats' => [],
                                        'referer_stats' => [],
                                        'utm_source_stats' => [],
                                        'utm_medium_stats' => [],
                                        'utm_campaign_stats' => [],
                                    ];
                                }

                                $s = &$statsByUrl[$urlId];
                                $s['total']++;

                                if ($visit->ip_hash) {
                                    $s['ip_hashes'][$visit->ip_hash] = true;
                                }

                                $inc = function (?string $value, string $key) use (&$s): void {
                                    if ($value) {
                                        $s[$key][$value] = ($s[$key][$value] ?? 0) + 1;
                                    }
                                };

                                $inc($visit->device_type, 'device_stats');
                                $inc($visit->browser, 'browser_stats');
                                $inc($visit->operating_system, 'os_stats');
                                $inc($visit->country, 'country_stats');
                                $inc($visit->utm_source, 'utm_source_stats');
                                $inc($visit->utm_medium, 'utm_medium_stats');
                                $inc($visit->utm_campaign, 'utm_campaign_stats');

                                if ($visit->city) {
                                    $cityKey = "{$visit->city} ({$visit->country_code})";
                                    $s['city_stats'][$cityKey] = ($s['city_stats'][$cityKey] ?? 0) + 1;
                                }

                                if ($visit->referer_host) {
                                    $s['referer_stats'][$visit->referer_host] = ($s['referer_stats'][$visit->referer_host] ?? 0) + 1;
                                }
                            }
                        });

                    foreach ($statsByUrl as $urlId => $s) {
                        ShortUrlDailyStats::updateOrCreate([
                            'short_url_id' => $urlId,
                            'date' => $date,
                        ], [
                            'visits_count' => $s['total'],
                            'unique_visits_count' => count($s['ip_hashes']),
                            'device_stats' => $s['device_stats'],
                            'browser_stats' => $s['browser_stats'],
                            'os_stats' => $s['os_stats'],
                            'country_stats' => $s['country_stats'],
                            'city_stats' => $s['city_stats'],
                            'referer_stats' => $s['referer_stats'],
                            'utm_source_stats' => $s['utm_source_stats'],
                            'utm_medium_stats' => $s['utm_medium_stats'],
                            'utm_campaign_stats' => $s['utm_campaign_stats'],
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

        return 0;
    }
}
