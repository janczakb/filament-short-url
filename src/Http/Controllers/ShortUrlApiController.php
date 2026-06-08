<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Http\Requests\StoreShortUrlRequest;
use Bjanczak\FilamentShortUrl\Http\Requests\UpdateShortUrlRequest;
use Bjanczak\FilamentShortUrl\Http\Requests\UpsertShortUrlRequest;
use Bjanczak\FilamentShortUrl\Http\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Http\Resources\ShortUrlVisitResource;
use Bjanczak\FilamentShortUrl\Http\Support\ApiLinkScope;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\VisitCsvExporter;
use Bjanczak\FilamentShortUrl\Support\ApiStatsFilterParser;
use Bjanczak\FilamentShortUrl\Support\ShortUrlCacheInvalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShortUrlApiController extends Controller
{
    public function __construct(
        private readonly ShortUrlService $service,
    ) {}

    /**
     * Display a listing of short URLs.
     */
    public function index(Request $request): JsonResponse
    {
        $links = ApiLinkScope::query($request)
            ->with(['pixels', 'customDomain', 'tags', 'folder'])
            ->orderBy('id', 'desc')
            ->paginate(30);

        return ShortUrlResource::collection($links)->response();
    }

    /**
     * Store a newly created short URL.
     */
    public function store(StoreShortUrlRequest $request): JsonResponse
    {
        $shortUrl = $this->persistLink($request, $request->validated());

        return (new ShortUrlResource($shortUrl))
            ->additional(['message' => __('filament-short-url::default.api_created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Create or update a short URL by external_id or url_key + destination match.
     */
    public function upsert(UpsertShortUrlRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $existing = $request->getExistingModel();

        if ($existing) {
            $pixelIds = $validated['pixels'] ?? null;
            $tagIds = $validated['tag_ids'] ?? null;
            unset($validated['pixels'], $validated['tag_ids']);

            DB::transaction(function () use ($existing, $validated, $pixelIds, $tagIds): void {
                $existing->update($validated);

                if ($pixelIds !== null) {
                    $existing->pixels()->sync($pixelIds);
                }

                if ($tagIds !== null) {
                    $existing->tags()->sync($tagIds);
                }
            });

            ShortUrlCacheInvalidator::forget($existing);

            return (new ShortUrlResource($existing->fresh(['pixels', 'customDomain', 'tags', 'folder'])))
                ->additional(['message' => __('filament-short-url::default.api_updated')])
                ->response();
        }

        $shortUrl = $this->persistLink($request, $validated);

        return (new ShortUrlResource($shortUrl))
            ->additional(['message' => __('filament-short-url::default.api_created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Bulk create short URLs (max 100).
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'links' => 'required|array|min:1|max:100',
        ]);

        $created = [];

        DB::transaction(function () use ($request, &$created): void {
            foreach ($request->input('links') as $index => $linkData) {
                if (! is_array($linkData)) {
                    abort(422, "Link at index {$index} must be an object.");
                }

                $subRequest = StoreShortUrlRequest::createFrom($request);
                $subRequest->setContainer(app());
                $subRequest->setRedirector(app('redirect'));
                $subRequest->replace($linkData);
                $subRequest->validateResolved();

                $created[] = $this->persistLink($subRequest, $subRequest->validated());
            }
        });

        return ShortUrlResource::collection(collect($created))
            ->additional(['message' => __('filament-short-url::default.api_bulk_created'), 'count' => count($created)])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Check whether a URL key is already taken.
     */
    public function exists(Request $request): JsonResponse
    {
        $request->validate([
            'url_key' => 'required|string|max:32',
            'custom_domain_id' => 'nullable|integer',
        ]);

        $urlKey = (string) $request->input('url_key');
        $domainScopeId = (int) ($request->input('custom_domain_id') ?? 0);

        return response()->json([
            'exists' => ApiLinkScope::query($request)
                ->where('url_key', $urlKey)
                ->where('domain_scope_id', $domainScopeId)
                ->exists(),
            'url_key' => $urlKey,
            'domain_scope_id' => $domainScopeId,
        ]);
    }

    /**
     * Return a random short URL from the scoped collection.
     */
    public function random(Request $request): JsonResponse
    {
        $link = ApiLinkScope::query($request)
            ->with(['pixels', 'customDomain', 'tags', 'folder'])
            ->inRandomOrder()
            ->first();

        if (! $link) {
            abort(404, __('filament-short-url::default.short_url_not_found'));
        }

        return (new ShortUrlResource($link))->response();
    }

    /**
     * Return lightweight info for a link by url_key or external_id.
     */
    public function info(Request $request): JsonResponse
    {
        $request->validate([
            'url_key' => 'required_without:external_id|nullable|string|max:32',
            'external_id' => 'required_without:url_key|nullable|string|max:255',
        ]);

        $query = ApiLinkScope::query($request);

        if ($request->filled('url_key')) {
            $link = $query->where('url_key', $request->query('url_key'))->first();
        } else {
            $link = $query->where('external_id', $request->query('external_id'))->first();
        }

        if (! $link) {
            abort(404, __('filament-short-url::default.short_url_not_found'));
        }

        return response()->json([
            'data' => [
                'id' => $link->id,
                'url_key' => $link->url_key,
                'external_id' => $link->external_id,
                'short_url' => $link->getShortUrl(),
                'destination_url' => $link->destination_url,
                'is_enabled' => (bool) $link->is_enabled,
                'is_archived' => (bool) $link->is_archived,
                'total_visits' => (int) $link->total_visits,
                'unique_visits' => (int) $link->unique_visits,
                'created_at' => $link->created_at->toIso8601String(),
                'updated_at' => $link->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Display the specified short URL.
     */
    public function show(Request $request, string|int $idOrKey): JsonResponse
    {
        $shortUrl = ApiLinkScope::find($request, $idOrKey);

        return (new ShortUrlResource($shortUrl))->response();
    }

    /**
     * Display statistics/analytics for the specified short URL.
     */
    public function stats(Request $request, string|int $idOrKey): JsonResponse
    {
        $request->validate(array_merge([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ], ApiStatsFilterParser::validationRules()));

        $shortUrl = ApiLinkScope::find($request, $idOrKey);
        $filters = ApiStatsFilterParser::fromRequest($request);

        $stats = $shortUrl->getCachedStats(
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
            filters: $filters,
        );

        return response()->json([
            'data' => $stats,
            'meta' => [
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
                'filters' => $filters,
            ],
        ]);
    }

    /**
     * Remove the specified short URL.
     */
    public function destroy(Request $request, string|int $idOrKey): JsonResponse
    {
        $shortUrl = ApiLinkScope::find($request, $idOrKey);
        $shortUrl->delete();

        return response()->json([
            'message' => __('filament-short-url::default.api_deleted'),
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
        $tagIds = $validated['tag_ids'] ?? null;
        unset($validated['pixels'], $validated['tag_ids']);

        DB::transaction(function () use ($shortUrl, $validated, $pixelIds, $tagIds): void {
            $shortUrl->update($validated);

            if ($pixelIds !== null) {
                $shortUrl->pixels()->sync($pixelIds);
            }

            if ($tagIds !== null) {
                $shortUrl->tags()->sync($tagIds);
            }
        });

        ShortUrlCacheInvalidator::forget($shortUrl);

        return (new ShortUrlResource($shortUrl->fresh(['pixels', 'customDomain', 'tags', 'folder'])))
            ->additional(['message' => __('filament-short-url::default.api_updated')])
            ->response();
    }

    /**
     * List visit logs for the specified short URL.
     */
    public function visits(Request $request, string|int $idOrKey): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $shortUrl = ApiLinkScope::find($request, $idOrKey);
        $perPage = (int) $request->query('per_page', 30);

        $visits = ShortUrlVisit::query()
            ->where('short_url_id', $shortUrl->id)
            ->orderByDesc('visited_at')
            ->paginate($perPage);

        return ShortUrlVisitResource::collection($visits)->response();
    }

    /**
     * Export visit logs as CSV for the specified short URL.
     */
    public function exportVisits(Request $request, string|int $idOrKey, VisitCsvExporter $exporter): StreamedResponse
    {
        $shortUrl = ApiLinkScope::find($request, $idOrKey);
        $filename = "visits-{$shortUrl->url_key}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($shortUrl, $exporter) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            $exporter->stream(
                ShortUrlVisit::query()
                    ->where('short_url_id', $shortUrl->id)
                    ->orderByDesc('visited_at')
                    ->cursor(),
                fn (array $row) => fputcsv($handle, $row)
            );

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Bulk delete short URLs by ID or URL key.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required_without:keys|array|min:1|max:100',
            'ids.*' => 'integer|exists:short_urls,id',
            'keys' => 'required_without:ids|array|min:1|max:100',
            'keys.*' => 'string|max:32',
        ]);

        $query = ApiLinkScope::query($request);

        if (! empty($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
        } else {
            $query->whereIn('url_key', $validated['keys']);
        }

        $links = $query->with('customDomain')->get();

        foreach ($links as $link) {
            $link->delete();
        }

        ShortUrlCacheInvalidator::forgetMany($links);
        $deleted = $links->count();

        return response()->json([
            'message' => __('filament-short-url::default.api_bulk_deleted'),
            'deleted' => $deleted,
        ]);
    }

    /**
     * Bulk update short URLs by ID or URL key.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required_without:keys|array|min:1|max:100',
            'ids.*' => 'integer|exists:short_urls,id',
            'keys' => 'required_without:ids|array|min:1|max:100',
            'keys.*' => 'string|max:32',
            'data' => 'required|array|min:1',
            'data.is_enabled' => 'sometimes|boolean',
            'data.is_archived' => 'sometimes|boolean',
            'data.notes' => 'sometimes|nullable|string|max:255',
            'data.expires_at' => 'sometimes|nullable|date',
            'data.max_visits' => 'sometimes|nullable|integer|min:1',
        ]);

        $query = ApiLinkScope::query($request);

        if (! empty($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
        } else {
            $query->whereIn('url_key', $validated['keys']);
        }

        $links = $query->with('customDomain')->get();

        foreach ($links as $link) {
            $link->update($validated['data']);
        }

        ShortUrlCacheInvalidator::forgetMany($links);
        $updated = $links->count();

        return response()->json([
            'message' => __('filament-short-url::default.api_bulk_updated'),
            'updated' => $updated,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function persistLink(Request $request, array $validated): ShortUrl
    {
        $pixelIds = $validated['pixels'] ?? [];
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['pixels'], $validated['tag_ids']);

        $ownerUserId = $this->ownerUserId($request);
        if ($ownerUserId !== null) {
            $validated['user_id'] = $ownerUserId;
        }

        $shortUrl = $this->service->create($validated);

        if (! empty($pixelIds)) {
            $shortUrl->pixels()->sync($pixelIds);
        }

        if (! empty($tagIds)) {
            $shortUrl->tags()->sync($tagIds);
        }

        return $shortUrl->fresh(['pixels', 'customDomain', 'tags', 'folder']);
    }

    private function ownerUserId(Request $request): ?int
    {
        /** @var array<string, mixed>|null $apiKey */
        $apiKey = $request->attributes->get('fsu_api_key');

        if (is_array($apiKey) && ! empty($apiKey['owner_user_id'])) {
            return (int) $apiKey['owner_user_id'];
        }

        return null;
    }
}
