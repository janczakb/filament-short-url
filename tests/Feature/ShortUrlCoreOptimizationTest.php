<?php

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\Ga4MeasurementProtocolService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Clean up any test configurations
    cache()->forget('filament-short-url:settings');
    app(ShortUrlSettingsManager::class)->set(['route_prefix' => 's']);
});

it('migrates settings to database and caches them', function () {
    // 1. Verify schema has table
    expect(Schema::hasTable('short_url_settings'))->toBeTrue();

    // 2. Write setting and verify it exists in db
    $mgr = app(ShortUrlSettingsManager::class);
    $mgr->set(['route_prefix' => 'custom-prefix']);

    $this->assertDatabaseHas('short_url_settings', [
        'key' => 'route_prefix',
        'value' => json_encode('custom-prefix'),
    ]);

    // 3. Clear cache manually and verify it still reads correctly
    cache()->forget('filament-short-url:settings');
    expect($mgr->get('route_prefix'))->toBe('custom-prefix');
});

it('forces 302 redirect status code for limited/expiring links', function () {
    // Case A: Link with expires_at
    $url1 = ShortUrl::create([
        'destination_url' => 'https://example.com/expiring',
        'url_key' => 'expiringlink',
        'expires_at' => now()->addDays(5),
        'redirect_status_code' => 301, // User requests 301
    ]);
    expect($url1->redirect_status_code)->toBe(302); // Enforced to 302

    // Case B: Link with max_visits
    $url2 = ShortUrl::create([
        'destination_url' => 'https://example.com/limited',
        'url_key' => 'limitedlink',
        'max_visits' => 100,
        'redirect_status_code' => 301,
    ]);
    expect($url2->redirect_status_code)->toBe(302); // Enforced to 302
});

it('generates a deterministic privacy-safe GA4 client ID and enterprise MP payload', function () {
    $ga4 = app(Ga4MeasurementProtocolService::class);

    $clientId1 = $ga4->buildClientId('192.168.1.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');
    $clientId2 = $ga4->buildClientId('192.168.1.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');
    $clientId3 = $ga4->buildClientId('8.8.8.8', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)');

    expect($clientId1)->toBe($clientId2)
        ->and($clientId1)->not->toBe($clientId3);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/ga4',
        'url_key' => 'ga4test',
        'ga_tracking_id' => 'G-TEST12345',
    ]);
    app(ShortUrlSettingsManager::class)->set([
        'ga4_api_secret' => 'secret_secret_123',
    ]);

    Http::fake();

    $visit = new ShortUrlVisit([
        'device_type' => 'desktop',
        'country' => 'Poland',
        'country_code' => 'PL',
        'browser' => 'Chrome',
        'referer_url' => 'https://google.com',
        'visited_at' => now(),
    ]);

    $ga4->send($shortUrl, $visit, '192.168.1.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);
        $events = collect($body['events'] ?? [])->pluck('name')->all();

        return str_contains($request->url(), 'measurement_id=G-TEST12345')
            && isset($body['timestamp_micros'])
            && in_array('page_view', $events, true)
            && in_array('click', $events, true)
            && in_array('short_url_visit', $events, true)
            && ($body['events'][0]['params']['session_id'] ?? null) !== null
            && ($body['events'][0]['params']['engagement_time_msec'] ?? 0) >= 100;
    });
});

