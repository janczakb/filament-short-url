<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Http\Requests\StoreShortUrlRequest;
use Bjanczak\FilamentShortUrl\Http\Requests\UpdateShortUrlRequest;
use Bjanczak\FilamentShortUrl\Http\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class ShortUrlApiController extends Controller
{
    public function __construct(
        private readonly ShortUrlService $service,
        private readonly SafeBrowsingService $safeBrowsing,
    ) {}

    /**
     * Display a listing of short URLs.
     */
    public function index(): JsonResponse
    {
        $links = ShortUrl::with(['pixels', 'customDomain'])->orderBy('id', 'desc')->paginate(30);

        return ShortUrlResource::collection($links)->response();
    }

    /**
     * Store a newly created short URL.
     */
    public function store(StoreShortUrlRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pixelIds = $validated['pixels'] ?? [];

        // Clean up parameters that shouldn't be mass assigned
        unset($validated['pixels']);

        $shortUrl = $this->service->create($validated);

        if (! empty($pixelIds)) {
            $shortUrl->pixels()->sync($pixelIds);
        }

        // Fire 'created' webhook if active
        $this->dispatchCreatedWebhook($shortUrl);

        return (new ShortUrlResource($shortUrl))
            ->additional(['message' => 'Short URL created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified short URL.
     */
    public function show(string|int $idOrKey): JsonResponse
    {
        $shortUrl = $this->findLink($idOrKey);

        return (new ShortUrlResource($shortUrl))->response();
    }

    /**
     * Display statistics/analytics for the specified short URL.
     */
    public function stats(Request $request, string|int $idOrKey): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $shortUrl = $this->findLink($idOrKey);

        $stats = $shortUrl->getCachedStats(
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to')
        );

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Remove the specified short URL.
     */
    public function destroy(string|int $idOrKey): JsonResponse
    {
        $shortUrl = $this->findLink($idOrKey);
        $shortUrl->delete();

        return response()->json([
            'message' => 'Short URL deleted successfully.',
        ]);
    }

    /**
     * Update the specified short URL.
     */
    public function update(UpdateShortUrlRequest $request, string|int $idOrKey): JsonResponse
    {
        $shortUrl = $request->getModel();

        $validated = $request->validated();

        $pixelIds = $validated['pixels'] ?? null;
        unset($validated['pixels']);

        $shortUrl->update($validated);

        if ($pixelIds !== null) {
            $shortUrl->pixels()->sync($pixelIds);
        }

        return (new ShortUrlResource($shortUrl->fresh()))
            ->additional(['message' => 'Short URL updated successfully.'])
            ->response();
    }

    /**
     * Find a ShortUrl by database ID or URL key.
     */
    private function findLink(string|int $idOrKey): ShortUrl
    {
        if (is_numeric($idOrKey)) {
            return ShortUrl::findOrFail((int) $idOrKey);
        }

        $link = ShortUrl::where('url_key', $idOrKey)->first();

        if (! $link) {
            abort(404, 'Short URL not found.');
        }

        return $link;
    }

    /**
     * Dispatch webhook if global or per-link webhook is active for 'created' event.
     */
    private function dispatchCreatedWebhook(ShortUrl $shortUrl): void
    {
        $targetUrl = $shortUrl->webhook_url;
        $globalUrl = config('filament-short-url.global_webhook_url');
        $events = config('filament-short-url.webhook_events', []);

        if (empty($targetUrl) && ! empty($globalUrl) && in_array('created', $events)) {
            $targetUrl = $globalUrl;
        }

        if (! empty($targetUrl)) {
            try {
                $connection = config('filament-short-url.queue_connection', 'sync');

                $job = new SendWebhookJob(
                    url: $targetUrl,
                    event: 'created',
                    payload: [
                        'event' => 'created',
                        'timestamp' => now()->toIso8601String(),
                        'short_url' => (new ShortUrlResource($shortUrl))->resolve(),
                    ]
                );

                if ($connection) {
                    $job->onConnection($connection);
                } else {
                    $job->onConnection('sync');
                }

                dispatch($job);
            } catch (\Throwable $e) {
                Log::error('[FilamentShortUrl] Created webhook dispatch failed', [
                    'url_key' => $shortUrl->url_key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
