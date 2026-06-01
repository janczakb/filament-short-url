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
            $connection = config('filament-short-url.queue_connection', 'sync');
            $ipAddress = ClientIpExtractor::getIp($request);
            $countryCode = ClientIpExtractor::getCountryCode($request);
            $city = ClientIpExtractor::getCity($request);

            $job = new TrackShortUrlVisitJob(
                shortUrl: $shortUrl,
                ipAddress: $ipAddress,
                userAgent: $request->userAgent() ?? '',
                refererUrl: $request->header('Referer'),
                countryCode: $countryCode,
                city: $city,
                utmSource: $request->query('utm_source'),
                utmMedium: $request->query('utm_medium'),
                utmCampaign: $request->query('utm_campaign'),
                utmTerm: $request->query('utm_term'),
                utmContent: $request->query('utm_content'),
            );

            if ($connection) {
                dispatch($job->onConnection($connection));
            } else {
                dispatch($job->onConnection('sync'));
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

            // Manually forget cache since DB-level update does not trigger Eloquent events
            cache()->forget("filament-short-url:{$shortUrl->url_key}");
        }

        $redirectUrl = $this->service->resolveRedirectUrl($shortUrl, $request);

        return redirect()->away($redirectUrl, $shortUrl->redirect_status_code);
    }
}
