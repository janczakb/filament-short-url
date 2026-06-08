<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Models\ShortUrlTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShortUrlTagApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tags = $this->scopedQuery($request)
            ->orderBy('name')
            ->paginate(30);

        return response()->json($tags);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:short_url_tags,slug',
            'color' => ['nullable', 'string', Rule::in(array_keys(ShortUrlTag::getColors()))],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $ownerUserId = $this->ownerUserId($request);
        if ($ownerUserId !== null) {
            $validated['user_id'] = $ownerUserId;
        }

        $tag = ShortUrlTag::create($validated);

        return response()->json([
            'data' => $tag,
            'message' => 'Tag created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tag = $this->findTag($request, $id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'slug' => 'sometimes|required|string|max:100|unique:short_url_tags,slug,'.$tag->id,
            'color' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(ShortUrlTag::getColors()))],
        ]);

        $tag->update($validated);

        return response()->json([
            'data' => $tag->fresh(),
            'message' => 'Tag updated successfully.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $tag = $this->findTag($request, $id);
        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully.',
        ]);
    }

    private function findTag(Request $request, int $id): ShortUrlTag
    {
        return $this->scopedQuery($request)->findOrFail($id);
    }

    /**
     * @return Builder<ShortUrlTag>
     */
    private function scopedQuery(Request $request)
    {
        $query = ShortUrlTag::query();
        $ownerUserId = $this->ownerUserId($request);

        if ($ownerUserId !== null) {
            $query->where('user_id', $ownerUserId);
        }

        return $query;
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
