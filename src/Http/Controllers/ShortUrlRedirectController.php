<?php

namespace Bjanczak\FilamentShortUrl\Http\Controllers;

use Bjanczak\FilamentShortUrl\Jobs\TrackShortUrlVisitJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ClientIpExtractor;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ShortUrlRedirectController extends Controller
{
    public function __construct(
        private readonly ShortUrlService $service,
    ) {}

    public function __invoke(Request $request, string $key): RedirectResponse
    {
        $shortUrl = ShortUrl::findByKey($key);

        // 404 if not found
        if (! $shortUrl) {
            abort(404);
        }

        // 410 Gone if disabled or expired
        if (! $shortUrl->isActive()) {
            abort(410);
        }

        if ($shortUrl->track_visits) {
            $connection = config('filament-short-url.queue_connection');
            $ipAddress = ClientIpExtractor::getIp($request);
            $countryCode = ClientIpExtractor::getCountryCode($request);

            $job = new TrackShortUrlVisitJob(
                shortUrl: $shortUrl,
                ipAddress: $ipAddress,
                userAgent: $request->userAgent() ?? '',
                refererUrl: $request->header('Referer'),
                countryCode: $countryCode,
            );

            if ($connection && $connection !== 'default') {
                dispatch($job->onConnection($connection));
            } else {
                dispatch($job);
            }
        }

        // Atomically disable single-use URLs — prevents race condition under concurrent load
        if ($shortUrl->single_use) {
            $affected = ShortUrl::where('id', $shortUrl->id)
                ->where('is_enabled', true)
                ->update(['is_enabled' => false]);

            // Another request beat us to it — this visit should 410
            if ($affected === 0) {
                abort(410);
            }
        }

        $redirectUrl = $this->service->resolveRedirectUrl($shortUrl, $request);

        return redirect()->away($redirectUrl, $shortUrl->redirect_status_code);
    }
}
