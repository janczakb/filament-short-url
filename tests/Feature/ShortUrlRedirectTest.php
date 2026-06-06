<?php

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\ViewShortUrlStats;
use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Jobs\TrackShortUrlVisitJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

if (! function_exists('createShortUrl')) {
    function createShortUrl(array $attrs = []): ShortUrl
    {
        return app(ShortUrlService::class)->create(array_merge([
            'destination_url' => 'https://example.com',
        ], $attrs));
    }
}

it('redirects to destination url', function () {
    $shortUrl = createShortUrl(['url_key' => 'abc123', 'track_visits' => false]);

    $this->get('/s/abc123')
        ->assertRedirect('https://example.com');
});

it('returns 404 for unknown key', function () {
    $this->get('/s/doesnotexist')->assertStatus(404);
});

it('returns 410 for disabled url', function () {
    createShortUrl(['url_key' => 'disabled1', 'is_enabled' => false, 'track_visits' => false]);

    $this->get('/s/disabled1')->assertStatus(410);
});

it('returns 410 for expired url', function () {
    createShortUrl([
        'url_key' => 'expired1',
        'expires_at' => now()->subDay(),
        'track_visits' => false,
    ]);

    $this->get('/s/expired1')->assertStatus(410);
});

it('redirects to expiration fallback url when deactivated or expired', function () {
    createShortUrl([
        'url_key' => 'fallback-expired',
        'expires_at' => now()->subDay(),
        'expiration_redirect_url' => 'https://fallback.example.com',
        'track_visits' => false,
    ]);

    $this->get('/s/fallback-expired')
        ->assertRedirect('https://fallback.example.com');
});

it('redirects to expiration fallback url when deactivated_at is past', function () {
    createShortUrl([
        'url_key' => 'fallback-deactivated',
        'activated_at' => now()->subDays(2),
        'deactivated_at' => now()->subDay(),
        'expiration_redirect_url' => 'https://fallback.example.com',
        'track_visits' => false,
    ]);

    $this->get('/s/fallback-deactivated')
        ->assertRedirect('https://fallback.example.com');
});

it('redirects to expiration fallback url when activated_at is in future', function () {
    createShortUrl([
        'url_key' => 'fallback-not-yet-active',
        'activated_at' => now()->addDay(),
        'expiration_redirect_url' => 'https://fallback.example.com',
        'track_visits' => false,
    ]);

    $this->get('/s/fallback-not-yet-active')
        ->assertRedirect('https://fallback.example.com');
});

it('redirects to destination when activated_at is in past and deactivated_at is in future', function () {
    createShortUrl([
        'url_key' => 'active-range',
        'activated_at' => now()->subDay(),
        'deactivated_at' => now()->addDay(),
        'track_visits' => false,
    ]);

    $this->get('/s/active-range')
        ->assertRedirect('https://example.com');
});

it('redirects to expiration fallback when max visits is reached', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'fallback-max-visits',
        'activated_at' => now()->subDay(),
        'max_visits' => 2,
        'expiration_redirect_url' => 'https://fallback.example.com',
        'track_visits' => false,
    ]);

    $shortUrl->total_visits = 2;
    $shortUrl->save();

    $this->get('/s/fallback-max-visits')
        ->assertRedirect('https://fallback.example.com');
});

it('redirects to destination when max visits is not reached', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'under-max-visits',
        'max_visits' => 2,
        'expiration_redirect_url' => 'https://fallback.example.com',
        'track_visits' => false,
    ]);

    $shortUrl->total_visits = 1;
    $shortUrl->save();

    $this->get('/s/under-max-visits')
        ->assertRedirect('https://example.com');
});

it('disables single-use url after first visit', function () {
    createShortUrl(['url_key' => 'single1', 'single_use' => true, 'track_visits' => false]);

    $this->get('/s/single1')->assertRedirect();

    $shortUrl = ShortUrl::where('url_key', 'single1')->first();
    expect($shortUrl->is_enabled)->toBeFalse();
});

it('uses 302 redirect by default', function () {
    createShortUrl(['url_key' => 'temp302', 'redirect_status_code' => 302, 'track_visits' => false]);

    $this->get('/s/temp302')->assertStatus(302);
});

