<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ShortUrlWorldMapWidget extends Widget
{
    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    protected string $view = 'filament-short-url::widgets.world-map';

    protected int|string|array $columnSpan = 'full';

    public function mount(?ShortUrl $record = null): void
    {
        $this->record = $record;
    }

    /**
     * Build a country_code → visit count map, merging daily stats and today's raw visits.
     *
     * @return array<string, int>
     */
    public function getCountryData(): array
    {
        if (! $this->record) {
            return [];
        }

        $dateFromClean = $this->dateFrom ? Carbon::parse($this->dateFrom)->toDateString() : null;
        $dateToClean = $this->dateTo ? Carbon::parse($this->dateTo)->toDateString() : null;
        $today = Carbon::today()->toDateString();

        $cacheTtl = (int) config('filament-short-url.geo_ip.stats_cache_ttl', 300);
        $cacheKey = "short_url_world_map_{$this->record->id}_".($dateFromClean ?: 'all').'_'.($dateToClean ?: 'all');

        return cache()->remember($cacheKey, $cacheTtl, function () use ($dateFromClean, $dateToClean) {
            $counts = [];

            $query = ShortUrlVisit::query()
                ->select('country_code', DB::raw('COUNT(*) as cnt'))
                ->where('short_url_id', $this->record->id)
                ->whereNotNull('country_code')
                ->where('country_code', '!=', '')
                ->where('is_bot', false)
                ->where('is_proxy', false);

            if ($dateFromClean) {
                $query->whereDate('visited_at', '>=', $dateFromClean);
            }
            if ($dateToClean) {
                $query->whereDate('visited_at', '<=', $dateToClean);
            }

            foreach ($query->groupBy('country_code')->get() as $row) {
                $code = strtoupper(trim($row->country_code));
                if ($code) {
                    $counts[$code] = (int) $row->cnt;
                }
            }

            arsort($counts);

            return $counts;
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $countryData = $this->getCountryData();
        $max = max(1, ...array_values($countryData) ?: [1]);
        $total = array_sum($countryData);

        // Normalise to 0–100 for CSS opacity/intensity
        $normalized = [];
        foreach ($countryData as $code => $count) {
            $normalized[$code] = round(($count / $max) * 100);
        }

        return [
            'countryData' => $countryData,
            'maxCount' => $max,
            'totalClicks' => $total,
            'normalized' => $normalized,
        ];
    }
}
