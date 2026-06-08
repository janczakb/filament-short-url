<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsCacheHelper;
use Bjanczak\FilamentShortUrl\Services\Stats\StatsScalingProfile;
use Bjanczak\FilamentShortUrl\Services\Stats\TodayStatsBuffer;
use Bjanczak\FilamentShortUrl\Services\StatsSqlHelper;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ShortUrlVisitsChart extends ChartWidget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected ?string $maxHeight = '200px';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = null;

    public string $activeMetric = 'total';

    protected string $view = 'filament-short-url::widgets.visits-chart';

    public function mount(): void
    {
        $this->setDefaultFilter();
    }

    protected function setDefaultFilter(): void
    {
        $start = $this->dateFrom ? Carbon::parse($this->dateFrom) : now()->subDays(29);
        $end = $this->dateTo ? Carbon::parse($this->dateTo) : now();
        $daysDiff = (int) $start->diffInDays($end);

        if ($daysDiff <= 1) {
            $this->filter = 'hourly';
        } elseif ($daysDiff <= 30) {
            $this->filter = 'daily';
        } elseif ($daysDiff <= 90) {
            $this->filter = 'weekly';
        } else {
            $this->filter = 'monthly';
        }
    }

    public function getHeading(): string
    {
        return __('filament-short-url::default.stats_chart_title');
    }

    protected function getFilters(): ?array
    {
        return [
            'hourly' => __('filament-short-url::default.stats_granularity_hourly'),
            'daily' => __('filament-short-url::default.stats_granularity_daily'),
            'weekly' => __('filament-short-url::default.stats_granularity_weekly'),
            'monthly' => __('filament-short-url::default.stats_granularity_monthly'),
        ];
    }

    public function getTimelineData(Carbon $start, Carbon $end, string $metric, string $granularity, array $filters): array
    {
        $dateFromClean = $start->toDateString();
        $dateToClean = $end->toDateString();
        $today = Carbon::today()->toDateString();
        $driver = DB::connection()->getDriverName();

        $buckets = [];
        $labels = [];

        if ($granularity === 'hourly') {
            $hoursDiff = (int) $start->diffInHours($end);
            if ($hoursDiff > 168) {
                $granularity = 'daily';
            } else {
                $current = $start->copy()->startOfHour();
                $endHour = $end->copy()->endOfHour();
                while ($current->lte($endHour)) {
                    $bucketKey = $current->format('Y-m-d H:00');
                    $buckets[$bucketKey] = 0;
                    $labels[$bucketKey] = $current->format('d.m H:i');
                    $current->addHour();
                }
            }
        }

        if ($granularity === 'weekly') {
            $current = $start->copy()->startOfWeek();
            while ($current->lte($end)) {
                $bucketKey = $current->format('o-W');
                $buckets[$bucketKey] = 0;
                $labels[$bucketKey] = 'W'.$current->format('W').' ('.$current->format('d.m').')';
                $current->addWeek();
            }
        } elseif ($granularity === 'monthly') {
            $current = $start->copy()->startOfMonth();
            while ($current->lte($end)) {
                $bucketKey = $current->format('Y-m');
                $buckets[$bucketKey] = 0;
                $labels[$bucketKey] = $current->format('m.Y');
                $current->addMonth();
            }
        } elseif ($granularity === 'daily') {
            $daysDiff = (int) $start->diffInDays($end);
            for ($i = $daysDiff; $i >= 0; $i--) {
                $d = $end->copy()->subDays($i)->format('Y-m-d');
                $buckets[$d] = 0;
                $labels[$d] = Carbon::parse($d)->format('d.m');
            }
        }

        if (empty($filters) && $granularity !== 'hourly') {
            $dailyStatsQuery = DB::table('short_url_daily_stats')
                ->where('short_url_id', $this->record->id);

            StatsSqlHelper::applyDailyStatsDateRange($dailyStatsQuery, $dateFromClean, $dateToClean);
            $dailyStatsQuery->orderBy('date', 'asc');

            $col = match ($metric) {
                'unique' => 'unique_visits_count',
                'qr' => 'qr_visits_count',
                default => 'visits_count',
            };

            $dailyRows = $dailyStatsQuery->pluck($col, 'date')->toArray();

            foreach ($dailyRows as $date => $count) {
                $carbonDate = Carbon::parse($date);
                if ($granularity === 'weekly') {
                    $bucketKey = $carbonDate->format('o-W');
                } elseif ($granularity === 'monthly') {
                    $bucketKey = $carbonDate->format('Y-m');
                } else {
                    $bucketKey = $date;
                }

                if (array_key_exists($bucketKey, $buckets)) {
                    $buckets[$bucketKey] += (int) $count;
                }
            }

            if ($dateToClean >= $today) {
                $todayCount = 0;
                $profile = app(StatsScalingProfile::class);

                if (empty($filters) && $profile->usesRedisTodayBuffer()) {
                    $redisHourly = app(TodayStatsBuffer::class)->getHourlyTotals(
                        (int) $this->record->id,
                        Carbon::parse($today)->startOfDay(),
                        Carbon::parse($today)->endOfDay(),
                        $metric,
                    );
                    $todayCount = array_sum($redisHourly);
                }

                if ($todayCount === 0) {
                    $todayRawQuery = DB::table('short_url_visits')
                        ->where('short_url_id', $this->record->id)
                        ->where('visited_at', '>=', $today.' 00:00:00')
                        ->where('is_bot', false)
                        ->where('is_proxy', false);

                    $aggregateExpression = match ($metric) {
                        'unique' => 'COUNT(DISTINCT ip_hash)',
                        'qr' => 'SUM(CASE WHEN is_qr_scan = 1 THEN 1 ELSE 0 END)',
                        default => 'COUNT(*)',
                    };

                    $todayCount = (int) $todayRawQuery->selectRaw("{$aggregateExpression} as cnt")->value('cnt');
                }

                $carbonToday = Carbon::parse($today);
                if ($granularity === 'weekly') {
                    $todayBucket = $carbonToday->format('o-W');
                } elseif ($granularity === 'monthly') {
                    $todayBucket = $carbonToday->format('Y-m');
                } else {
                    $todayBucket = $today;
                }

                if (array_key_exists($todayBucket, $buckets)) {
                    $buckets[$todayBucket] += $todayCount;
                }
            }
        } elseif (! empty($filters) && $granularity !== 'hourly' && $metric === 'total') {
            $stats = $this->record->getCachedStats(
                dateFrom: $dateFromClean,
                dateTo: $dateToClean,
                filters: $filters,
            );

            foreach ($stats['visitsByDay'] ?? [] as $date => $count) {
                if ($granularity === 'monthly' && strlen((string) $date) === 7) {
                    $bucketKey = $date;
                } else {
                    $carbonDate = Carbon::parse($date);
                    $bucketKey = match ($granularity) {
                        'weekly' => $carbonDate->format('o-W'),
                        'monthly' => $carbonDate->format('Y-m'),
                        default => $date,
                    };
                }

                if (array_key_exists($bucketKey, $buckets)) {
                    $buckets[$bucketKey] += (int) $count;
                }
            }
        } elseif (empty($filters) && $granularity === 'hourly') {
            $profile = app(StatsScalingProfile::class);
            $useRedisToday = $profile->usesRedisTodayBuffer() && $dateToClean >= $today;

            $retentionDays = (int) config('filament-short-url.pruning.retention_days', 90);
            $rawCutoff = Carbon::today()->subDays($retentionDays)->toDateString();
            $effectiveFrom = $dateFromClean >= $rawCutoff ? $dateFromClean : $rawCutoff;

            if (! $useRedisToday || $effectiveFrom < $today) {
                $query = DB::table('short_url_visits')
                    ->where('short_url_id', $this->record->id)
                    ->where('visited_at', '>=', $effectiveFrom.' 00:00:00')
                    ->where('visited_at', '<=', $dateToClean.' 23:59:59')
                    ->where('is_bot', false)
                    ->where('is_proxy', false);

                if ($useRedisToday) {
                    $query->where('visited_at', '<', $today.' 00:00:00');
                }

                $aggregateExpression = match ($metric) {
                    'unique' => 'COUNT(DISTINCT ip_hash)',
                    'qr' => 'SUM(CASE WHEN is_qr_scan = 1 THEN 1 ELSE 0 END)',
                    default => 'COUNT(*)',
                };

                $dateExpression = match ($driver) {
                    'sqlite' => "strftime('%Y-%m-%d %H:00', visited_at)",
                    'pgsql' => "to_char(visited_at, 'YYYY-MM-DD HH24:00')",
                    default => "DATE_FORMAT(visited_at, '%Y-%m-%d %H:00')",
                };

                $rows = $query->select(DB::raw("{$dateExpression} as time_bucket"), DB::raw("{$aggregateExpression} as count"))
                    ->groupBy('time_bucket')
                    ->pluck('count', 'time_bucket')
                    ->toArray();

                foreach ($rows as $bucket => $count) {
                    if (array_key_exists($bucket, $buckets)) {
                        $buckets[$bucket] = (int) $count;
                    }
                }
            }

            if ($useRedisToday) {
                $redisHourly = app(TodayStatsBuffer::class)->getHourlyTotals(
                    (int) $this->record->id,
                    $start->copy()->startOfHour()->max(Carbon::parse($today)->startOfDay()),
                    $end->copy()->endOfHour(),
                    $metric,
                );

                foreach ($redisHourly as $bucket => $count) {
                    if (array_key_exists($bucket, $buckets)) {
                        $buckets[$bucket] = (int) $count;
                    }
                }
            }
        } else {
            $retentionDays = (int) config('filament-short-url.pruning.retention_days', 90);
            $rawCutoff = Carbon::today()->subDays($retentionDays)->toDateString();
            $effectiveFrom = $dateFromClean >= $rawCutoff ? $dateFromClean : $rawCutoff;

            $query = DB::table('short_url_visits')
                ->where('short_url_id', $this->record->id)
                ->where('visited_at', '>=', $effectiveFrom.' 00:00:00')
                ->where('visited_at', '<=', $dateToClean.' 23:59:59')
                ->where('is_bot', false)
                ->where('is_proxy', false);

            $this->record->applyStatsFilters($query, $filters);

            $aggregateExpression = match ($metric) {
                'unique' => 'COUNT(DISTINCT ip_hash)',
                'qr' => 'SUM(CASE WHEN is_qr_scan = 1 THEN 1 ELSE 0 END)',
                default => 'COUNT(*)',
            };

            $weeklyHandled = false;

            if ($granularity === 'weekly' && $driver === 'sqlite') {
                $uniqueHashes = [];

                foreach ($query->select(['visited_at', 'ip_hash', 'is_qr_scan'])->cursor() as $row) {
                    $bucketKey = Carbon::parse($row->visited_at)->format('o-W');
                    if (! array_key_exists($bucketKey, $buckets)) {
                        continue;
                    }

                    if ($metric === 'unique') {
                        $hash = $row->ip_hash ?? '';
                        if ($hash === '' || isset($uniqueHashes[$bucketKey][$hash])) {
                            continue;
                        }
                        $uniqueHashes[$bucketKey][$hash] = true;
                        $buckets[$bucketKey]++;
                    } elseif ($metric === 'qr') {
                        if ((bool) ($row->is_qr_scan ?? false)) {
                            $buckets[$bucketKey]++;
                        }
                    } else {
                        $buckets[$bucketKey]++;
                    }
                }

                $weeklyHandled = true;
            }

            if (! $weeklyHandled) {
                $dateExpression = match ($granularity) {
                    'hourly' => match ($driver) {
                        'sqlite' => "strftime('%Y-%m-%d %H:00', visited_at)",
                        'pgsql' => "to_char(visited_at, 'YYYY-MM-DD HH24:00')",
                        default => "DATE_FORMAT(visited_at, '%Y-%m-%d %H:00')",
                    },
                    'weekly' => match ($driver) {
                        'pgsql' => "to_char(visited_at, 'IYYY-IW')",
                        default => 'YEARWEEK(visited_at, 3)',
                    },
                    'monthly' => match ($driver) {
                        'sqlite' => "strftime('%Y-%m', visited_at)",
                        'pgsql' => "to_char(visited_at, 'YYYY-MM')",
                        default => "DATE_FORMAT(visited_at, '%Y-%m')",
                    },
                    default => match ($driver) {
                        'sqlite' => "strftime('%Y-%m-%d', visited_at)",
                        'pgsql' => "to_char(visited_at, 'YYYY-MM-DD')",
                        default => "DATE_FORMAT(visited_at, '%Y-%m-%d')",
                    },
                };

                $rows = $query->select(DB::raw("{$dateExpression} as time_bucket"), DB::raw("{$aggregateExpression} as count"))
                    ->groupBy('time_bucket')
                    ->pluck('count', 'time_bucket')
                    ->toArray();

                foreach ($rows as $bucket => $count) {
                    if ($granularity === 'weekly' && $driver !== 'sqlite' && $driver !== 'pgsql') {
                        if (strlen((string) $bucket) === 6) {
                            $year = substr((string) $bucket, 0, 4);
                            $week = substr((string) $bucket, 4, 2);
                            $bucket = "{$year}-{$week}";
                        }
                    }

                    if (array_key_exists($bucket, $buckets)) {
                        $buckets[$bucket] = (int) $count;
                    }
                }
            }
        }

        return [
            'data' => array_values($buckets),
            'labels' => array_values($labels),
        ];
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $start = $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : now()->subDays(29)->startOfDay();
        $end = $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : now()->endOfDay();
        $granularity = $this->filter ?: 'daily';
        $filtersHash = md5(json_encode($this->filters));

        $cacheKey = "short_url_chart_data_{$this->record->id}_".$start->toDateString().'_'.$end->toDateString().'_'.$granularity.'_'.$filtersHash.'_'.$this->activeMetric;
        $this->record->registerCacheKey($cacheKey);

        $chartData = StatsCacheHelper::remember($cacheKey, function () use ($start, $end, $granularity) {
            return $this->getTimelineData($start, $end, $this->activeMetric, $granularity, $this->filters);
        });

        $currentValues = $chartData['data'] ?? [];
        $labels = $chartData['labels'] ?? [];

        $diffInDays = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($diffInDays);
        $prevEnd = $start->copy()->subDay();

        $prevCacheKey = "short_url_chart_data_{$this->record->id}_".$prevStart->toDateString().'_'.$prevEnd->toDateString().'_'.$granularity.'_'.$filtersHash.'_'.$this->activeMetric;
        $this->record->registerCacheKey($prevCacheKey);

        $prevChartData = StatsCacheHelper::remember($prevCacheKey, function () use ($prevStart, $prevEnd, $granularity) {
            return $this->getTimelineData($prevStart, $prevEnd, $this->activeMetric, $granularity, $this->filters);
        });

        $prevValues = $prevChartData['data'] ?? [];

        // Align/pad previous data structure to match current labels length exactly
        $countCurrent = count($currentValues);
        $countPrev = count($prevValues);
        if ($countPrev < $countCurrent) {
            $prevValues = array_pad($prevValues, $countCurrent, 0);
        } elseif ($countPrev > $countCurrent) {
            $prevValues = array_slice($prevValues, 0, $countCurrent);
        }

        $metricLabel = match ($this->activeMetric) {
            'unique' => __('filament-short-url::default.stats_card_unique'),
            'qr' => __('filament-short-url::default.stats_card_qr_scans'),
            default => __('filament-short-url::default.qr_chart_visits_label'),
        };

        return [
            'datasets' => [
                [
                    'label' => $metricLabel,
                    'data' => $currentValues,
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.08)',
                    'borderWidth' => 2,
                    'pointBackgroundColor' => 'rgb(99, 102, 241)',
                    'pointRadius' => 3,
                    'tension' => 0.4,
                    'fill' => true,
                ],
                [
                    'label' => $metricLabel.' ('.__('filament-short-url::default.stats_prev_period').')',
                    'data' => $prevValues,
                    'borderColor' => 'rgba(99, 102, 241, 0.35)',
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                    'borderDash' => [5, 5],
                    'pointRadius' => 0,
                    'tension' => 0.4,
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