it('records visit data when tracking is enabled', function () {
    Queue::fake();

    createShortUrl(['url_key' => 'track1', 'track_visits' => true]);

    $this->get('/s/track1', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0']);

    Queue::assertPushed(
        TrackShortUrlVisitJob::class
    );
});

it('does not record visit when tracking is disabled', function () {
    Queue::fake();

    createShortUrl(['url_key' => 'notrack1', 'track_visits' => false]);

    $this->get('/s/notrack1');

    Queue::assertNotPushed(
        TrackShortUrlVisitJob::class
    );
});

it('extracts proxy-resistant client IP address', function () {
    config(['filament-short-url.queue_connection' => 'sync']);
    config(['filament-short-url.geo_ip.enabled' => false]);
    config(['filament-short-url.trust_cdn_headers' => true]);

    $shortUrl = createShortUrl(['url_key' => 'proxyip', 'track_visits' => true]);

    $this->get('/s/proxyip', [
        'CF-Connecting-IP' => '1.2.3.4',
        'X-Real-IP' => '5.6.7.8',
        'X-Forwarded-For' => '9.10.11.12, 13.14.15.16',
    ]);

    $visit = $shortUrl->visits()->first();
    expect($visit)->not->toBeNull()
        ->and($visit->ip_address)->toBe('1.2.3.4');

    $shortUrl2 = createShortUrl(['url_key' => 'proxyip2', 'track_visits' => true]);
    $this->get('/s/proxyip2', [
        'X-Forwarded-For' => '9.10.11.12, 13.14.15.16',
    ]);

    $visit2 = $shortUrl2->visits()->first();
    expect($visit2->ip_address)->toBe('9.10.11.12');
});

it('resolves country from edge CDN headers offline', function () {
    config(['filament-short-url.queue_connection' => 'sync']);
    config(['filament-short-url.geo_ip.enabled' => true]);
    config(['filament-short-url.geo_ip.driver' => 'headers']);
    config(['filament-short-url.trust_cdn_headers' => true]);

    $shortUrl = createShortUrl(['url_key' => 'cdngeo', 'track_visits' => true]);

    $this->get('/s/cdngeo', [
        'CF-IPCountry' => 'PL',
    ]);

    $visit = $shortUrl->visits()->first();
    expect($visit)->not->toBeNull()
        ->and($visit->country_code)->toBe('PL')
        ->and($visit->country)->toBe('Poland');
});

it('caches stats page calculations', function () {
    $shortUrl = createShortUrl(['url_key' => 'cachestats']);

    $page = new ViewShortUrlStats;
    $page->record = $shortUrl;

    // First mount: should be 0 since no visits exist
    $page->mount($shortUrl);
    expect($page->totalVisits)->toBe(0);

    // Create a visit in the database (cache is not cleared automatically for this manual action)
    $shortUrl->visits()->create([
        'ip_address' => '1.2.3.4',
        'visited_at' => now(),
    ]);

    // Second mount: should still be 0 because stats are cached
    $page->mount($shortUrl);
    expect($page->totalVisits)->toBe(0);

    // Clear the specific date-filtered cache key
    $dateFrom = now()->subDays(29)->format('Y-m-d');
    $dateTo = now()->format('Y-m-d');
    $shortUrl->clearStatsCache($dateFrom, $dateTo);

    // Third mount: should now be 1 because cache is cleared and recalculated
    $page->mount($shortUrl);
    expect($page->totalVisits)->toBe(1);
});

it('records visit data with UTM parameters and resolved referer host', function () {
    config(['filament-short-url.queue_connection' => 'sync']);
    config(['filament-short-url.geo_ip.enabled' => false]);

    $shortUrl = createShortUrl(['url_key' => 'utmtrack', 'track_visits' => true, 'track_referer_url' => true]);

    $this->get('/s/utmtrack?utm_source=google&utm_medium=cpc&utm_campaign=summer_sale&utm_term=shoes&utm_content=banner', [
        'Referer' => 'https://m.facebook.com/some/path?query=string',
    ]);

    $visit = $shortUrl->visits()->first();
    expect($visit)->not->toBeNull();
    expect($visit->utm_source)->toBe('google');
    expect($visit->utm_medium)->toBe('cpc');
    expect($visit->utm_campaign)->toBe('summer_sale');
    expect($visit->utm_term)->toBe('shoes');
    expect($visit->utm_content)->toBe('banner');
    expect($visit->referer_url)->toBe('https://m.facebook.com/some/path?query=string');
    expect($visit->referer_host)->toBe('facebook.com');
});

it('resolves city from edge CDN headers offline', function () {
    config(['filament-short-url.queue_connection' => 'sync']);
    config(['filament-short-url.geo_ip.enabled' => true]);
    config(['filament-short-url.geo_ip.driver' => 'headers']);
    config(['filament-short-url.trust_cdn_headers' => true]);

    $shortUrl = createShortUrl(['url_key' => 'cdncity', 'track_visits' => true]);

    $this->get('/s/cdncity', [
        'CF-IPCountry' => 'US',
        'CF-IPCity' => 'New York',
    ]);

    $visit = $shortUrl->visits()->first();
    expect($visit)->not->toBeNull()
        ->and($visit->country_code)->toBe('US')
        ->and($visit->country)->toBe('United States')
        ->and($visit->city)->toBe('New York');
});

it('resolves country and city from ip-api driver', function () {
    Http::fake([
        'http://ip-api.com/*' => Http::response([
            'status' => 'success',
            'country' => 'Germany',
            'countryCode' => 'DE',
            'city' => 'Berlin',
        ], 200),
    ]);

    config(['filament-short-url.geo_ip.enabled' => true]);
    config(['filament-short-url.geo_ip.driver' => 'ip-api']);

    $geoIpService = app(GeoIpService::class);
    $result = $geoIpService->resolve('8.8.8.8');

    expect($result)->toBe([
        'country' => 'Germany',
        'country_code' => 'DE',
        'city' => 'Berlin',
    ]);
});

it('applies rate limiting on redirects when enabled', function () {
    config(['filament-short-url.rate_limiting.enabled' => true]);
    config(['filament-short-url.rate_limiting.max_attempts' => 2]);
    config(['filament-short-url.rate_limiting.decay_seconds' => 10]);

    $shortUrl = createShortUrl(['url_key' => 'ratelimit1', 'track_visits' => false]);

    // Attempt 1: Success
    $this->get('/s/ratelimit1')->assertRedirect('https://example.com');

    // Attempt 2: Success
    $this->get('/s/ratelimit1')->assertRedirect('https://example.com');

    // Attempt 3: Rate limited (429)
    $this->get('/s/ratelimit1')->assertStatus(429);
});

it('requires password to redirect when protected', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'password123',
        'password' => 'secret-key',
        'track_visits' => false,
    ]);

    // Unauthenticated request should render password prompt view
    $response = $this->get('/s/password123');
    $response->assertStatus(200);
    $response->assertSee('Password Required');

    // Send incorrect password
    $response = $this->post('/s/password123', ['password' => 'wrong']);
    $response->assertStatus(200);
    $response->assertSee('Incorrect password');

    // Send correct password
    $response = $this->post('/s/password123', ['password' => 'secret-key']);
    $response->assertRedirect('/s/password123');

    // Following request should redirect to target
    $this->get('/s/password123')->assertRedirect('https://example.com');
});

