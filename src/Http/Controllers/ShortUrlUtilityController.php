<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Services\IframeableChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ShortUrlUtilityController extends Controller
{
    public function __construct(
        private readonly IframeableChecker $iframeableChecker,
    ) {}

    /**
     * Pre-check whether a destination URL can be embedded in a cloaking iframe.
     */
    public function checkIframeable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $url = $validated['url'];
        $iframeable = $this->iframeableChecker->isIframeable($url);

        return response()->json([
            'url' => $url,
            'iframeable' => $iframeable,
        ]);
    }
}
