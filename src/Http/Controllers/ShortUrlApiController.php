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
        $validated = $request->validate($this->getValidationRules($request));

        $pixelIds = $validated['pixels'] ?? [];

        // Clean up parameters that shouldn't be mass assigned
        unset($validated['pixels']);

        $shortUrl = $this->service->create($validated);

        if (! empty($pixelIds)) {
            $shortUrl->pixels()->sync($pixelIds);
        }

        // Fire 'created' webhook if active
        $this->dispatchCreatedWebhook($shortUrl);

        return response()->json([
            'message' => 'Short URL created successfully.',
            'data' => $this->transformLink($shortUrl),
        ], 201);
    }

    /**
     * Display the specified short URL.
     */
    public function show(string|int $idOrKey): JsonResponse
    {
        $shortUrl = $this->findLink($idOrKey);

        return response()->json([
            'data' => $this->transformLink($shortUrl),
        ]);
    }

    /**
     * Display statistics/analytics for the specified short URL.
     */
    public function stats(Request $request, string|int $idOrKey): JsonResponse
    {
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
    public function update(Request $request, string|int $idOrKey): JsonResponse
    {
        $shortUrl = $this->findLink($idOrKey);

        // Merge existing dates for validation if only one of them is sent
        if ($request->has('expires_at') && ! $request->has('activated_at')) {
            $request->merge(['activated_at' => $shortUrl->activated_at?->toIso8601String()]);
        }
        if ($request->has('activated_at') && ! $request->has('expires_at')) {
            $request->merge(['expires_at' => $shortUrl->expires_at?->toIso8601String()]);
        }

        $validated = $request->validate($this->getValidationRules($request, $shortUrl));

        $pixelIds = $validated['pixels'] ?? null;
        unset($validated['pixels']);

        $shortUrl->update($validated);

        if ($pixelIds !== null) {
            $shortUrl->pixels()->sync($pixelIds);
        }

        return response()->json([
            'message' => 'Short URL updated successfully.',
            'data' => $this->transformLink($shortUrl->fresh()),
        ]);
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
     * Get the validation rules for creating or updating a short URL.
     *
     * @return array<string, mixed>
     */
    private function getValidationRules(Request $request, ?ShortUrl $model = null): array
    {
        $countries = __('filament-short-url::countries');
        $countryRule = is_array($countries) && ! empty($countries)
            ? 'in:'.implode(',', array_merge(array_keys($countries), array_map('strtolower', array_keys($countries))))
            : 'string|max:10';

        $languages = __('filament-short-url::languages');
        $languageRule = is_array($languages) && ! empty($languages)
            ? 'in:'.implode(',', array_merge(array_keys($languages), array_map('strtoupper', array_keys($languages))))
            : 'string|max:10';

        $isUpdate = $model !== null;

        // Apply after_or_equal:today only if the activated_at date is actually being changed
        $activatedAtRule = 'nullable|date';
        if ($isUpdate) {
            if ($request->has('activated_at') && $request->input('activated_at') !== $model->activated_at?->toIso8601String() && $request->input('activated_at') !== $model->activated_at?->toDateTimeString()) {
                $activatedAtRule .= '|after_or_equal:today';
            }
        } else {
            $activatedAtRule .= '|after_or_equal:today';
        }

        $uniqueKeyRule = 'unique:short_urls,url_key';
        if ($isUpdate) {
            $uniqueKeyRule .= ','.$model->id;
        }

        return [
            'destination_url' => ($isUpdate ? 'sometimes|' : '').'required|url|max:2048',
            'url_key' => ($isUpdate ? 'sometimes|' : '').'nullable|string|alpha_dash|max:32|'.$uniqueKeyRule,
            'notes' => 'nullable|string|max:1000',
            'is_enabled' => 'nullable|boolean',
            'redirect_status_code' => 'nullable|integer|in:301,302',
            'single_use' => 'nullable|boolean',
            'forward_query_params' => 'nullable|boolean',
            'max_visits' => 'nullable|integer|min:1',
            'expiration_redirect_url' => 'nullable|url|max:2048',
            'activated_at' => $activatedAtRule,
            'expires_at' => 'nullable|date|after_or_equal:activated_at',
            'webhook_url' => 'nullable|url|max:2048',
            'targeting_rules' => 'nullable|array',
            'targeting_rules.type' => 'required_with:targeting_rules|string|in:none,device,geo,language,rotation',
            'targeting_rules.device' => 'nullable|array',
            'targeting_rules.device.mobile' => 'nullable|url|max:2048',
            'targeting_rules.device.tablet' => 'nullable|url|max:2048',
            'targeting_rules.device.desktop' => 'nullable|url|max:2048',
            'targeting_rules.device.ios' => 'nullable|url|max:2048',
            'targeting_rules.device.android' => 'nullable|url|max:2048',
            'targeting_rules.geo' => 'nullable|array',
            'targeting_rules.geo.*.country_code' => 'required_with:targeting_rules.geo|distinct:ignore_case|'.$countryRule,
            'targeting_rules.geo.*.url' => 'required_with:targeting_rules.geo|url|max:2048',
            'targeting_rules.language' => 'nullable|array',
            'targeting_rules.language.*.language_code' => 'required_with:targeting_rules.language|distinct:ignore_case|'.$languageRule,
            'targeting_rules.language.*.url' => 'required_with:targeting_rules.language|url|max:2048',
            'targeting_rules.rotation' => 'nullable|array',
            'targeting_rules.rotation.*.url' => 'required_with:targeting_rules.rotation|url|max:2048',
            'targeting_rules.rotation.*.weight' => 'required_with:targeting_rules.rotation|integer|min:1|max:1000',
            'password' => 'nullable|string|max:255',
            'show_warning_page' => 'nullable|boolean',
            'auto_open_app_mobile' => 'nullable|boolean',
            'ga_tracking_id' => 'nullable|string|regex:/^G-[A-Z0-9]+$/',
            'track_visits' => 'nullable|boolean',
            'track_ip_address' => 'nullable|boolean',
            'track_browser' => 'nullable|boolean',
            'track_browser_version' => 'nullable|boolean',
            'track_operating_system' => 'nullable|boolean',
            'track_operating_system_version' => 'nullable|boolean',
            'track_device_type' => 'nullable|boolean',
            'track_referer_url' => 'nullable|boolean',
            'track_browser_language' => 'nullable|boolean',
            'pixels' => 'nullable|array',
            'pixels.*' => 'integer|exists:short_url_pixels,id',
        ];
    }

    /**
     * Transform a ShortUrl model to API response array.
     */
    private function transformLink(ShortUrl $link): array
    {
        $pixels = $link->relationLoaded('pixels') ? $link->pixels : $link->pixels()->get();

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
            'webhook_url' => $link->webhook_url,
            'targeting_rules' => $link->targeting_rules,
            'password' => $link->password,
            'show_warning_page' => (bool) $link->show_warning_page,
            'auto_open_app_mobile' => (bool) $link->auto_open_app_mobile,
            'ga_tracking_id' => $link->ga_tracking_id,
            'track_visits' => (bool) $link->track_visits,
            'track_ip_address' => (bool) $link->track_ip_address,
            'track_browser' => (bool) $link->track_browser,
            'track_browser_version' => (bool) $link->track_browser_version,
            'track_operating_system' => (bool) $link->track_operating_system,
            'track_operating_system_version' => (bool) $link->track_operating_system_version,
            'track_device_type' => (bool) $link->track_device_type,
            'track_referer_url' => (bool) $link->track_referer_url,
            'track_browser_language' => (bool) $link->track_browser_language,
            'pixels' => $pixels->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'pixel_id' => $p->pixel_id,
                'is_active' => (bool) $p->is_active,
            ])->toArray(),
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