it('applies rate limiting on password attempts when protected', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'password-ratelimit',
        'password' => 'secret-combination',
        'track_visits' => false,
    ]);

    // First 5 incorrect attempts should return 200 (renders password prompt again)
    for ($i = 0; $i < 5; $i++) {
        $this->post('/s/password-ratelimit', ['password' => 'wrong-pass'])
            ->assertStatus(200)
            ->assertSee('Incorrect password');
    }

    // 6th incorrect attempt should be rate limited (429)
    $this->post('/s/password-ratelimit', ['password' => 'wrong-pass'])
        ->assertStatus(429);
});

it('shows warning page before redirecting when enabled', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'warn1',
        'show_warning_page' => true,
        'track_visits' => false,
    ]);

    // Unconfirmed visit should render warning page
    $response = $this->get('/s/warn1');
    $response->assertStatus(200);
    $response->assertSee('Security Redirect Warning');
    $response->assertSee('https://example.com');

    // Confirmed visit should redirect
    $this->get('/s/warn1?confirmed=1')->assertRedirect('https://example.com');
});

it('requires password first, then shows warning page when both are enabled', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'both-secure',
        'password' => 'secret-combination',
        'show_warning_page' => true,
        'track_visits' => false,
    ]);

    // 1. Visit without password -> password prompt page, not warning page
    $response = $this->get('/s/both-secure');
    $response->assertStatus(200);
    $response->assertSee('Password Required');
    $response->assertDontSee('Security Redirect Warning');

    // 2. Submit wrong password -> password prompt page with error
    $response = $this->post('/s/both-secure', ['password' => 'wrong']);
    $response->assertStatus(200);
    $response->assertSee('Incorrect password');
    $response->assertDontSee('Security Redirect Warning');

    // 3. Submit correct password -> redirects back to the short URL path
    $response = $this->post('/s/both-secure', ['password' => 'secret-combination']);
    $response->assertRedirect('/s/both-secure');

    // 4. Follow redirect (GET request with authenticated session, but without confirmed parameter)
    // -> shows redirect warning page, not direct redirect
    $response = $this->get('/s/both-secure');
    $response->assertStatus(200);
    $response->assertSee('Security Redirect Warning');
    $response->assertSee('https://example.com');

    // 5. Follow the confirmed link (confirmed=1) -> redirects to destination
    $this->get('/s/both-secure?confirmed=1')
        ->assertRedirect('https://example.com');
});

