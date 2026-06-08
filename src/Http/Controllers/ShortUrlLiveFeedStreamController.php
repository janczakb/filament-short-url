<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\LiveFeedBroadcaster;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShortUrlLiveFeedStreamController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, ShortUrl $shortUrl): StreamedResponse
    {
        $this->authorize('view', $shortUrl);

        $cursor = (int) $request->query('cursor', 0);
        $intervalSeconds = max(1, (int) config('filament-short-url.live_feed.sse_interval_seconds', 3));
        $maxDurationSeconds = max(0, (int) config('filament-short-url.live_feed.sse_max_duration_seconds', 120));

        return response()->stream(function () use ($shortUrl, $cursor, $intervalSeconds, $maxDurationSeconds): void {
            if (LiveFeedBroadcaster::usesRedisPush()) {
                $this->streamWithRedisPush($shortUrl, $cursor, $intervalSeconds, $maxDurationSeconds);

                return;
            }

            $this->streamWithPolling($shortUrl, $cursor, $intervalSeconds, $maxDurationSeconds);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Redis pub/sub push: block on SUBSCRIBE between heartbeats (no sleep polling, no DB).
     */
    private function streamWithRedisPush(
        ShortUrl $shortUrl,
        int $cursor,
        int $intervalSeconds,
        int $maxDurationSeconds,
    ): void {
        $lastId = $cursor;
        $deadline = time() + $maxDurationSeconds;

        while (time() < $deadline && ! connection_aborted()) {
            $latest = LiveFeedBroadcaster::latestId($shortUrl->id);

            if ($latest > $lastId) {
                $this->emitUpdate($latest);
                $lastId = $latest;
            }

            $remaining = max(1, min($intervalSeconds, $deadline - time()));
            $pushedId = LiveFeedBroadcaster::waitForPublish($shortUrl->id, $remaining);

            if ($pushedId !== null && $pushedId > $lastId) {
                $this->emitUpdate($pushedId);
                $lastId = $pushedId;

                continue;
            }

            $latest = LiveFeedBroadcaster::latestId($shortUrl->id);

            if ($latest > $lastId) {
                $this->emitUpdate($latest);
                $lastId = $latest;
            } else {
                $this->emitHeartbeat();
            }
        }
    }

    /**
     * Fallback when Redis is unavailable: poll cache cursor (and DB once when cache is cold).
     */
    private function streamWithPolling(
        ShortUrl $shortUrl,
        int $cursor,
        int $intervalSeconds,
        int $maxDurationSeconds,
    ): void {
        $lastId = $cursor;
        $deadline = time() + $maxDurationSeconds;

        while (time() < $deadline && ! connection_aborted()) {
            $latest = $this->resolveLatestVisitId($shortUrl);

            if ($latest > $lastId) {
                $this->emitUpdate($latest);
                $lastId = $latest;
            } else {
                $this->emitHeartbeat();
            }

            sleep($intervalSeconds);
        }
    }

    private function resolveLatestVisitId(ShortUrl $shortUrl): int
    {
        $latest = LiveFeedBroadcaster::latestId($shortUrl->id);

        if ($latest > 0) {
            return $latest;
        }

        return (int) ShortUrlVisit::query()
            ->where('short_url_id', $shortUrl->id)
            ->where('is_bot', false)
            ->where('is_proxy', false)
            ->max('id');
    }

    private function emitUpdate(int $latestId): void
    {
        echo 'event: update'."\n";
        echo 'data: '.json_encode(['latest_id' => $latestId], JSON_THROW_ON_ERROR)."\n\n";
        $this->flushOutput();
    }

    private function emitHeartbeat(): void
    {
        echo ": heartbeat\n\n";
        $this->flushOutput();
    }

    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
