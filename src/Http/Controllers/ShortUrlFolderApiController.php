<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Models\ShortUrlFolder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShortUrlFolderApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $folders = $this->scopedQuery($request)
            ->orderBy('name')
            ->paginate(30);

        return response()->json($folders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:short_url_folders,slug',
            'color' => ['nullable', 'string', Rule::in(array_keys(ShortUrlFolder::getColors()))],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $ownerUserId = $this->ownerUserId($request);
        if ($ownerUserId !== null) {
            $validated['user_id'] = $ownerUserId;
        }

        $folder = ShortUrlFolder::create($validated);

        return response()->json([
            'data' => $folder,
            'message' => 'Folder created successfully.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $folder = $this->findFolder($request, $id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'slug' => 'sometimes|required|string|max:100|unique:short_url_folders,slug,'.$folder->id,
            'color' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(ShortUrlFolder::getColors()))],
        ]);

        $folder->update($validated);

        return response()->json([
            'data' => $folder->fresh(),
            'message' => 'Folder updated successfully.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $folder = $this->findFolder($request, $id);
        $folder->delete();

        return response()->json([
            'message' => 'Folder deleted successfully.',
        ]);
    }

    private function findFolder(Request $request, int $id): ShortUrlFolder
    {
        return $this->scopedQuery($request)->findOrFail($id);
    }

    /**
     * @return Builder<ShortUrlFolder>
     */
    private function scopedQuery(Request $request)
    {
        $query = ShortUrlFolder::query();
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
