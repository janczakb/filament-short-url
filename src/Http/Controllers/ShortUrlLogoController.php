<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShortUrlLogoController extends Controller
{
    /**
     * Upload QR logo file from admin panel.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        Log::info('ShortUrlLogoController::uploadLogo called', [
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
