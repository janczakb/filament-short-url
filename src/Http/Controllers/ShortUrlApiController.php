<?php

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class ShortUrlApiController extends Controller
{
    public function __construct(
        private readonly ShortUrlService $service
    ) {}

    /**
     * Display a listing of short URLs.
     */
    public function index(): JsonResponse
    {
        $links = ShortUrl::orderBy('id', 'desc')->paginate(30);

        $transformed = $links->getCollection()->map(fn ($link) => $this->transformLink($link));

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $links->currentPage(),
                'last_page' => $links->lastPage(),
                'per_page' => $links->perPage(),
                'total' => $links->total(),
            ],
        ]);
    }

    /**
     * Store a newly created short URL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'destination_url' => 'required|url|max:2048',
            'url_key' => 'nullable|string|alpha_dash|max:50|unique:short_urls,url_key',
            'notes' => 'nullable|string|max:1000',
            'is_enabled' => 'nullable|boolean',
            'redirect_status_code' => 'nullable|integer|in:301,302',
            'single_use' => 'nullable|boolean',
            'forward_query_params' => 'nullable|boolean',
            'max_visits' => 'nullable|integer|min:1',
            'expiration_redirect_url' => 'nullable|url|max:2048',
            'activated_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'pixel_meta_id' => 'nullable|string|max:100',
            'pixel_google_id' => 'nullable|string|max:100',
            'pixel_linkedin_id' => 'nullable|string|max:100',
            'webhook_url' => 'nullable|url|max:2048',
        ]);

        $shortUrl = $this->service->create($validated);

        // Fire 'created' webhook if active
        $this->dispatchCreatedWebhook($shortUrl);

        return response()->json([
            'message' => 'Short URL created successfully.',
            'data' => $this->transformLink($shortUrl),
        ], 201);
    }

    /**
     * Remove the specified short URL.
     */
    public function destroy(int $id): JsonResponse
    {
        $shortUrl = ShortUrl::findOrFail($id);
        $shortUrl->delete();

        return response()->json([
            'message' => 'Short URL deleted successfully.',
        ]);
    }

    /**
     * Transform a ShortUrl model to API response array.
     */
    private function transformLink(ShortUrl $link): array
    {
        return [
            'id' => $link->id,
            'destination_url' => $link->destination_url,
            'url_key' => $link->url_key,
            'short_url' => $link->getShortUrl(),
            'is_enabled' => (bool) $link->is_enabled,
            'redirect_status_code' => (int) $link->redirect_status_code,
            'total_visits' => (int) $link->total_visits,
            'unique_visits' => (int) $link->unique_visits,
            'max_visits' => $link->max_visits ? (int) $link->max_visits : null,
            'activated_at' => $link->activated_at ? $link->activated_at->toIso8601String() : null,
            'expires_at' => $link->expires_at ? $link->expires_at->toIso8601String() : null,
            'pixel_meta_id' => $link->pixel_meta_id,
            'pixel_google_id' => $link->pixel_google_id,
            'pixel_linkedin_id' => $link->pixel_linkedin_id,
            'webhook_url' => $link->webhook_url,
            'notes' => $link->notes,
            'created_at' => $link->created_at->toIso8601String(),
        ];
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
                dispatch(new SendWebhookJob(
                    url: $targetUrl,
                    event: 'created',
                    payload: [
                        'event' => 'created',
                        'timestamp' => now()->toIso8601String(),
                        'short_url' => $this->transformLink($shortUrl),
                    ]
                )->onConnection($connection ?: 'sync'));
            } catch (\Throwable $e) {
                Log::error('[FilamentShortUrl] Created webhook dispatch failed', [
                    'url_key' => $shortUrl->url_key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
