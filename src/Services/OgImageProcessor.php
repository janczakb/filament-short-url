<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OgImageProcessor
{
    public function __construct(
        private readonly ShortUrlTempStorage $tempStorage,
    ) {}

    /**
     * Convert a local image file to WebP and store it on the given disk.
     */
    public function storeWebpFromPath(
        string $sourcePath,
        string $directory = ShortUrlTempStorage::OG_PERMANENT,
        string $disk = 'public',
    ): ?string {
        if (! is_file($sourcePath)) {
            return null;
        }

        $directory = $this->tempStorage->resolveDirectory($directory);
        $filename = Str::uuid().'.webp';
        $tempWebpPath = tempnam(sys_get_temp_dir(), 'og').'.webp';
        $success = false;

        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick($sourcePath);
                $imagick->setImageBackgroundColor('white');
                $imagick->flattenImages();
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality(85);
                $imagick->writeImage($tempWebpPath);
                $imagick->clear();
                $imagick->destroy();
                $success = true;
            } catch (\Exception) {
                // Fall back to GD.
            }
        }

        if (! $success && function_exists('imagecreatefromstring')) {
            try {
                $imgData = file_get_contents($sourcePath);

                if ($imgData !== false) {
                    $im = imagecreatefromstring($imgData);

                    if ($im !== false) {
                        $width = imagesx($im);
                        $height = imagesy($im);
                        $canvas = imagecreatetruecolor($width, $height);
                        $white = imagecolorallocate($canvas, 255, 255, 255);
                        imagefill($canvas, 0, 0, $white);
                        imagecopy($canvas, $im, 0, 0, 0, 0, $width, $height);
                        imagedestroy($im);
                        imagewebp($canvas, $tempWebpPath, 85);
                        imagedestroy($canvas);
                        $success = true;
                    }
                }
            } catch (\Exception) {
                // Fall back to storing the original file.
            }
        }

        if ($success && file_exists($tempWebpPath)) {
            Storage::disk($disk)->makeDirectory($directory);
            $storedPath = Storage::disk($disk)->putFileAs(
                $directory,
                new File($tempWebpPath),
                $filename
            );
            @unlink($tempWebpPath);

            return $storedPath;
        }

        Storage::disk($disk)->makeDirectory($directory);
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $storedPath = Storage::disk($disk)->putFileAs(
            $directory,
            new File($sourcePath),
            Str::uuid().'.'.$extension
        );

        return $storedPath ?: null;
    }
}