it('targets redirect by device type', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'device-target',
        'track_visits' => false,
        'targeting_rules' => [
            'type' => 'device',
            'device' => [
                'ios' => 'https://ios.example.com',
                'android' => 'https://android.example.com',
                'desktop' => 'https://desktop.example.com',
            ],
        ],
    ]);

    // iOS
    $this->get('/s/device-target', ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'])
        ->assertRedirect('https://ios.example.com');

    // Android
    $this->get('/s/device-target', ['User-Agent' => 'Mozilla/5.0 (Linux; Android 10)'])
        ->assertRedirect('https://android.example.com');

    // Desktop
    $this->get('/s/device-target', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
        ->assertRedirect('https://desktop.example.com');
});

it('targets redirect by country code', function () {
    config(['filament-short-url.trust_cdn_headers' => true]);

    $shortUrl = createShortUrl([
        'url_key' => 'geo-target',
        'track_visits' => false,
        'targeting_rules' => [
            'type' => 'geo',
            'geo' => [
                ['country_code' => 'PL', 'url' => 'https://pl.example.com'],
                ['country_code' => 'US', 'url' => 'https://us.example.com'],
            ],
        ],
    ]);

    // PL
    $this->get('/s/geo-target', ['CF-IPCountry' => 'PL'])
        ->assertRedirect('https://pl.example.com');

    // US
    $this->get('/s/geo-target', ['CF-IPCountry' => 'US'])
        ->assertRedirect('https://us.example.com');

    // Other (fallback)
    $this->get('/s/geo-target', ['CF-IPCountry' => 'DE'])
        ->assertRedirect('https://example.com');
});

it('targets redirect by browser language', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'lang-target',
        'track_visits' => false,
        'targeting_rules' => [
            'type' => 'language',
            'language' => [
                ['language_code' => 'PL', 'url' => 'https://pl.example.com'],
                ['language_code' => 'en-US', 'url' => 'https://enus.example.com'],
                ['language_code' => 'de', 'url' => 'https://de.example.com'],
            ],
        ],
    ]);

    // PL (matching base pl)
    $this->get('/s/lang-target', ['Accept-Language' => 'pl-PL,pl;q=0.9'])
        ->assertRedirect('https://pl.example.com');

    // en-US (matching exact locale en-US)
    $this->get('/s/lang-target', ['Accept-Language' => 'en-US,en;q=0.8'])
        ->assertRedirect('https://enus.example.com');

    // de (matching base de from de-DE)
    $this->get('/s/lang-target', ['Accept-Language' => 'de-DE,de;q=0.8'])
        ->assertRedirect('https://de.example.com');

    // Other (fallback)
    $this->get('/s/lang-target', ['Accept-Language' => 'fr-FR,fr;q=0.9'])
        ->assertRedirect('https://example.com');
});

