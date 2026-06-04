<?php

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Jobs\TrackShortUrlVisitJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
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

it('generates a deterministic privacy-safe GA4 client ID', function () {
    $job1 = new TrackShortUrlVisitJob(
        shortUrl: new ShortUrl(['id' => 1]),
        ipAddress: '192.168.1.1',
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'
    );

    $job2 = new TrackShortUrlVisitJob(
        shortUrl: new ShortUrl(['id' => 1]),
        ipAddress: '192.168.1.1',
        userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'
    );

    $job3 = new TrackShortUrlVisitJob(
        shortUrl: new ShortUrl(['id' => 1]),
        ipAddress: '8.8.8.8',
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)'
    );

    // Use reflection to access the private sendGa4Hit method or verify GA4 client ID determination
    $ref = new ReflectionClass(TrackShortUrlVisitJob::class);
    $method = $ref->getMethod('sendGa4Hit');
    $method->setAccessible(true);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/ga4',
        'url_key' => 'ga4test',
        'ga_tracking_id' => 'G-12345',
    ]);
    app(ShortUrlSettingsManager::class)->set([
        'ga4_api_secret' => 'secret_secret_123',
    ]);

    Http::fake();

    $visit = new ShortUrlVisit([
        'device_type' => 'desktop',
        'country' => 'Poland',
        'browser' => 'Chrome',
    ]);

    // Send first hit
    $method->invoke($job1, $shortUrl, $visit);
    // Send second hit (same IP/UA)
    $method->invoke($job2, $shortUrl, $visit);
    // Send third hit (different IP/UA)
    $method->invoke($job3, $shortUrl, $visit);

    $requests = Http::recorded();
    expect($requests)->toHaveCount(3);

    $body1 = json_decode($requests[0][0]->body(), true);
    $body2 = json_decode($requests[1][0]->body(), true);
    $body3 = json_decode($requests[2][0]->body(), true);

    // Client ID 1 and 2 must match exactly since they originate from the same user (deterministic hash)
    expect($body1['client_id'])->toBe($body2['client_id']);

    // Client ID 3 must be different
    expect($body1['client_id'])->not->toBe($body3['client_id']);
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

    expect($stats->device_stats)->toBe(['desktop' => 2, 'mobile' => 1])
        ->and($stats->browser_stats)->toBe(['Chrome' => 2, 'Safari' => 1])
        ->and($stats->os_stats)->toBe(['iOS' => 1, 'macOS' => 2])
        ->and($stats->country_stats)->toBe(['Poland' => 3])
        ->and($stats->city_stats)->toBe(['Krakow (PL)' => 1, 'Warsaw (PL)' => 2])
        ->and($stats->language_stats)->toBe(['en' => 1, 'pl' => 2]);
});
