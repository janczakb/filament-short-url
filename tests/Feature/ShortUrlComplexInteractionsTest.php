<?php

/**
 * Complex option interactions and conflict scenarios tests.
 *
 * Verifies that rate limiting, password protection, targeting, single-use,
 * counter buffering, warning pages, and custom domains operate together
 * coherently and performantly without logical conflicts or edge-case bypasses.
 */

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Models\ShortUrlDailyStats;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

// ─── Helpers ──────────────────────────────────────────────────────────────────

if (! function_exists('makeLink')) {
    function makeLink(array $attrs = []): ShortUrl
    {
        return app(ShortUrlService::class)->create(array_merge([
            'destination_url' => 'https://example.com',
            'track_visits' => false,
        ], $attrs));
    }
}

if (! function_exists('mgr')) {
    function mgr(): ShortUrlSettingsManager
    {
        return app(ShortUrlSettingsManager::class);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// TESTS
// ═══════════════════════════════════════════════════════════════════════════

describe('complex option interactions', function () {

    beforeEach(function () {
        cache()->forget('filament-short-url:settings');
        app()->forgetInstance(ShortUrlSettingsManager::class);
        RateLimiter::clear('short_url_limit:pw-rl-combo:127.0.0.1');
        RateLimiter::clear('short_url_password_limit:pw-rl-combo:127.0.0.1');
        RateLimiter::clear('short_url_limit:pw-brute:127.0.0.1');
        RateLimiter::clear('short_url_password_limit:pw-brute:127.0.0.1');
    });

    it('global rate limiting hits before the password check on the redirect path', function () {
        config([
            'filament-short-url.rate_limiting.enabled' => true,
            'filament-short-url.rate_limiting.max_attempts' => 2,
            'filament-short-url.rate_limiting.decay_seconds' => 60,
        ]);

        makeLink([
            'url_key' => 'pw-rl-combo',
            'password' => 'secret123',
        ]);

        // First attempt (below rate limit) -> Shows password page (200)
        $this->get('/s/pw-rl-combo')->assertStatus(200)->assertSee('Password Required');

        // Second attempt (at rate limit) -> Shows password page (200)
        $this->get('/s/pw-rl-combo')->assertStatus(200);

        // Third attempt (exceeds rate limit) -> Aborts with 429
        $this->get('/s/pw-rl-combo')->assertStatus(429);

        // Submitting POST should also be blocked by the rate limiter
        $this->post('/s/pw-rl-combo', ['password' => 'secret123'])->assertStatus(429);
    });

    it('password rate limiting is independent and tracks wrong attempts only', function () {
        config([
            'filament-short-url.rate_limiting.enabled' => false,
        ]);

        makeLink([
            'url_key' => 'pw-brute',
            'password' => 'open-sesame',
        ]);

        // Submit 5 wrong attempts -> 200 with incorrect password
        for ($i = 0; $i < 5; $i++) {
            $this->post('/s/pw-brute', ['password' => 'wrong-password'])
                ->assertStatus(200)
                ->assertSee('Incorrect password');
        }

        // 6th attempt (even correct password) -> 429 due to brute force protection
        $this->post('/s/pw-brute', ['password' => 'open-sesame'])
            ->assertStatus(429);

        // Check that a different IP can still log in successfully immediately
        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
            ->post('/s/pw-brute', ['password' => 'open-sesame'])
            ->assertRedirect('/s/pw-brute');
    });

    it('targeting rules apply after password protection but before warning page', function () {
        config([
            'filament-short-url.rate_limiting.enabled' => false,
        ]);

        $link = makeLink([
            'url_key' => 'tgt-pw-warn',
            'destination_url' => 'https://default.com',
            'password' => 'super-secret',
            'show_warning_page' => true,
            'targeting_rules' => [[
                'match' => 'or',
                'url' => 'https://mobile.com',
                'filters' => [['type' => 'device', 'data' => ['devices' => ['mobile']]]],
            ]],
        ]);

        // 1. Unauthenticated request as mobile: password prompt must show first, not warning, not redirect
        $response = $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get('/s/tgt-pw-warn');

        $response->assertStatus(200)
            ->assertSee('Password Required')
            ->assertDontSee('Security Redirect Warning')
            ->assertDontSee('https://mobile.com');

        // 2. Submit correct password
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->post('/s/tgt-pw-warn', ['password' => 'super-secret'])
            ->assertRedirect('/s/tgt-pw-warn');

        // 3. Authenticated request as mobile: targeting resolved to mobile.com, warning page shown
        $response2 = $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get('/s/tgt-pw-warn');

        $response2->assertStatus(200)
            ->assertSee('Security Redirect Warning')
            ->assertSee('https://mobile.com')
            ->assertDontSee('https://default.com');

        // 4. Confirm redirection: goes to mobile URL
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get('/s/tgt-pw-warn?confirmed=1')
            ->assertRedirect('https://mobile.com');
    });

    it('counter buffering does not cause data loss during visit aggregation and pruning', function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
            'filament-short-url.counter_buffering.enabled' => true,
            'filament-short-url.pruning.enabled' => true,
            'filament-short-url.pruning.retention_days' => 5,
        ]);

        $link = makeLink([
            'url_key' => 'buf-prune',
            'track_visits' => true,
        ]);

        // Record a real historical visit in database (older than retention limit, e.g. 10 days ago)
        $oldVisit = $link->visits()->create([
            'visited_at' => now()->subDays(10),
            'ip_hash' => 'old-hash',
        ]);

        // Record a recent visit in database (within retention window, e.g. 2 days ago)
        $recentVisit = $link->visits()->create([
            'visited_at' => now()->subDays(2),
            'ip_hash' => 'recent-hash',
        ]);

        // Simulate new visits buffered in cache (current day)
        $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
        cache()->put("{$prefix}total:{$link->id}", 5);
        cache()->put("{$prefix}unique:{$link->id}", 4);
        cache()->forever("{$prefix}dirty_ids", [$link->id]);

        // Run Aggregate and Prune command
        $this->artisan('short-url:aggregate-and-prune')->assertExitCode(0);

        // Verification:
        // 1. The old visit should be pruned from database
        $this->assertDatabaseMissing('short_url_visits', ['id' => $oldVisit->id]);

        // 2. The recent visit should remain in database
        $this->assertDatabaseHas('short_url_visits', ['id' => $recentVisit->id]);

        // 3. The historical visits before today (recentVisit at 2 days ago) should be aggregated into daily stats
        $dailyStat = ShortUrlDailyStats::where('short_url_id', $link->id)->first();
        expect($dailyStat)->not->toBeNull()
            ->and($dailyStat->visits_count)->toBe(1)
            ->and($dailyStat->unique_visits_count)->toBe(1);

        // 4. Cache buffer for today's visits should still exist intact (aggregation only processes visits prior to today)
        expect((int) cache()->get("{$prefix}total:{$link->id}"))->toBe(5)
            ->and((int) cache()->get("{$prefix}unique:{$link->id}"))->toBe(4);

        // 5. Sync the counters
        $this->artisan('short-url:sync-counters')->assertExitCode(0);

        // 6. DB totals should now be updated with buffered values plus previously stored values
        $link->refresh();
        expect($link->total_visits)->toBe(5)
            ->and($link->unique_visits)->toBe(4);
    });

    it('split destination variants are resolved and UTM parameters forwarded correctly', function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
        ]);

        // Setup a short URL with targeting rules pointing to either single or split destinations
        $link = makeLink([
            'url_key' => 'split-utm-tgt',
            'destination_url' => 'https://fallback.com',
            'forward_query_params' => true,
            'destination_type' => 'split',
            'rotation_variants' => [
                ['label' => 'Root Variant A', 'url' => 'https://root-a.com', 'weight' => 100],
            ],
            'targeting_rules' => [
                [
                    // Rule 1: Mobile users go to split rotation
                    'match' => 'or',
                    'destination_type' => 'split',
                    'filters' => [['type' => 'device', 'data' => ['devices' => ['mobile']]]],
                    'variants' => [
                        ['label' => 'Mobile Variant B', 'url' => 'https://mobile-b.com', 'weight' => 100],
                    ],
                ],
                [
                    // Rule 2: Tablet users go to a single destination
                    'match' => 'or',
                    'destination_type' => 'single',
                    'url' => 'https://tablet-single.com',
                    'filters' => [['type' => 'device', 'data' => ['devices' => ['tablet']]]],
                ],
            ],
        ]);

        // 1. Visit as tablet with UTM params -> redirected to tablet-single.com with query parameters
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)')
            ->get('/s/split-utm-tgt?utm_source=newsletter&foo=bar')
            ->assertRedirect('https://tablet-single.com?utm_source=newsletter&foo=bar');

        // 2. Visit as mobile with query params -> redirected to mobile-b.com with query parameters
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get('/s/split-utm-tgt?utm_source=social')
            ->assertRedirect('https://mobile-b.com?utm_source=social');

        // Verify the resolved variant is stored in container for tracking
        expect(app('resolved_ab_variant'))->toBe('Mobile Variant B');

        // 3. Visit as desktop with query params -> redirects to default root variant A with query parameters
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
            ->get('/s/split-utm-tgt?utm_source=direct')
            ->assertRedirect('https://root-a.com?utm_source=direct');

        expect(app('resolved_ab_variant'))->toBe('Root Variant A');
    });

    it('custom domain + rate limiting + single use operate without caching bypasses', function () {
        config([
            'filament-short-url.cache_ttl' => 3600,
            'filament-short-url.rate_limiting.enabled' => true,
            'filament-short-url.rate_limiting.max_attempts' => 10,
            'filament-short-url.rate_limiting.decay_seconds' => 60,
            'app.url' => 'http://localhost',
        ]);

        $domain = ShortUrlCustomDomain::create([
            'domain' => 'brand.test',
            'is_active' => true,
            'is_verified' => true,
        ]);

        $link = makeLink([
            'url_key' => 'su-domain',
            'custom_domain_id' => $domain->id,
            'single_use' => true,
            'is_enabled' => true,
        ]);

        // Clear local process/facade cache
        cache()->forget('filament-short-url:custom-domain:brand.test');
        cache()->forget('filament-short-url:su-domain:brand.test');

        // First visit via custom domain: redirect succeeds using full URL
        $this->get('http://brand.test/su-domain')
            ->assertRedirect('https://example.com');

        // Verify DB is disabled
        $link->refresh();
        expect($link->is_enabled)->toBeFalse();

        // Second visit via custom domain: returns 410 Gone (even if cache contains stale enabled status)
        $this->get('http://brand.test/su-domain')
            ->assertStatus(410);
    });

    it('manually disabled link returns 410 even if expired and has expiration_redirect_url set', function () {
        makeLink([
            'url_key' => 'disabled-fallback-expired',
            'is_enabled' => false,
            'expires_at' => now()->subDay(),
            'expiration_redirect_url' => 'https://fallback.example.com',
        ]);

        $this->get('/s/disabled-fallback-expired')->assertStatus(410);
    });

    it('single_use link with track_visits disabled still gets disabled on first visit', function () {
        $link = makeLink([
            'url_key' => 'su-no-track',
            'single_use' => true,
            'track_visits' => false,
            'is_enabled' => true,
        ]);

        $this->get('/s/su-no-track')->assertRedirect('https://example.com');

        $link->refresh();
        expect($link->is_enabled)->toBeFalse();
    });

    it('rate limiting prevents single_use consumption on blocked requests', function () {
        $key = 'su-rl-block-first';
        $ip = '127.0.0.1';
        $limiterKey = "short_url_limit:{$key}:{$ip}";

        // Clear rate limiter key
        RateLimiter::clear($limiterKey);

        config([
            'filament-short-url.rate_limiting.enabled' => true,
            'filament-short-url.rate_limiting.max_attempts' => 1,
            'filament-short-url.rate_limiting.decay_seconds' => 60,
        ]);

        $link = makeLink([
            'url_key' => $key,
            'single_use' => true,
            'is_enabled' => true,
        ]);

        // Manually record 1 hit so the next hit (the request) will exceed the limit of 1
        RateLimiter::hit($limiterKey, 60);

        // First visit to the link is immediately rate limited
        $this->get("/s/{$key}")->assertStatus(429);

        // The link must NOT be disabled because the request was blocked before hitting single_use update
        $link->refresh();
        expect($link->is_enabled)->toBeTrue();
    });

    it('cannot bypass password prompt by passing confirmed parameter', function () {
        makeLink([
            'url_key' => 'pw-bypass',
            'password' => 'secret',
            'show_warning_page' => true,
        ]);

        // Attempting to bypass password using ?confirmed=1 should still show password prompt
        $this->get('/s/pw-bypass?confirmed=1')
            ->assertStatus(200)
            ->assertSee('Password Required')
            ->assertDontSee('Security Redirect Warning');
    });

    it('single_use link combined with warning page is only disabled after confirmation redirect', function () {
        $link = makeLink([
            'url_key' => 'su-warn',
            'single_use' => true,
            'show_warning_page' => true,
            'is_enabled' => true,
        ]);

        // 1. First request: warning page rendered, link remains enabled
        $this->get('/s/su-warn')
            ->assertStatus(200)
            ->assertSee('Security Redirect Warning');

        $link->refresh();
        expect($link->is_enabled)->toBeTrue();

        // 2. Second request: confirmed, redirects, link is disabled
        $this->get('/s/su-warn?confirmed=1')
            ->assertRedirect('https://example.com');

        $link->refresh();
        expect($link->is_enabled)->toBeFalse();

        // 3. Third request: returns 410
        $this->get('/s/su-warn?confirmed=1')
            ->assertStatus(410);
    });

});
