<?php

namespace Bjanczak\FilamentShortUrl\Console\Commands;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AggregateAndPruneVisitsCommand extends Command
{
    /** @var string */
    protected $signature = 'short-url:aggregate-and-prune';

    /** @var string */
    protected $description = 'Aggregate past days visits into daily stats and prune old raw visit logs';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        // 1. Find all unique dates before today that have visits
        $dates = ShortUrlVisit::whereDate('visited_at', '<', $today)
            ->selectRaw('DATE(visited_at) as visit_date')
            ->distinct()
            ->pluck('visit_date')
            ->toArray();

        if (empty($dates)) {
            $this->info('No historical visits to aggregate.');
        } else {
            $this->info('Found ' . count($dates) . ' days to aggregate.');

            foreach ($dates as $date) {
                // Find all visits on this day
                $visits = ShortUrlVisit::whereDate('visited_at', '=', $date)->get();
                $visitsByUrl = $visits->groupBy('short_url_id');

                foreach ($visitsByUrl as $urlId => $urlVisits) {
                    $total = $urlVisits->count();
                    $unique = $urlVisits->unique('ip_hash')->count();

                    // Group helper
                    $groupByField = function ($collection, $field) {
                        return $collection->whereNotNull($field)
                            ->groupBy($field)
                            ->map(fn ($group) => $group->count())
                            ->toArray();
                    };

                    $deviceStats = $groupByField($urlVisits, 'device_type');
                    $browserStats = $groupByField($urlVisits, 'browser');
                    $osStats = $groupByField($urlVisits, 'operating_system');

                    // Country
                    $countryStats = $urlVisits->whereNotNull('country')
                        ->groupBy('country')
                        ->map(fn ($group) => $group->count())
                        ->toArray();

                    // City
                    $cityStats = $urlVisits->whereNotNull('city')
                        ->map(function ($visit) {
                            $visit->city_key = "{$visit->city} ({$visit->country_code})";
                            return $visit;
                        })
                        ->groupBy('city_key')
                        ->map(fn ($group) => $group->count())
                        ->toArray();

                    // Referer
                    $refererStats = $urlVisits->whereNotNull('referer_host')
                        ->groupBy('referer_host')
                        ->map(fn ($group) => $group->count())
                        ->toArray();

                    // UTM
                    $utmSourceStats = $groupByField($urlVisits, 'utm_source');
                    $utmMediumStats = $groupByField($urlVisits, 'utm_medium');
                    $utmCampaignStats = $groupByField($urlVisits, 'utm_campaign');

                    // Upsert into short_url_daily_stats
                    ShortUrlDailyStats::updateOrCreate([
                        'short_url_id' => $urlId,
                        'date' => $date,
                    ], [
                        'visits_count' => $total,
                        'unique_visits_count' => $unique,
                        'device_stats' => $deviceStats,
                        'browser_stats' => $browserStats,
                        'os_stats' => $osStats,
                        'country_stats' => $countryStats,
                        'city_stats' => $cityStats,
                        'referer_stats' => $refererStats,
                        'utm_source_stats' => $utmSourceStats,
                        'utm_medium_stats' => $utmMediumStats,
                        'utm_campaign_stats' => $utmCampaignStats,
                    ]);
                }

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
