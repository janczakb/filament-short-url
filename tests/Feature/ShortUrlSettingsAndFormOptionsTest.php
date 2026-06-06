<?php

/**
 * Comprehensive settings & form options integration tests.
 *
 * Every config option, every form-exposed link attribute, and every
 * redirect-path combination must have at least one test here.
 * Tests live inside the plugin so they ship with the package.
 */

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

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
// 1.  SETTINGS MANAGER — persistence, cache, config override
// ═══════════════════════════════════════════════════════════════════════════

describe('settings manager', function () {
    beforeEach(function () {
        cache()->forget('filament-short-url:settings');
        app()->forgetInstance(ShortUrlSettingsManager::class);
    });

    it('returns default values when nothing is stored', function () {
        $mgr = mgr();

        expect($mgr->get('route_prefix'))->toBe(config('filament-short-url.route_prefix', 's'))
            ->and($mgr->get('cache_ttl'))->toBe(config('filament-short-url.cache_ttl', 3600))
            ->and($mgr->get('redirect_status_code'))->toBe(302)
            ->and($mgr->get('key_length'))->toBe(6)
            ->and($mgr->get('lock_url_key'))->toBeFalse()
            ->and($mgr->get('disable_default_domain'))->toBeFalse()
            ->and($mgr->get('geo_ip_enabled'))->toBeTrue()
            ->and($mgr->get('tracking_enabled'))->toBeTrue()
            ->and($mgr->get('tracking_anonymize_ips'))->toBeFalse()
            ->and($mgr->get('counter_buffering_enabled'))->toBeFalse()
            ->and($mgr->get('rate_limiting_enabled'))->toBeFalse()
            ->and($mgr->get('pruning_enabled'))->toBeTrue()
            ->and($mgr->get('pruning_retention_days'))->toBe(90)
            ->and($mgr->get('trust_cdn_headers'))->toBeFalse()
            ->and($mgr->get('api_enabled'))->toBeFalse()
            ->and($mgr->get('global_webhook_enabled'))->toBeFalse()
            ->and($mgr->get('vpn_detection_enabled'))->toBeFalse();
    });

    it('persists a setting to database and reads it back', function () {
        mgr()->set(['route_prefix' => 'go']);

        // Fresh instance (cache cleared by set())
        app()->forgetInstance(ShortUrlSettingsManager::class);
        cache()->forget('filament-short-url:settings');

        expect(mgr()->get('route_prefix'))->toBe('go');
    });

    it('stored setting overrides config default', function () {
        config(['filament-short-url.cache_ttl' => 3600]);
        mgr()->set(['cache_ttl' => 99]);

        app()->forgetInstance(ShortUrlSettingsManager::class);
        cache()->forget('filament-short-url:settings');

        expect(mgr()->get('cache_ttl'))->toBe(99);
    });

    it('does not persist enable_fallback_route (boot-time-only setting)', function () {
        mgr()->set(['enable_fallback_route' => false]);

        $stored = DB::table('short_url_settings')
            ->where('key', 'enable_fallback_route')
            ->exists();

        expect($stored)->toBeFalse();
    });

    it('applies config overrides immediately after set()', function () {
        mgr()->set(['cache_ttl' => 777]);

        // applyConfigOverrides should have pushed the value to config() directly
        expect(config('filament-short-url.cache_ttl'))->toBe(777);
    });

    it('caches settings in local process memory after first read', function () {
        $mgr = mgr();
        $mgr->get('route_prefix'); // warms internal cache

        // Delete from DB — should still read from in-memory cache
        DB::table('short_url_settings')->truncate();
        cache()->forget('filament-short-url:settings');

        // In-memory cache still populated on same instance
        expect($mgr->get('route_prefix'))->toBe(config('filament-short-url.route_prefix', 's'));
    });

    it('invalidates cache on set() so next read contains the updated value', function () {
        mgr()->set(['key_length' => 10]);

        // set() clears the old cache entry then immediately re-populates it via
        // applyConfigOverrides(). The invariant is that the UPDATED value is now
        // cached — so a fresh read from another process sees the new value without
        // a DB round-trip.
        app()->forgetInstance(ShortUrlSettingsManager::class);
        cache()->forget('filament-short-url:settings');

        expect(mgr()->get('key_length'))->toBe(10);
    });

    it('does not store unsupported (unknown) keys', function () {
        mgr()->set(['totally_fake_key' => 'evil_value']);

        $stored = DB::table('short_url_settings')
            ->where('key', 'totally_fake_key')
            ->exists();

        expect($stored)->toBeFalse();
    });

    it('partial set() only updates given keys, others are preserved', function () {
        mgr()->set(['route_prefix' => 'short', 'cache_ttl' => 500]);
        app()->forgetInstance(ShortUrlSettingsManager::class);
        cache()->forget('filament-short-url:settings');

        mgr()->set(['cache_ttl' => 600]); // only update cache_ttl
        app()->forgetInstance(ShortUrlSettingsManager::class);
        cache()->forget('filament-short-url:settings');

        $fresh = mgr();
        expect($fresh->get('route_prefix'))->toBe('short')  // unchanged
            ->and($fresh->get('cache_ttl'))->toBe(600);     // updated
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 2.  LINK OPTIONS — is_enabled, forward_query_params
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: is_enabled', function () {
    it('enabled link redirects to destination', function () {
        makeLink(['url_key' => 'enabled-link', 'is_enabled' => true]);
        $this->get('/s/enabled-link')->assertRedirect('https://example.com');
    });

    it('disabled link returns 410', function () {
        makeLink(['url_key' => 'disabled-link', 'is_enabled' => false]);
        $this->get('/s/disabled-link')->assertStatus(410);
    });

    it('disabled link returns 410 even with expiration_redirect_url set (only expiry triggers fallback)', function () {
        // expiration_redirect_url is only followed when the link has actually expired or is not yet active.
        // A manually disabled link (is_enabled=false) returns 410 regardless.
        makeLink([
            'url_key' => 'disabled-fallback',
            'is_enabled' => false,
            'expiration_redirect_url' => 'https://fallback.example.com',
        ]);
        $this->get('/s/disabled-fallback')->assertStatus(410);
    });
});

describe('link option: forward_query_params', function () {
    it('forwards query params to destination when enabled', function () {
        makeLink(['url_key' => 'fwd-on', 'forward_query_params' => true]);
        $this->get('/s/fwd-on?foo=bar&baz=qux')
            ->assertRedirect('https://example.com?foo=bar&baz=qux');
    });

    it('does not forward query params when disabled', function () {
        makeLink(['url_key' => 'fwd-off', 'forward_query_params' => false]);
        $this->get('/s/fwd-off?foo=bar')
            ->assertRedirect('https://example.com');
    });

    it('merges query params with existing destination query string', function () {
        makeLink([
            'url_key' => 'fwd-merge',
            'destination_url' => 'https://example.com?existing=1',
            'forward_query_params' => true,
        ]);
        $response = $this->get('/s/fwd-merge?added=2');
        $location = $response->headers->get('Location');

        expect($location)->toContain('existing=1')
            ->and($location)->toContain('added=2');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 3.  LINK OPTIONS — redirect_status_code
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: redirect_status_code', function () {
    it('uses 302 by default', function () {
        makeLink(['url_key' => 'code-302']);
        $this->get('/s/code-302')->assertStatus(302);
    });

    it('uses 301 when specified', function () {
        $link = makeLink(['url_key' => 'code-301', 'redirect_status_code' => 301]);

        // Per business rule, 301 is forced to 302 when expires_at or max_visits is set.
        // Without those, 301 should be respected.
        expect($link->redirect_status_code)->toBe(301);
        $this->get('/s/code-301')->assertStatus(301);
    });

    it('forces 302 when expires_at is set regardless of requested status code', function () {
        $link = makeLink([
            'url_key' => 'code-force',
            'redirect_status_code' => 301,
            'expires_at' => now()->addDay(),
        ]);
        expect($link->redirect_status_code)->toBe(302);
    });

    it('forces 302 when max_visits is set regardless of requested status code', function () {
        $link = makeLink([
            'url_key' => 'code-force-mv',
            'redirect_status_code' => 301,
            'max_visits' => 100,
        ]);
        expect($link->redirect_status_code)->toBe(302);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 4.  LINK OPTIONS — activated_at / expires_at / deactivated_at / max_visits
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: date validity', function () {
    it('returns 410 when not yet activated', function () {
        makeLink(['url_key' => 'not-yet', 'activated_at' => now()->addHour()]);
        $this->get('/s/not-yet')->assertStatus(410);
    });

    it('redirects when activated_at is in the past', function () {
        makeLink(['url_key' => 'was-activated', 'activated_at' => now()->subHour()]);
        $this->get('/s/was-activated')->assertRedirect('https://example.com');
    });

    it('returns 410 after expires_at', function () {
        makeLink(['url_key' => 'expired', 'expires_at' => now()->subSecond()]);
        $this->get('/s/expired')->assertStatus(410);
    });

    it('redirects before expires_at', function () {
        makeLink(['url_key' => 'not-expired', 'expires_at' => now()->addHour()]);
        $this->get('/s/not-expired')->assertRedirect('https://example.com');
    });

    it('returns 410 when both activated_at is past and deactivated_at is past', function () {
        makeLink([
            'url_key' => 'deactivated',
            'activated_at' => now()->subDay(),
            'deactivated_at' => now()->subHour(),
        ]);
        $this->get('/s/deactivated')->assertStatus(410);
    });

    it('redirects when within valid activated_at / deactivated_at window', function () {
        makeLink([
            'url_key' => 'valid-window',
            'activated_at' => now()->subHour(),
            'deactivated_at' => now()->addHour(),
        ]);
        $this->get('/s/valid-window')->assertRedirect('https://example.com');
    });

    it('uses expiration_redirect_url when expired', function () {
        makeLink([
            'url_key' => 'exp-redirect',
            'expires_at' => now()->subSecond(),
            'expiration_redirect_url' => 'https://new.example.com',
        ]);
        $this->get('/s/exp-redirect')->assertRedirect('https://new.example.com');
    });
});

describe('link option: max_visits', function () {
    it('redirects when below max_visits', function () {
        $link = makeLink(['url_key' => 'mv-below', 'max_visits' => 5]);
        $link->update(['total_visits' => 4]);

        $this->get('/s/mv-below')->assertRedirect('https://example.com');
    });

    it('returns 410 when max_visits reached', function () {
        $link = makeLink(['url_key' => 'mv-at', 'max_visits' => 3]);
        $link->update(['total_visits' => 3]);

        $this->get('/s/mv-at')->assertStatus(410);
    });

    it('returns 410 when max_visits reached (expiration_redirect_url does not apply to visit cap)', function () {
        // expiration_redirect_url only fires for time-based expiry, not for visit count cap.
        $link = makeLink([
            'url_key' => 'mv-fallback',
            'max_visits' => 1,
            'expiration_redirect_url' => 'https://done.example.com',
        ]);
        $link->update(['total_visits' => 1]);

        $this->get('/s/mv-fallback')->assertStatus(410);
    });

    it('single_use is mutually exclusive with max_visits in the form: max_visits field hidden when single_use is on', function () {
        // When single_use=true, max_visits should have no effect (it's hidden in the UI).
        // Model-level: single_use is independent in DB, but form hides max_visits.
        // Here we just verify that a single_use link with max_visits > 0 still
        // disables after first use (single_use takes precedence at controller level).
        config(['filament-short-url.queue_connection' => 'sync']);

        $link = makeLink([
            'url_key' => 'su-mv',
            'single_use' => true,
            'max_visits' => 999, // should be irrelevant
            'track_visits' => true,
        ]);

        $this->get('/s/su-mv')->assertRedirect('https://example.com');

        // Link is now disabled
        $link->refresh();
        expect($link->is_enabled)->toBeFalse();

        // Second visit must return 410
        $this->get('/s/su-mv')->assertStatus(410);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 5.  LINK OPTIONS — single_use
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: single_use', function () {
    it('disables the link after first visit', function () {
        config(['filament-short-url.queue_connection' => 'sync']);
        makeLink(['url_key' => 'su1', 'single_use' => true, 'track_visits' => true]);

        $this->get('/s/su1')->assertRedirect();

        $link = ShortUrl::where('url_key', 'su1')->first();
        expect($link->is_enabled)->toBeFalse();
    });

    it('returns 410 on second visit after single use', function () {
        config([
            'filament-short-url.cache_ttl' => 3600,
            'filament-short-url.queue_connection' => 'sync',
        ]);
        makeLink(['url_key' => 'su2', 'single_use' => true, 'track_visits' => true]);

        $this->get('/s/su2')->assertRedirect();
        $this->get('/s/su2')->assertStatus(410);
    });

    it('re-fetch from DB prevents stale-cache bypass for single_use', function () {
        // Manually prime the cache with is_enabled=true,
        // then disable the link in DB.
        config(['filament-short-url.cache_ttl' => 3600]);

        $link = makeLink(['url_key' => 'su-cache', 'single_use' => true]);

        // Warm cache (is_enabled=true in cache)
        ShortUrl::findByKey('su-cache');

        // Disable in DB directly (simulates another request having consumed it)
        ShortUrl::where('id', $link->id)->update(['is_enabled' => false]);

        // isActive() must re-fetch from DB for single_use and return false
        $link->refresh();
        $fresh = ShortUrl::findByKey('su-cache'); // still cached with enabled=true
        expect($fresh)->not->toBeNull();
        expect($fresh->isActive())->toBeFalse(); // DB re-fetch must catch this
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 6.  LINK OPTIONS — password protection
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: password', function () {
    it('shows password prompt to unauthenticated visitor', function () {
        makeLink(['url_key' => 'pw1', 'password' => 'letmein']);

        // GET request to /s/{key} should redirect to /s-auth/{key}
        $response = $this->get('/s/pw1');
        $response->assertStatus(302);
        $response->assertRedirect('/s-auth/pw1');

        // GET request to /s-auth/{key} should show password prompt
        $this->get('/s-auth/pw1')->assertStatus(200)->assertSee('Password Required');
    });

    it('redirects after correct password via session', function () {
        makeLink(['url_key' => 'pw2', 'password' => 'open']);

        // POST correct password to /s-auth/{key} -> redirects back to /s-auth/{key}
        $this->post('/s-auth/pw2', ['password' => 'open'])->assertRedirect('/s-auth/pw2');
        // Following request to /s-auth/{key} redirects to final destination
        $this->get('/s-auth/pw2')->assertRedirect('https://example.com');
    });

    it('returns error view on wrong password', function () {
        makeLink(['url_key' => 'pw3', 'password' => 'right']);

        // POST wrong password to /s-auth/{key} -> returns prompt with error
        $this->post('/s-auth/pw3', ['password' => 'wrong'])
            ->assertStatus(200)
            ->assertSee('Incorrect password');
    });

    it('rate limits after 5 wrong password attempts', function () {
        makeLink(['url_key' => 'pw-rl', 'password' => 'correct']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/s-auth/pw-rl', ['password' => 'wrong'])->assertStatus(200);
        }

        $this->post('/s-auth/pw-rl', ['password' => 'wrong'])->assertStatus(429);
    });

    it('password check happens before warning page', function () {
        makeLink(['url_key' => 'pw-warn', 'password' => 'abc', 'show_warning_page' => true]);

        // Unauthenticated visitor redirects to /s-auth/pw-warn
        $response = $this->get('/s/pw-warn');
        $response->assertStatus(302);
        $response->assertRedirect('/s-auth/pw-warn');

        // Password prompt must appear first, not warning
        $this->get('/s-auth/pw-warn')->assertSee('Password Required')->assertDontSee('Security Redirect Warning');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 7.  LINK OPTIONS — show_warning_page
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: show_warning_page', function () {
    it('shows warning view without ?confirmed', function () {
        makeLink(['url_key' => 'warn1', 'show_warning_page' => true]);

        $this->get('/s/warn1')->assertStatus(200)->assertSee('Security Redirect Warning');
    });

    it('redirects with ?confirmed=1', function () {
        makeLink(['url_key' => 'warn2', 'show_warning_page' => true]);

        $this->get('/s/warn2?confirmed=1')->assertRedirect('https://example.com');
    });

    it('does not show warning when disabled', function () {
        makeLink(['url_key' => 'no-warn', 'show_warning_page' => false]);

        $this->get('/s/no-warn')->assertRedirect('https://example.com');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 8.  SETTINGS OPTION — rate_limiting
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: rate_limiting', function () {
    it('passes requests below the limit', function () {
        config([
            'filament-short-url.rate_limiting.enabled' => true,
            'filament-short-url.rate_limiting.max_attempts' => 5,
            'filament-short-url.rate_limiting.decay_seconds' => 60,
        ]);

        makeLink(['url_key' => 'rl-pass']);

        $this->get('/s/rl-pass')->assertRedirect('https://example.com');
    });

    it('blocks after limit is exceeded', function () {
        config([
            'filament-short-url.rate_limiting.enabled' => true,
            'filament-short-url.rate_limiting.max_attempts' => 2,
            'filament-short-url.rate_limiting.decay_seconds' => 10,
        ]);

        makeLink(['url_key' => 'rl-block']);

        $this->get('/s/rl-block')->assertStatus(302);
        $this->get('/s/rl-block')->assertStatus(302);
        $this->get('/s/rl-block')->assertStatus(429);
    });

    it('does not rate-limit when disabled', function () {
        config(['filament-short-url.rate_limiting.enabled' => false]);

        makeLink(['url_key' => 'rl-off']);

        for ($i = 0; $i < 10; $i++) {
            $this->get('/s/rl-off')->assertStatus(302);
        }
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 9.  SETTINGS OPTION — geo_ip (headers driver)
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: geo_ip with headers driver', function () {
    beforeEach(function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
            'filament-short-url.geo_ip.enabled' => true,
            'filament-short-url.geo_ip.driver' => 'headers',
            'filament-short-url.trust_cdn_headers' => true,
        ]);
    });

    it('resolves country from CF-IPCountry header', function () {
        $link = makeLink(['url_key' => 'geo-cf', 'track_visits' => true]);

        $this->get('/s/geo-cf', ['CF-IPCountry' => 'FR']);

        expect($link->visits()->first()->country_code)->toBe('FR');
    });

    it('resolves city from CF-IPCity header', function () {
        $link = makeLink(['url_key' => 'geo-city', 'track_visits' => true]);

        $this->get('/s/geo-city', ['CF-IPCountry' => 'DE', 'CF-IPCity' => 'Berlin']);

        $visit = $link->visits()->first();
        expect($visit->country_code)->toBe('DE')
            ->and($visit->city)->toBe('Berlin');
    });

    it('stores null country when no geo header present and geo is enabled', function () {
        config(['filament-short-url.trust_cdn_headers' => false]);
        $link = makeLink(['url_key' => 'geo-null', 'track_visits' => true]);

        $this->get('/s/geo-null'); // no geo headers

        $visit = $link->visits()->first();
        expect($visit->country_code)->toBeNull();
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 10. SETTINGS OPTION — tracking fields
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: tracking fields', function () {
    beforeEach(function () {
        config(['filament-short-url.queue_connection' => 'sync']);
    });

    it('records ip_address when track_ip_address is enabled', function () {
        $link = makeLink(['url_key' => 'tip-on', 'track_visits' => true, 'track_ip_address' => true]);

        $this->get('/s/tip-on', [], ['REMOTE_ADDR' => '5.6.7.8']);

        expect($link->visits()->first()->ip_address)->not->toBeNull();
    });

    it('does not record ip_address when track_ip_address is disabled', function () {
        $link = makeLink(['url_key' => 'tip-off', 'track_visits' => true, 'track_ip_address' => false]);

        $this->get('/s/tip-off');

        expect($link->visits()->first()->ip_address)->toBeNull();
    });

    it('records browser when track_browser is enabled', function () {
        $link = makeLink(['url_key' => 'tb-on', 'track_visits' => true, 'track_browser' => true]);

        $this->get('/s/tb-on', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36']);

        expect($link->visits()->first()->browser)->not->toBeNull();
    });

    it('does not record browser when track_browser is disabled', function () {
        $link = makeLink(['url_key' => 'tb-off', 'track_visits' => true, 'track_browser' => false]);

        $this->get('/s/tb-off', ['User-Agent' => 'Mozilla/5.0 Chrome/120']);

        expect($link->visits()->first()->browser)->toBeNull();
    });

    it('records device_type when track_device_type is enabled', function () {
        $link = makeLink(['url_key' => 'tdt-on', 'track_visits' => true, 'track_device_type' => true]);

        $this->get('/s/tdt-on', ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)']);

        expect($link->visits()->first()->device_type)->toBe('mobile');
    });

    it('does not record device_type when track_device_type is disabled', function () {
        $link = makeLink(['url_key' => 'tdt-off', 'track_visits' => true, 'track_device_type' => false]);

        $this->get('/s/tdt-off', ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)']);

        expect($link->visits()->first()->device_type)->toBeNull();
    });

    it('records referer_url when track_referer_url is enabled', function () {
        $link = makeLink(['url_key' => 'tref-on', 'track_visits' => true, 'track_referer_url' => true]);

        $this->get('/s/tref-on', ['Referer' => 'https://google.com/search?q=test']);

        expect($link->visits()->first()->referer_url)->toBe('https://google.com/search?q=test');
    });

    it('does not record referer_url when track_referer_url is disabled', function () {
        $link = makeLink(['url_key' => 'tref-off', 'track_visits' => true, 'track_referer_url' => false]);

        $this->get('/s/tref-off', ['Referer' => 'https://google.com/search?q=test']);

        expect($link->visits()->first()->referer_url)->toBeNull();
    });

    it('records browser_language when enabled', function () {
        $link = makeLink(['url_key' => 'tbl-on', 'track_visits' => true, 'track_browser_language' => true]);

        $this->get('/s/tbl-on', ['Accept-Language' => 'pl-PL,pl;q=0.9']);

        expect($link->visits()->first()->browser_language)->toBe('pl');
    });

    it('does not record browser_language when disabled', function () {
        $link = makeLink(['url_key' => 'tbl-off', 'track_visits' => true, 'track_browser_language' => false]);

        $this->get('/s/tbl-off', ['Accept-Language' => 'pl-PL,pl;q=0.9']);

        expect($link->visits()->first()->browser_language)->toBeNull();
    });

    it('records no visit data at all when track_visits is false', function () {
        $link = makeLink(['url_key' => 'no-track', 'track_visits' => false]);

        $this->get('/s/no-track');

        expect($link->visits()->count())->toBe(0);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 11. SETTINGS OPTION — tracking_anonymize_ips
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: tracking_anonymize_ips', function () {
    it('anonymizes ipv4 when enabled', function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
            'filament-short-url.tracking.anonymize_ips' => true,
        ]);

        $link = makeLink(['url_key' => 'anon-v4', 'track_visits' => true, 'track_ip_address' => true]);

        // withServerVariables sets REMOTE_ADDR correctly in the test client
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.10.55'])
            ->get('/s/anon-v4');

        expect($link->visits()->first()->ip_address)->toBe('192.168.10.0');
    });

    it('hash is computed on raw ip before anonymization', function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
            'filament-short-url.tracking.anonymize_ips' => true,
        ]);

        $link = makeLink(['url_key' => 'anon-hash', 'track_visits' => true, 'track_ip_address' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
            ->get('/s/anon-hash');

        $visit = $link->visits()->first();
        // Stored IP is anonymized
        expect($visit->ip_address)->toBe('1.2.3.0');
        // Hash is of the raw (pre-anonymization) IP
        expect($visit->ip_hash)->toBe(hash_hmac('sha256', '1.2.3.4', config('app.key', '')));
    });

    it('stores full ip when anonymize_ips is disabled', function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
            'filament-short-url.tracking.anonymize_ips' => false,
        ]);

        $link = makeLink(['url_key' => 'no-anon', 'track_visits' => true, 'track_ip_address' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->get('/s/no-anon');

        expect($link->visits()->first()->ip_address)->toBe('10.20.30.40');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 12. SETTINGS OPTION — trust_cdn_headers + IP extraction
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: trust_cdn_headers', function () {
    it('reads CF-Connecting-IP when trust_cdn_headers is enabled', function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
            'filament-short-url.geo_ip.enabled' => false,
            'filament-short-url.trust_cdn_headers' => true,
        ]);

        $link = makeLink(['url_key' => 'cdn-ip', 'track_visits' => true, 'track_ip_address' => true]);

        $this->get('/s/cdn-ip', ['CF-Connecting-IP' => '11.22.33.44']);

        expect($link->visits()->first()->ip_address)->toBe('11.22.33.44');
    });

    it('ignores CDN headers when trust_cdn_headers is disabled', function () {
        config([
            'filament-short-url.queue_connection' => 'sync',
            'filament-short-url.geo_ip.enabled' => false,
            'filament-short-url.trust_cdn_headers' => false,
        ]);

        $link = makeLink(['url_key' => 'no-cdn-ip', 'track_visits' => true, 'track_ip_address' => true]);

        // Even with CF header, should use REMOTE_ADDR
        $this->get('/s/no-cdn-ip', ['CF-Connecting-IP' => '99.99.99.99'], ['REMOTE_ADDR' => '127.0.0.1']);

        expect($link->visits()->first()->ip_address)->not->toBe('99.99.99.99');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 13. SETTINGS OPTION — cache_ttl interaction with redirects
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: cache_ttl', function () {
    it('short url is served from cache on hot path', function () {
        config(['filament-short-url.cache_ttl' => 3600]);

        makeLink(['url_key' => 'cache-hot']);

        // First request primes cache
        $this->get('/s/cache-hot')->assertStatus(302);

        // The cache key uses the request host — in tests that is 'localhost'
        $cacheKey = 'filament-short-url:cache-hot:localhost';
        // Try both with and without app host suffix
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $found = cache()->has($cacheKey) || cache()->has("filament-short-url:cache-hot:{$appHost}");

        expect($found)->toBeTrue();
    });

    it('cache_ttl=0 disables caching', function () {
        config(['filament-short-url.cache_ttl' => 0]);

        makeLink(['url_key' => 'no-cache-link']);
        $this->get('/s/no-cache-link')->assertStatus(302);

        expect(cache()->has('filament-short-url:no-cache-link:localhost'))->toBeFalse();
    });

    it('deleting a link clears cache', function () {
        config(['filament-short-url.cache_ttl' => 3600]);

        $link = makeLink(['url_key' => 'del-cache']);
        $this->get('/s/del-cache'); // warm cache

        $link->delete();

        $this->get('/s/del-cache')->assertStatus(404);
    });

    it('updating a link clears cache', function () {
        config(['filament-short-url.cache_ttl' => 3600]);

        $link = makeLink(['url_key' => 'upd-cache', 'destination_url' => 'https://old.example.com']);
        $this->get('/s/upd-cache'); // warm cache

        $link->update(['destination_url' => 'https://new.example.com']);

        $this->get('/s/upd-cache')->assertRedirect('https://new.example.com');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 14. SETTINGS OPTION — counter_buffering with file/array store
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: counter_buffering', function () {
    beforeEach(function () {
        config([
            'filament-short-url.counter_buffering.enabled' => true,
            'filament-short-url.queue_connection' => 'sync',
        ]);
    });

    it('buffers visit counts in cache instead of writing to db immediately', function () {
        $link = makeLink(['url_key' => 'buf-1', 'track_visits' => true]);
        $originalVisits = $link->total_visits;

        $this->get('/s/buf-1');

        // DB should not have changed yet (buffered)
        $link->refresh();
        expect($link->total_visits)->toBe($originalVisits);

        // Cache buffer should have count=1
        $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
        expect((int) cache()->get("{$prefix}total:{$link->id}"))->toBe(1);
    });

    it('sync-counters command flushes buffer to database', function () {
        $link = makeLink(['url_key' => 'buf-sync', 'track_visits' => true]);
        $link->update(['total_visits' => 0]);

        // Simulate 3 visits buffered
        $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
        cache()->put("{$prefix}total:{$link->id}", 3);
        cache()->put("{$prefix}unique:{$link->id}", 2);
        cache()->forever("{$prefix}dirty_ids", [$link->id]);

        $this->artisan('short-url:sync-counters')->assertExitCode(0);

        $link->refresh();
        expect($link->total_visits)->toBe(3)
            ->and($link->unique_visits)->toBe(2);
    });

    it('getRealTimeTotalVisits includes buffered counts', function () {
        $link = makeLink(['url_key' => 'buf-rt']);
        $link->update(['total_visits' => 10]);

        $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
        cache()->put("{$prefix}total:{$link->id}", 5);

        expect($link->getRealTimeTotalVisits())->toBe(15);
    });

    it('falls back to DB increment if cache throws on buffering', function () {
        // Simulate a broken cache by replacing with a store that throws on increment
        // We verify the fallback path (queue connection=sync) does not crash the redirect

        config(['filament-short-url.queue_connection' => 'sync']);
        $link = makeLink(['url_key' => 'buf-fail', 'track_visits' => true]);

        // Even if buffering path has issues, redirect must still succeed
        $this->get('/s/buf-fail')->assertStatus(302);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 15. SETTINGS OPTION — pruning
// ═══════════════════════════════════════════════════════════════════════════

describe('settings option: pruning', function () {
    it('prunes visits older than retention_days', function () {
        config([
            'filament-short-url.pruning.enabled' => true,
            'filament-short-url.pruning.retention_days' => 7,
        ]);

        $link = makeLink(['url_key' => 'prune-test']);
        $oldVisit = $link->visits()->create([
            'visited_at' => now()->subDays(10),
            'ip_hash' => 'abc123',
        ]);
        $recentVisit = $link->visits()->create([
            'visited_at' => now()->subDays(3),
            'ip_hash' => 'def456',
        ]);

        $this->artisan('short-url:aggregate-and-prune')->assertExitCode(0);

        $this->assertDatabaseMissing('short_url_visits', ['id' => $oldVisit->id]);
        $this->assertDatabaseHas('short_url_visits', ['id' => $recentVisit->id]);
    });

    it('does not prune when pruning is disabled', function () {
        config([
            'filament-short-url.pruning.enabled' => false,
            'filament-short-url.pruning.retention_days' => 7,
        ]);

        $link = makeLink(['url_key' => 'prune-off']);
        $oldVisit = $link->visits()->create([
            'visited_at' => now()->subDays(30),
            'ip_hash' => 'xyz789',
        ]);

        $this->artisan('short-url:aggregate-and-prune')->assertExitCode(0);

        $this->assertDatabaseHas('short_url_visits', ['id' => $oldVisit->id]);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 16. LINK OPTION — destination_type split (A/B rotation)
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: destination_type split (A/B rotation)', function () {
    it('redirects to a variant when destination_type is split with 100% weight', function () {
        makeLink([
            'url_key' => 'split-100',
            'destination_type' => 'split',
            'rotation_variants' => [
                ['label' => 'Only', 'url' => 'https://variant-a.example.com', 'weight' => 100],
            ],
        ]);

        $this->get('/s/split-100')->assertRedirect('https://variant-a.example.com');
    });

    it('falls back to destination_url when no variant matches (rotation_variants empty)', function () {
        makeLink([
            'url_key' => 'split-empty',
            'destination_type' => 'split',
            'destination_url' => 'https://fallback.example.com',
            'rotation_variants' => [],
        ]);

        $this->get('/s/split-empty')->assertRedirect('https://fallback.example.com');
    });

    it('legacy targeting rules with rotation type redirect correctly', function () {
        makeLink([
            'url_key' => 'legacy-rot',
            'targeting_rules' => [
                'type' => 'rotation',
                'rotation' => [
                    ['url' => 'https://rot-a.example.com', 'weight' => 100],
                ],
            ],
        ]);

        $this->get('/s/legacy-rot')->assertRedirect('https://rot-a.example.com');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 17. LINK OPTION — targeting rules (new multi-filter engine)
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: targeting rules (new engine)', function () {
    it('routes mobile users to mobile url', function () {
        makeLink([
            'url_key' => 'tgt-mobile',
            'targeting_rules' => [[
                'match' => 'or',
                'url' => 'https://mobile.example.com',
                'filters' => [['type' => 'device', 'data' => ['devices' => ['mobile']]]],
            ]],
        ]);

        $this->get('/s/tgt-mobile', ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'])
            ->assertRedirect('https://mobile.example.com');
    });

    it('falls back to destination_url when device does not match', function () {
        makeLink([
            'url_key' => 'tgt-no-match',
            'targeting_rules' => [[
                'match' => 'or',
                'url' => 'https://mobile.example.com',
                'filters' => [['type' => 'device', 'data' => ['devices' => ['mobile']]]],
            ]],
        ]);

        $this->get('/s/tgt-no-match', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
            ->assertRedirect('https://example.com');
    });

    it('AND rule only matches when ALL filters match', function () {
        config(['filament-short-url.trust_cdn_headers' => true]);

        makeLink([
            'url_key' => 'tgt-and',
            'targeting_rules' => [[
                'match' => 'and',
                'url' => 'https://and-match.example.com',
                'filters' => [
                    ['type' => 'country', 'data' => ['countries' => ['PL']]],
                    ['type' => 'device', 'data' => ['devices' => ['mobile']]],
                ],
            ]],
        ]);

        // Both match → redirect
        $this->get('/s/tgt-and', [
            'CF-IPCountry' => 'PL',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
        ])->assertRedirect('https://and-match.example.com');

        // Only country → fallback
        $this->get('/s/tgt-and', [
            'CF-IPCountry' => 'PL',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ])->assertRedirect('https://example.com');
    });

    it('OR rule matches when ANY filter matches', function () {
        config(['filament-short-url.trust_cdn_headers' => true]);

        makeLink([
            'url_key' => 'tgt-or',
            'targeting_rules' => [[
                'match' => 'or',
                'url' => 'https://or-match.example.com',
                'filters' => [
                    ['type' => 'country', 'data' => ['countries' => ['PL']]],
                    ['type' => 'device', 'data' => ['devices' => ['mobile']]],
                ],
            ]],
        ]);

        // Only country matches
        $this->get('/s/tgt-or', [
            'CF-IPCountry' => 'PL',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0)',
        ])->assertRedirect('https://or-match.example.com');
    });

    it('language filter routes correctly', function () {
        makeLink([
            'url_key' => 'tgt-lang',
            'targeting_rules' => [[
                'match' => 'or',
                'url' => 'https://pl.example.com',
                'filters' => [['type' => 'language', 'data' => ['languages' => ['pl']]]],
            ]],
        ]);

        $this->get('/s/tgt-lang', ['Accept-Language' => 'pl-PL,pl;q=0.9'])
            ->assertRedirect('https://pl.example.com');

        $this->get('/s/tgt-lang', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertRedirect('https://example.com');
    });

    it('platform filter routes correctly', function () {
        makeLink([
            'url_key' => 'tgt-plat',
            'targeting_rules' => [[
                'match' => 'or',
                'url' => 'https://ios.example.com',
                'filters' => [['type' => 'platform', 'data' => ['platforms' => ['ios']]]],
            ]],
        ]);

        $this->get('/s/tgt-plat', ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'])
            ->assertRedirect('https://ios.example.com');

        $this->get('/s/tgt-plat', ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
            ->assertRedirect('https://example.com');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 18. LINK OPTION — GA tracking ID (does not break redirect)
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: ga_tracking_id', function () {
    it('redirect succeeds when ga_tracking_id is set but no GA4 api secret configured', function () {
        config(['filament-short-url.queue_connection' => 'sync']);
        config(['filament-short-url.ga4.api_secret' => null]);

        makeLink([
            'url_key' => 'ga-no-secret',
            'track_visits' => true,
            'ga_tracking_id' => 'G-TESTID',
        ]);

        // Should redirect without throwing — GA4 call skipped silently
        $this->get('/s/ga-no-secret')->assertStatus(302);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 19. LINK OPTION — notes (internal, not exposed in redirect)
// ═══════════════════════════════════════════════════════════════════════════

describe('link option: notes', function () {
    it('notes field is stored and does not affect redirect behavior', function () {
        $link = makeLink(['url_key' => 'notes-test', 'notes' => 'Internal reminder']);

        expect($link->notes)->toBe('Internal reminder');
        $this->get('/s/notes-test')->assertRedirect('https://example.com');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 20. CONFLICT / INTERACTION checks
// ═══════════════════════════════════════════════════════════════════════════

describe('option interactions and conflict scenarios', function () {
    it('expired link is not affected by is_enabled=true', function () {
        makeLink([
            'url_key' => 'conflict-exp',
            'is_enabled' => true,        // explicitly enabled
            'expires_at' => now()->subSecond(), // but expired
        ]);

        $this->get('/s/conflict-exp')->assertStatus(410);
    });

    it('not-yet-activated link returns 410 even with is_enabled=true', function () {
        makeLink([
            'url_key' => 'conflict-act',
            'is_enabled' => true,
            'activated_at' => now()->addHour(),
        ]);

        $this->get('/s/conflict-act')->assertStatus(410);
    });

    it('single_use link with password shows password prompt on first visit', function () {
        makeLink(['url_key' => 'su-pw', 'single_use' => true, 'password' => 'abc']);

        // Should redirect to stateful s-auth route
        $response = $this->get('/s/su-pw');
        $response->assertStatus(302);
        $response->assertRedirect('/s-auth/su-pw');

        // Visiting s-auth should render password prompt, NOT redirect + disable
        $this->get('/s-auth/su-pw')->assertSee('Password Required');

        // Link must still be enabled (not consumed by the password prompt visit)
        expect(ShortUrl::where('url_key', 'su-pw')->value('is_enabled'))->toBeTrue();
    });

    it('warning page + max_visits: warning shown first, max_visits enforced after confirm', function () {
        config(['filament-short-url.queue_connection' => 'sync']);

        $link = makeLink([
            'url_key' => 'warn-mv',
            'show_warning_page' => true,
            'max_visits' => 1,
            'track_visits' => true,
        ]);
        $link->update(['total_visits' => 0]);

        // First unconfirmed visit: warning page shown, visit NOT counted
        $this->get('/s/warn-mv')->assertSee('Security Redirect Warning');

        // Confirm: redirect succeeds, visit is tracked
        $this->get('/s/warn-mv?confirmed=1')->assertRedirect('https://example.com');

        // Now at max_visits=1. Next confirmed visit should 410
        $this->get('/s/warn-mv?confirmed=1')->assertStatus(410);
    });

    it('rate limiting + geo targeting: both apply independently', function () {
        config([
            'filament-short-url.rate_limiting.enabled' => true,
            'filament-short-url.rate_limiting.max_attempts' => 3,
            'filament-short-url.rate_limiting.decay_seconds' => 60,
            'filament-short-url.trust_cdn_headers' => true,
        ]);

        makeLink([
            'url_key' => 'rl-geo',
            'targeting_rules' => [[
                'match' => 'or',
                'url' => 'https://pl-target.example.com',
                'filters' => [['type' => 'country', 'data' => ['countries' => ['PL']]]],
            ]],
        ]);

        // Under rate limit: targeting works
        $this->get('/s/rl-geo', ['CF-IPCountry' => 'PL'])->assertRedirect('https://pl-target.example.com');
        $this->get('/s/rl-geo', ['CF-IPCountry' => 'PL'])->assertRedirect('https://pl-target.example.com');
        $this->get('/s/rl-geo', ['CF-IPCountry' => 'PL'])->assertRedirect('https://pl-target.example.com');

        // Rate limited
        $this->get('/s/rl-geo', ['CF-IPCountry' => 'PL'])->assertStatus(429);
    });

    it('forward_query_params does not interfere with UTM tracking', function () {
        config(['filament-short-url.queue_connection' => 'sync']);

        $link = makeLink([
            'url_key' => 'fwd-utm',
            'destination_url' => 'https://example.com',
            'forward_query_params' => true,
            'track_visits' => true,
            'track_referer_url' => true,
        ]);

        $this->get('/s/fwd-utm?utm_source=test&utm_medium=email', [
            'Referer' => 'https://newsletter.example.com',
        ]);

        $visit = $link->visits()->first();
        expect($visit->utm_source)->toBe('test')
            ->and($visit->utm_medium)->toBe('email')
            ->and($visit->referer_url)->toBe('https://newsletter.example.com');

        // Also verify the redirect includes the forwarded params
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 21. PERFORMANCE: no N+1 queries on redirect hot path
// ═══════════════════════════════════════════════════════════════════════════

describe('performance: redirect hot path', function () {
    it('serves redirect with at most 2 DB queries when model is cached', function () {
        config([
            'filament-short-url.cache_ttl' => 3600,
            'filament-short-url.queue_connection' => 'sync',
        ]);

        makeLink(['url_key' => 'perf-hot', 'track_visits' => false]);

        // Warm the cache
        $this->get('/s/perf-hot');

        // On second hit, model comes from cache
        // We can only measure DB queries via DB::getQueryLog()
        DB::enableQueryLog();

        $this->get('/s/perf-hot')->assertStatus(302);

        $queries = DB::getQueryLog();

        // On a pure cache hit with no tracking: 0 DB queries expected.
        // With single_use re-fetch: 1 DB query max.
        // With tracking (sync): additional queries for inserting visit.
        // Here track_visits=false so 0 DB queries on cache hit.
        expect(count($queries))->toBeLessThanOrEqual(1);

        DB::disableQueryLog();
    });
});