it('signs outgoing webhooks with X-ShortUrl-Signature when secret is configured', function () {
    app(ShortUrlSettingsManager::class)->set([
        'webhook_signing_secret' => 'my-signing-secret-key-123',
    ]);

    Http::fake();

    $job = new SendWebhookJob(
        url: 'https://webhook.site/my-endpoint',
        event: 'visited',
        payload: ['click_id' => 99, 'short_url_id' => 1]
    );

    $job->handle();

    Http::assertSent(function ($request) {
        expect($request->hasHeader('X-ShortUrl-Signature'))->toBeTrue();

        $secret = 'my-signing-secret-key-123';
        $payloadJson = json_encode(['click_id' => 99, 'short_url_id' => 1], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expectedSignature = hash_hmac('sha256', $payloadJson, $secret);

        expect($request->header('X-ShortUrl-Signature')[0])->toBe($expectedSignature);

        return true;
    });
});

it('runs database-level daily stats aggregation successfully', function () {
    // 1. Seed raw visits for a previous day
    $yesterday = Carbon::yesterday()->toDateString();

    $link = ShortUrl::create([
        'destination_url' => 'https://example.com/aggregate',
        'url_key' => 'aggkey',
    ]);

    DB::table('short_url_visits')->insert([
        [
            'short_url_id' => $link->id,
            'ip_hash' => hash('sha256', '1.1.1.1'),
            'browser' => 'Chrome',
            'operating_system' => 'macOS',
            'device_type' => 'desktop',
            'country' => 'Poland',
            'country_code' => 'PL',
            'city' => 'Warsaw',
            'referer_host' => 'google.com',
            'visited_at' => $yesterday.' 10:00:00',
            'is_qr_scan' => false,
            'browser_language' => 'pl',
        ],
        [
            'short_url_id' => $link->id,
            'ip_hash' => hash('sha256', '1.1.1.1'), // same user
            'browser' => 'Chrome',
            'operating_system' => 'macOS',
            'device_type' => 'desktop',
            'country' => 'Poland',
            'country_code' => 'PL',
            'city' => 'Warsaw',
            'referer_host' => 'google.com',
            'visited_at' => $yesterday.' 11:00:00',
            'is_qr_scan' => false,
            'browser_language' => 'pl',
        ],
        [
            'short_url_id' => $link->id,
            'ip_hash' => hash('sha256', '2.2.2.2'), // unique user, QR scan
            'browser' => 'Safari',
            'operating_system' => 'iOS',
            'device_type' => 'mobile',
            'country' => 'Poland',
            'country_code' => 'PL',
            'city' => 'Krakow',
            'referer_host' => 'facebook.com',
            'visited_at' => $yesterday.' 12:00:00',
            'is_qr_scan' => true,
            'browser_language' => 'en',
        ],
    ]);

    // Run the aggregation command
    $this->artisan('short-url:aggregate-and-prune')
        ->assertExitCode(0);

    // Verify statistics entry in database
    $this->assertDatabaseHas('short_url_daily_stats', [
        'short_url_id' => $link->id,
        'date' => $yesterday.' 00:00:00',
        'visits_count' => 3,
        'unique_visits_count' => 2,
        'qr_visits_count' => 1,
    ]);

    $stats = ShortUrlDailyStats::where('short_url_id', $link->id)
        ->where('date', $yesterday.' 00:00:00')
        ->first();

    expect($stats->device_stats)->toMatchArray(['desktop' => 2, 'mobile' => 1])
        ->and($stats->browser_stats)->toMatchArray(['Chrome' => 2, 'Safari' => 1])
        ->and($stats->os_stats)->toMatchArray(['iOS' => 1, 'macOS' => 2])
        ->and($stats->country_stats)->toMatchArray(['PL' => 3])
        ->and($stats->city_stats)->toMatchArray(['Krakow (PL)' => 1, 'Warsaw (PL)' => 2])
        ->and($stats->language_stats)->toMatchArray(['en' => 1, 'pl' => 2]);
});

it('anonymizes IP address correctly for IPv4 and IPv6', function () {
    $ipv4 = '192.168.1.123';
    $ipv6 = '2001:db8:85a3:8d3:1319:8a2e:370:7334';

    expect(ShortUrlTracker::anonymizeIp($ipv4))->toBe('192.168.1.0')
        ->and(ShortUrlTracker::anonymizeIp($ipv6))->toBe('2001:db8:85a3::');
});

it('anonymizes saved IP address when anonymize_ips is enabled but computes hash on raw IP', function () {
    $mgr = app(ShortUrlSettingsManager::class);
    $mgr->set(['tracking_anonymize_ips' => true]);

    $link = ShortUrl::create([
        'destination_url' => 'https://example.com/anonymize',
        'url_key' => 'anonkey',
        'track_ip_address' => true,
    ]);

    $request = Request::create('/anonkey', 'GET', [], [], [], [
        'REMOTE_ADDR' => '1.2.3.4',
    ]);

    $tracker = app(ShortUrlTracker::class);
    $visit = $tracker->record($link, $request);

    expect($visit->ip_address)->toBe('1.2.3.0')
        // Hash must now use HMAC-SHA256 keyed with app.key \u2014 NOT plain SHA-256
        ->and($visit->ip_hash)->toBe(hash_hmac('sha256', '1.2.3.4', config('app.key', '')));
});