it('targets redirect by rotation rules', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'rotation-target',
        'track_visits' => false,
        'targeting_rules' => [
            'type' => 'rotation',
            'rotation' => [
                ['url' => 'https://a.example.com', 'weight' => 100],
            ],
        ],
    ]);

    $this->get('/s/rotation-target')->assertRedirect('https://a.example.com');
});

it('aggregates old visits and prunes logs', function () {
    config(['filament-short-url.pruning.enabled' => true]);
    config(['filament-short-url.pruning.retention_days' => 7]);

    $shortUrl = createShortUrl(['url_key' => 'agg1']);

    // Create raw visit from yesterday
    $visitYesterday = $shortUrl->visits()->create([
        'ip_address' => '1.1.1.1',
        'ip_hash' => hash('sha256', '1.1.1.1'),
        'visited_at' => now()->subDay(),
        'device_type' => 'desktop',
        'browser' => 'Chrome',
        'operating_system' => 'Windows',
        'country_code' => 'PL',
        'country' => 'Poland',
    ]);

    // Create raw visit older than retention window (8 days ago)
    $visitOld = $shortUrl->visits()->create([
        'ip_address' => '2.2.2.2',
        'ip_hash' => hash('sha256', '2.2.2.2'),
        'visited_at' => now()->subDays(8),
        'device_type' => 'mobile',
        'browser' => 'Safari',
        'operating_system' => 'iOS',
        'country_code' => 'US',
        'country' => 'United States',
    ]);

    // Run aggregation command
    $this->artisan('short-url:aggregate-and-prune')->assertSuccessful();

    // Verify stats were aggregated for yesterday (Eloquent-based to handle date cast differences across DBs)
    $statsYesterday = ShortUrlDailyStats::where('short_url_id', $shortUrl->id)
        ->whereDate('date', now()->subDay()->toDateString())
        ->first();

    expect($statsYesterday)->not->toBeNull();
    expect($statsYesterday->visits_count)->toBe(1);
    expect($statsYesterday->unique_visits_count)->toBe(1);

    // Verify stats were aggregated for 8 days ago
    $statsOld = ShortUrlDailyStats::where('short_url_id', $shortUrl->id)
        ->whereDate('date', now()->subDays(8)->toDateString())
        ->first();

    expect($statsOld)->not->toBeNull();
    expect($statsOld->visits_count)->toBe(1);
    expect($statsOld->unique_visits_count)->toBe(1);

    // Check pruning: visit from 8 days ago should be deleted, yesterday should remain
    $this->assertDatabaseMissing('short_url_visits', ['id' => $visitOld->id]);
    $this->assertDatabaseHas('short_url_visits', ['id' => $visitYesterday->id]);
});

it('tracks QR code scan visits via query parameters', function () {
    config(['filament-short-url.queue_connection' => 'sync']);

    $shortUrl = createShortUrl(['url_key' => 'qrtest1', 'track_visits' => true]);

    // Test ?source=qr
    $this->get('/s/qrtest1?source=qr');
    $visit1 = $shortUrl->visits()->latest('id')->first();
    expect($visit1)->not->toBeNull()
        ->and($visit1->is_qr_scan)->toBeTrue();

    // Test ?qr=1
    $this->get('/s/qrtest1?qr=1');
    $visit2 = $shortUrl->visits()->latest('id')->first();
    expect($visit2)->not->toBeNull()
        ->and($visit2->is_qr_scan)->toBeTrue();

    // Test direct visit
    $this->get('/s/qrtest1');
    $visit3 = $shortUrl->visits()->latest('id')->first();
    expect($visit3)->not->toBeNull()
        ->and($visit3->is_qr_scan)->toBeFalse();

    $shortUrl->refresh();
    expect($shortUrl->qr_scans)->toBe(2);
});

it('extracts and tracks visitor browser language from headers', function () {
    config(['filament-short-url.queue_connection' => 'sync']);

    $shortUrl = createShortUrl(['url_key' => 'langtest1', 'track_visits' => true]);

    $this->get('/s/langtest1', [
        'Accept-Language' => 'pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
    ]);

    $visit = $shortUrl->visits()->first();
    expect($visit)->not->toBeNull()
        ->and($visit->browser_language)->toBe('pl');
});

