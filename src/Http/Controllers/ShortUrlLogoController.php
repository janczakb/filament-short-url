<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShortUrlLogoController extends Controller
{
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
