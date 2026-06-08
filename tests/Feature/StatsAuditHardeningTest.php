<?php

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Services\ProxyDetectionService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlPasswordHasher;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

if (! function_exists('createAuditLink')) {
    function createAuditLink(array $attrs = []): ShortUrl
    {
        return app(ShortUrlService::class)->create(array_merge([
            'destination_url' => 'https://example.com',
        ], $attrs));
    }
}

it('collects filtered stats via daily rollups and a single raw scan', function () {
    $link = createAuditLink();

    $link->visits()->create([
        'ip_address' => '1.1.1.1',
        'ip_hash' => hash('sha256', '1.1.1.1'),
        'country_code' => 'PL',
        'visited_at' => now(),
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $link->visits()->create([
        'ip_address' => '2.2.2.2',
        'ip_hash' => hash('sha256', '2.2.2.2'),
        'country_code' => 'DE',
        'visited_at' => now(),
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $stats = $link->getCachedStats(filters: ['country_code' => 'PL']);

    expect($stats['totalVisits'])->toBe(1)
        ->and($stats['visitsByCountry'])->toHaveKey('PL')
        ->and($stats['visitsByCountry']['PL'])->toBe(1);
});

it('uses cached security breakdown with a single aggregated query', function () {
    $link = createAuditLink();

    $link->visits()->create([
        'ip_address' => '1.1.1.1',
        'ip_hash' => hash('sha256', '1.1.1.1'),
        'visited_at' => now(),
        'is_bot' => true,
        'is_proxy' => false,
    ]);

    $link->visits()->create([
        'ip_address' => '2.2.2.2',
        'ip_hash' => hash('sha256', '2.2.2.2'),
        'visited_at' => now(),
        'is_bot' => false,
        'is_proxy' => true,
    ]);

    $stats = $link->getSecurityBreakdownStats();

    expect($stats['totalClicks'])->toBe(2)
        ->and($stats['botClicks'])->toBe(1)
        ->and($stats['proxyClicks'])->toBe(1)
        ->and($stats['humanClicks'])->toBe(0);
});

it('fails closed on vpn detection errors when blocking is enabled', function () {
    config([
        'filament-short-url.vpn_detection.enabled' => true,
        'filament-short-url.vpn_detection.block_action' => 'block_with_403',
        'filament-short-url.vpn_detection.driver' => 'ip-api',
    ]);

    Http::fake(fn () => throw new RuntimeException('timeout'));

    $result = app(ProxyDetectionService::class)->detect('8.8.8.8');

    expect($result['is_proxy'])->toBeTrue();
});

it('rejects public stats passwords supplied in the query string', function () {
    $hasher = app(ShortUrlPasswordHasher::class);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'secured-stats',
        'public_stats_enabled' => true,
        'public_stats_password' => $hasher->hash('secret-stats'),
        'track_visits' => false,
    ]);

    $this->get('/s/public-stats/'.$shortUrl->url_key.'?password=secret-stats')
        ->assertForbidden();

    $this->postJson('/s/public-stats/'.$shortUrl->url_key, [
        'password' => 'secret-stats',
    ])->assertOk()
        ->assertJsonStructure(['data' => ['totalVisits', 'uniqueVisits']]);
});

it('excludes links over max_visits from scopeActive when counter buffer pushes total over limit', function () {
    config([
        'filament-short-url.counter_buffering.enabled' => true,
    ]);

    $link = createAuditLink([
        'url_key' => 'scope-buffer',
        'max_visits' => 2,
        'total_visits' => 1,
    ]);

    $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
    cache()->put("{$prefix}total:{$link->id}", 2);
    cache()->put("{$prefix}dirty_ids", [$link->id]);

    expect(ShortUrl::query()->active()->whereKey($link->id)->exists())->toBeFalse()
        ->and($link->fresh()->isActive())->toBeFalse();
});

it('redirects legacy prefixed paths on custom domains with 301', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'links.acme.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'promo',
        'custom_domain_id' => $domain->id,
    ]);

    $this->get('http://links.acme.com/s/promo')
        ->assertRedirect('http://links.acme.com/promo')
        ->assertStatus(301);
});

it('preserves last_aggregation_date when saving unrelated settings', function () {
    DB::table('short_url_settings')->insert([
        'key' => 'last_aggregation_date',
        'value' => json_encode('2026-06-01'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(ShortUrlSettingsManager::class)->set([
        'cache_ttl' => 7200,
    ]);

    expect(DB::table('short_url_settings')->where('key', 'last_aggregation_date')->exists())->toBeTrue();
});

it('does not overwrite masked secret settings on save', function () {
    DB::table('short_url_settings')->insert([
        'key' => 'ga4_api_secret',
        'value' => json_encode('real-secret-value'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(ShortUrlSettingsManager::class)->set([
        'ga4_api_secret' => 'G-XXXX••••••••',
        'cache_ttl' => 3600,
    ]);

    $stored = json_decode(
        (string) DB::table('short_url_settings')->where('key', 'ga4_api_secret')->value('value'),
        true,
    );

    expect($stored)->toBe('real-secret-value');
});

it('blocks webhook redirects for ssrf hardening', function () {
    config(['filament-short-url.webhook_signing_required' => false]);

    Http::fake([
        'https://webhook.site/*' => Http::response('', 302, [
            'Location' => 'http://169.254.169.254/latest/meta-data/',
        ]),
    ]);

    $job = new SendWebhookJob(
        url: 'https://webhook.site/callback',
        event: 'visited',
        payload: ['event' => 'visited'],
    );

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);
});

it('respects global webhook events for per-link webhooks', function () {
    Queue::fake();

    config([
        'filament-short-url.webhook_events' => ['visited'],
    ]);

    $link = createAuditLink([
        'webhook_url' => 'https://hooks.example.test/link',
    ]);

    $link->dispatchWebhook('created');

    Queue::assertNothingPushed();
});

it('rate limits public stats per link and ip', function () {
    $hasher = app(ShortUrlPasswordHasher::class);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'stats-rate-limit',
        'public_stats_enabled' => true,
        'public_stats_password' => $hasher->hash('secret-stats'),
        'track_visits' => false,
    ]);

    RateLimiter::clear('short_url_public_stats:'.$shortUrl->id.':'.sha1('127.0.0.1'));

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/s/public-stats/'.$shortUrl->url_key, [
            'password' => 'secret-stats',
        ])->assertOk();
    }

    $this->postJson('/s/public-stats/'.$shortUrl->url_key, [
        'password' => 'secret-stats',
    ])->assertStatus(429);
});

it('normalizes custom domains without www on save', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'www.links.example.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    expect($domain->fresh()->domain)->toBe('links.example.com')
        ->and(ShortUrlCustomDomain::resolveForHost('www.links.example.com')?->id)->toBe($domain->id);
});