it('does not track browser language if track_browser_language is disabled', function () {
    config(['filament-short-url.queue_connection' => 'sync']);

    $shortUrl = createShortUrl([
        'url_key' => 'langtest2',
        'track_visits' => true,
        'track_browser_language' => false,
    ]);

    $this->get('/s/langtest2', [
        'Accept-Language' => 'pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7',
    ]);

    $visit = $shortUrl->visits()->first();
    expect($visit)->not->toBeNull()
        ->and($visit->browser_language)->toBeNull();
});

it('tracks browser language if track_browser_language is enabled', function () {
    config(['filament-short-url.queue_connection' => 'sync']);

    $shortUrl = createShortUrl([
        'url_key' => 'langtest3',
        'track_visits' => true,
        'track_browser_language' => true,
    ]);

    $this->get('/s/langtest3', [
        'Accept-Language' => 'fr-FR,fr;q=0.9',
    ]);

    $visit = $shortUrl->visits()->first();
    expect($visit)->not->toBeNull()
        ->and($visit->browser_language)->toBe('fr');
});

it('enforces max_visits in real-time even when model caching is active', function () {
    config([
        'filament-short-url.cache_ttl' => 3600,
        'filament-short-url.queue_connection' => 'sync',
    ]);

    $shortUrl = createShortUrl([
        'url_key' => 'max-visits-cache',
        'max_visits' => 2,
        'track_visits' => true,
    ]);

    // First visit: gets cached, redirects
    $this->get('/s/max-visits-cache')->assertRedirect('https://example.com');

    // Second visit: loaded from cache, redirects
    $this->get('/s/max-visits-cache')->assertRedirect('https://example.com');

    // Third visit: should detect limit reached and return 410
    $this->get('/s/max-visits-cache')->assertStatus(410);
});

it('enforces single_use in real-time even when model caching is active', function () {
    config([
        'filament-short-url.cache_ttl' => 3600,
        'filament-short-url.queue_connection' => 'sync',
    ]);

    $shortUrl = createShortUrl([
        'url_key' => 'single-use-cache',
        'single_use' => true,
        'track_visits' => true,
    ]);

    // First visit: redirects and disables the URL
    $this->get('/s/single-use-cache')->assertRedirect('https://example.com');

    // Second visit: should return 410, even if the model exists in the cache as enabled
    $this->get('/s/single-use-cache')->assertStatus(410);
});

it('renders custom branded expired view on deactivated URL', function () {
    createShortUrl(['url_key' => 'disabled-view', 'is_enabled' => false, 'track_visits' => false]);

    $response = $this->get('/s/disabled-view');
    $response->assertStatus(410);
    $response->assertSee('Link Inactive or Expired');
    $response->assertSee('Go to Homepage');
});

it('evaluates new multi-filter targeting rules with OR match logic', function () {
    config(['filament-short-url.trust_cdn_headers' => true]);

    createShortUrl([
        'url_key' => 'or-multi',
        'track_visits' => false,
        'targeting_rules' => [
            [
                'match' => 'or',
                'url' => 'https://matched-or.example.com',
                'filters' => [
                    [
                        'type' => 'country',
                        'data' => ['countries' => ['PL', 'DE']],
                    ],
                    [
                        'type' => 'device',
                        'data' => ['devices' => ['mobile']],
                    ],
                ],
            ],
        ],
    ]);

    // Matches Country (PL) -> redirects
    $this->get('/s/or-multi', ['CF-IPCountry' => 'PL'])
        ->assertRedirect('https://matched-or.example.com');

    // Matches Device (mobile) but different Country -> redirects
    $this->get('/s/or-multi', [
        'CF-IPCountry' => 'US',
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
    ])->assertRedirect('https://matched-or.example.com');

    // Matches neither -> falls back to default destination_url
    $this->get('/s/or-multi', [
        'CF-IPCountry' => 'US',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ])->assertRedirect('https://example.com');
});

