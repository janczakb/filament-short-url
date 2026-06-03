<?php

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Upload QR logo file from admin panel.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        Log::info('ShortUrlApiController::uploadLogo called', [
            'auth_check' => auth()->check(),
            'user_id' => auth()->id(),
            'has_file' => $request->hasFile('logo'),
            'all_files' => array_keys($request->allFiles()),
        ]);

        if (! auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'logo' => 'required|image|max:10240',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $tempPath = $file->getRealPath();
            $filename = Str::random(40).'.webp';
            $targetPath = 'short-urls/tmp/'.$filename;

            $processed = $this->processLogo($tempPath, $targetPath);

            if ($processed) {
                $path = $targetPath;
            } else {
                // Fallback: store raw file if image processing fails
                $path = $file->store('short-urls/tmp', 'public');
            }

            $url = route('short-url.logo', ['filename' => basename($path)]);

            return response()->json([
                'path' => $path,
                'url' => $url,
            ]);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }

    /**
     * Process the uploaded logo: detect available driver and optimize/convert to WebP.
     */
    private function processLogo(string $filePath, string $targetPath): bool
    {
        if (extension_loaded('imagick') && class_exists(\Imagick::class)) {
            return $this->processLogoWithImagick($filePath, $targetPath);
        }

        if (extension_loaded('gd') && function_exists('gd_info')) {
            return $this->processLogoWithGd($filePath, $targetPath);
        }

        return false;
    }

    /**
     * Process the logo using Imagick: scale down to 800px max edge and convert to WebP.
     */
    private function processLogoWithImagick(string $filePath, string $targetPath): bool
    {
        try {
            $imagick = new \Imagick($filePath);

            // Get original dimensions
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();

            // Calculate new dimensions
            $maxDim = 800;
            if ($width > $maxDim || $height > $maxDim) {
                if ($width > $height) {
                    $newWidth = $maxDim;
                    $newHeight = (int) round(($height * $maxDim) / $width);
                } else {
                    $newHeight = $maxDim;
                    $newWidth = (int) round(($width * $maxDim) / $height);
                }
                $imagick->scaleImage($newWidth, $newHeight);
            }

            // Convert to WebP format
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(85);

            // Get image data as blob
            $webpData = $imagick->getImageBlob();

            // Clear resources
            $imagick->clear();
            $imagick->destroy();

            if ($webpData) {
                return Storage::disk('public')->put($targetPath, $webpData);
            }
        } catch (\Throwable $e) {
            // Fall back to GD or raw store
        }

        return false;
    }

    /**
     * Process the uploaded logo using GD: scale down to 800px on the longer side (preserving aspect ratio) and convert to WebP.
     */
    private function processLogoWithGd(string $filePath, string $targetPath): bool
    {
        // 1. Get original dimensions and type
        $info = @getimagesize($filePath);
        if (! $info) {
            return false;
        }

        [$width, $height, $type] = $info;

        // 2. Load image based on type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($filePath);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($filePath);
                break;
            case IMAGETYPE_WEBP:
                $src = @imagecreatefromwebp($filePath);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($filePath);
                break;
            default:
                return false;
        }

        if (! $src) {
            return false;
        }

        // 3. Calculate new dimensions
        $maxDim = 800;
        $newWidth = $width;
        $newHeight = $height;

        if ($width > $maxDim || $height > $maxDim) {
            if ($width > $height) {
                $newWidth = $maxDim;
                $newHeight = (int) round(($height * $maxDim) / $width);
            } else {
                $newHeight = $maxDim;
                $newWidth = (int) round(($width * $maxDim) / $height);
            }
        }

        // 4. Create new truecolor image
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if (! $dst) {
            imagedestroy($src);

            return false;
        }

        // Preserve transparency for PNG and WebP
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        // Resize
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // 5. Save as WebP
        $disk = Storage::disk('public');

        ob_start();
        $saved = imagewebp($dst, null, 85);
        $webpData = ob_get_clean();

        // Free memory
        imagedestroy($src);
        imagedestroy($dst);

        if ($saved && $webpData !== false) {
            return $disk->put($targetPath, $webpData);
        }

        return false;
    }

    /**
     * Serve the uploaded QR logo.
     */
    public function serveLogo(string $filename): StreamedResponse|BinaryFileResponse
    {
        // Prevent directory traversal attacks
        $filename = basename($filename);
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.[a-zA-Z0-9]+$/', $filename)) {
            abort(400, 'Invalid filename');
        }

        $disk = Storage::disk('public');
        $path = 'short-urls/logos/'.$filename;

        if (! $disk->exists($path)) {
            $path = 'short-urls/tmp/'.$filename;
            if (! $disk->exists($path)) {
                abort(404);
            }
        }

        return $disk->response($path, null, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        ]);
    }
}
