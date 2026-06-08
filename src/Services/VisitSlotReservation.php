<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Atomically reserves visit slots for max_visits enforcement at redirect time.
 *
 * Counters are incremented here so parallel redirects cannot exceed the cap.
 * When tracking runs later, total/qr increments are skipped if already reserved.
 */
class VisitSlotReservation
{
    public function __construct(
        private readonly BotDetector $botDetector,
        private readonly ShortUrlTracker $tracker,
    ) {}

    /**
     * Reserve a visit slot when max_visits is configured.
     *
     * Returns false when the cap is reached. Returns true when there is no cap,
     * when the visitor is a bot (bots do not consume slots), or after a successful reservation.
     */
    public function tryReserve(ShortUrl $shortUrl, Request $request): bool
    {
        if ($shortUrl->max_visits === null) {
            return true;
        }

        if ($this->botDetector->isBot($request)) {
            return true;
        }

        if ($this->tracker->isDuplicateRequest($shortUrl->id, $request)) {
            return true;
        }

        $isQrScan = $request->query('source') === 'qr' || $request->query('qr') === '1';

        return $this->reserveAtomicSlot($shortUrl, $isQrScan);
    }

    /**
     * Whether visit tracking should skip total/qr counter increments (already reserved at redirect).
     */
    public function shouldSkipTotalIncrementInJob(ShortUrl $shortUrl, Request $request): bool
    {
        if ($shortUrl->max_visits === null) {
            return false;
        }

        if ($this->botDetector->isBot($request)) {
            return false;
        }

        if ($this->tracker->isDuplicateRequest($shortUrl->id, $request)) {
            return false;
        }

        return true;
    }

    /**
     * Whether redirect must increment counters even when visit rows are not tracked.
     */
    public function requiresCounterWithoutTracking(ShortUrl $shortUrl): bool
    {
        return $shortUrl->max_visits !== null && ! $shortUrl->track_visits;
    }

    /**
     * Atomically reserve a visit slot and increment total (and optional QR) counters.
     *
     * Used at redirect time for max_visits enforcement so parallel requests cannot exceed the cap.
     */
    private function reserveAtomicSlot(ShortUrl $shortUrl, bool $isQrScan = false): bool
    {
        if ($shortUrl->max_visits === null) {
            return true;
        }

        $threshold = max(1, (int) config('filament-short-url.max_visits_pessimistic_remaining', 5));
        $current = $shortUrl->getRealTimeTotalVisits();

        if ($current >= $shortUrl->max_visits) {
            return false;
        }

        $nearLimit = ($shortUrl->max_visits - $current) <= $threshold;
        $buffering = (bool) config('filament-short-url.counter_buffering.enabled', false);

        if ($buffering || $nearLimit) {
            return $this->tryAtomicVisitReservationWithLock($shortUrl, $isQrScan);
        }

        if ($this->tryAtomicVisitReservationFastPath($shortUrl, $isQrScan)) {
            return true;
        }

        return $this->tryAtomicVisitReservationWithLock($shortUrl, $isQrScan);
    }

    private function tryAtomicVisitReservationFastPath(ShortUrl $shortUrl, bool $isQrScan): bool
    {
        $updates = ['total_visits' => DB::raw('total_visits + 1')];

        if ($isQrScan) {
            $updates['qr_scans'] = DB::raw('qr_scans + 1');
        }

        $affected = $shortUrl->newQuery()
            ->where('id', $shortUrl->id)
            ->whereNotNull('max_visits')
            ->whereColumn('total_visits', '<', 'max_visits')
            ->update($updates);

        if ($affected !== 1) {
            return false;
        }

        $shortUrl->attributes['total_visits'] = ((int) ($shortUrl->attributes['total_visits'] ?? 0)) + 1;

        if ($isQrScan) {
            $shortUrl->attributes['qr_scans'] = ((int) ($shortUrl->attributes['qr_scans'] ?? 0)) + 1;
        }

        $shortUrl->touchVisitCountCache(1);

        return true;
    }

    private function tryAtomicVisitReservationWithLock(ShortUrl $shortUrl, bool $isQrScan): bool
    {
        return (bool) DB::transaction(function () use ($shortUrl, $isQrScan): bool {
            /** @var ShortUrl|null $locked */
            $locked = ShortUrl::query()
                ->where('id', $shortUrl->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return false;
            }

            if ($locked->getRealTimeTotalVisits() >= $locked->max_visits) {
                return false;
            }

            $locked->incrementVisits(isUnique: false, isQrScan: $isQrScan, incrementTotal: true);
            $shortUrl->setAttribute(
                'total_visits',
                $locked->getAttributes()['total_visits'] ?? $shortUrl->getAttributes()['total_visits'] ?? 0,
            );

            return true;
        });
    }
}
