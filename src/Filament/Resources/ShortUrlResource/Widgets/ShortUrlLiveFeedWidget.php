<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\Concerns\HasStatsFilters;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\LiveFeedBroadcaster;
use Filament\Widgets\Widget;

class ShortUrlLiveFeedWidget extends Widget
{
    use HasStatsFilters;

    public ?ShortUrl $record = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    /**
     * ID of the most recently rendered visit.
     * Stored in the Livewire snapshot so each browser tab tracks independently.
     * Initialized to -1 so mount can detect "first load" vs "subsequent polls".
     */
    public int $latestVisitId = -1;

    protected string $view = 'filament-short-url::widgets.live-feed';

    /**
     * Called when the SSE stream reports a newer visit id.
     */
    public function onStreamUpdate(int $latestId): void
    {
        if (! $this->record) {
            $this->skipRender();

            return;
        }

        if ($latestId <= $this->latestVisitId) {
            $this->skipRender();

            return;
        }

        $this->latestVisitId = $latestId;
    }

    /**
     * Called on every wire:poll tick (legacy) or from SSE client.
     *
     * Does a single O(1) MAX(id) query against the composite index.
     * If nothing changed since the last render → skipRender() → ~100-byte response.
     * Only when the cursor advances does a full re-render occur.
     */
    public function checkForUpdates(): void
    {
        if (! $this->record) {
            $this->skipRender();

            return;
        }

        $latest = (int) ShortUrlVisit::query()
            ->where('short_url_id', $this->record->id)
            ->where('is_bot', false)
            ->where('is_proxy', false)
            ->max('id');

        if ($latest === $this->latestVisitId) {
            // Nothing new — skip rendering entirely.
            $this->skipRender();

            return;
        }

        // New visit detected — update the cursor; render is triggered automatically.
        $this->latestVisitId = $latest;
    }

    protected function getViewData(): array
    {
        if (! $this->record) {
            return [
                'visits' => [],
                'usesRedisPush' => LiveFeedBroadcaster::usesRedisPush(),
            ];
        }

        // BUG FIX #1: Include latestVisitId in the cache key.
        // Without this, user B could get a 3-second-old cache response that doesn't
        // include the visit that triggered their re-render, then skipRender() on every
        // subsequent poll because their latestVisitId is already up-to-date — causing
        // them to permanently miss that visit until the next new visit arrives.
        $filtersHash = md5(json_encode($this->filters ?? []));
        $cacheKey = "live_feed_{$this->record->id}_{$this->dateFrom}_{$this->dateTo}_{$filtersHash}_{$this->latestVisitId}";

        $visits = cache()->remember($cacheKey, 3, function () {
            $query = ShortUrlVisit::query()
                ->where('short_url_id', $this->record->id)
                ->where('is_bot', false)
                ->where('is_proxy', false);

            if ($this->dateFrom) {
                $query->where('visited_at', '>=', $this->dateFrom.' 00:00:00');
            }
            if ($this->dateTo) {
                $query->where('visited_at', '<=', $this->dateTo.' 23:59:59');
            }

            $this->record->applyStatsFilters($query, $this->filters ?? []);

            return $query
                ->latest('visited_at')
                ->limit(25)
                ->get([
                    'id', 'visited_at', 'country', 'country_code', 'city',
                    'browser', 'operating_system', 'device_type',
                    'referer_host', 'referer_url', 'ip_address',
                    'is_qr_scan', 'selected_variant',
                ])
                ->map(fn (ShortUrlVisit $visit) => [
                    'id' => $visit->id,
                    'time_ago' => $visit->visited_at->diffForHumans(),
                    'ip_address' => $visit->ip_address,
                    'flag_url' => $visit->country_code
                        ? 'https://flagcdn.com/h20/'.strtolower($visit->country_code).'.webp'
                        : null,
                    'country' => $visit->country,
                    'country_code' => $visit->country_code,
                    'city' => $visit->city,
                    'browser' => $visit->browser,
                    'operating_system' => $visit->operating_system,
                    'device_type' => $visit->device_type,
                    'referer_host' => $visit->referer_host,
                    'referer_url' => $visit->referer_url,
                    'is_qr_scan' => $visit->is_qr_scan,
                    'selected_variant' => $visit->selected_variant,
                ])
                ->all();
        });

        // BUG FIX #2: Initialize the cursor on first load so the very first poll
        // does not trigger an unnecessary re-render.
        // $latestVisitId starts at -1 (sentinel). After the initial getViewData()
        // we set it to the actual MAX id so checkForUpdates() correctly skips
        // re-renders when there are no new visits.
        if ($this->latestVisitId === -1) {
            $this->latestVisitId = ! empty($visits)
                ? (int) $visits[0]['id']
                : (int) ShortUrlVisit::query()
                    ->where('short_url_id', $this->record->id)
                    ->where('is_bot', false)
                    ->where('is_proxy', false)
                    ->max('id');
        }

        return ['visits' => $visits, 'usesRedisPush' => LiveFeedBroadcaster::usesRedisPush()];
    }
}