it('evaluates new multi-filter targeting rules with AND match logic', function () {
    config(['filament-short-url.trust_cdn_headers' => true]);

    createShortUrl([
        'url_key' => 'and-multi',
        'track_visits' => false,
        'targeting_rules' => [
            [
                'match' => 'and',
                'url' => 'https://matched-and.example.com',
                'filters' => [
                    [
                        'type' => 'country',
                        'data' => ['countries' => ['PL']],
                    ],
                    [
                        'type' => 'device',
                        'data' => ['devices' => ['mobile']],
                    ],
                ],
            ],
        ],
    ]);

    // Matches both -> redirects
    $this->get('/s/and-multi', [
        'CF-IPCountry' => 'PL',
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
    ])->assertRedirect('https://matched-and.example.com');

    // Matches only Country -> falls back
    $this->get('/s/and-multi', [
        'CF-IPCountry' => 'PL',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ])->assertRedirect('https://example.com');

    // Matches only Device -> falls back
    $this->get('/s/and-multi', [
        'CF-IPCountry' => 'US',
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
    ])->assertRedirect('https://example.com');
});

it('evaluates platform and browser language filters in new rule engine', function () {
    createShortUrl([
        'url_key' => 'platform-lang',
        'track_visits' => false,
        'targeting_rules' => [
            [
                'match' => 'and',
                'url' => 'https://matched-platform-lang.example.com',
                'filters' => [
                    [
                        'type' => 'platform',
                        'data' => ['platforms' => ['ios', 'android']],
                    ],
                    [
                        'type' => 'language',
                        'data' => ['languages' => ['pl', 'de']],
                    ],
                ],
            ],
        ],
    ]);

    // iOS + pl -> redirects
    $this->get('/s/platform-lang', [
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        'Accept-Language' => 'pl-PL,pl;q=0.9',
    ])->assertRedirect('https://matched-platform-lang.example.com');

    // Android + de -> redirects
    $this->get('/s/platform-lang', [
        'User-Agent' => 'Mozilla/5.0 (Linux; Android 13)',
        'Accept-Language' => 'de-DE,de;q=0.9',
    ])->assertRedirect('https://matched-platform-lang.example.com');

    // iOS + en -> falls back
    $this->get('/s/platform-lang', [
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        'Accept-Language' => 'en-US,en;q=0.9',
    ])->assertRedirect('https://example.com');

    // Windows + pl -> falls back
    $this->get('/s/platform-lang', [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'Accept-Language' => 'pl-PL,pl;q=0.9',
    ])->assertRedirect('https://example.com');
});

it('dispatches limit_reached webhook when link reaches its click limit', function () {
    Queue::fake([SendWebhookJob::class]);
    cache()->forget('filament-short-url:settings');

    $shortUrl = createShortUrl([
        'url_key' => 'limit-reach-webhook',
        'max_visits' => 1,
        'track_visits' => true,
        'webhook_url' => 'https://webhook.site/limit',
    ]);

    // Force queue connection to sync for the job so it runs inline
    config(['filament-short-url.queue_connection' => 'sync']);

    // Perform visit (will trigger TrackShortUrlVisitJob which will increment total_visits to 1 >= max_visits)
    $this->get('/s/limit-reach-webhook');

    Queue::assertPushed(SendWebhookJob::class, function ($job) {
        return $job->url === 'https://webhook.site/limit' && $job->event === 'limit_reached';
    });
});

it('dispatches expired webhook when user attempts to visit an expired link', function () {
    Queue::fake([SendWebhookJob::class]);
    cache()->forget('filament-short-url:settings');

    $shortUrl = createShortUrl([
        'url_key' => 'expired-webhook-test',
        'expires_at' => now()->subDay(),
        'track_visits' => true,
        'webhook_url' => 'https://webhook.site/expired',
    ]);

    // Perform visit to expired link
    $this->get('/s/expired-webhook-test');

    Queue::assertPushed(SendWebhookJob::class, function ($job) {
        return $job->url === 'https://webhook.site/expired' && $job->event === 'expired';
    });
});

it('invalidates redirect cache keys when the url_key is updated', function () {
    $shortUrl = createShortUrl([
        'url_key' => 'old-key',
        'track_visits' => false,
    ]);

    cache()->remember('filament-short-url:old-key:default', 3600, fn () => $shortUrl);
    expect(cache()->has('filament-short-url:old-key:default'))->toBeTrue();

    // Now update url_key
    $shortUrl->update(['url_key' => 'new-key']);

    // Cache for both keys should be cleared
    expect(cache()->has('filament-short-url:old-key:default'))->toBeFalse();
    expect(cache()->has('filament-short-url:new-key:default'))->toBeFalse();
});
