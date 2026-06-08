<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Services\Ga4MeasurementProtocolService;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\ProxyDetectionService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlRedirectHandler;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

it('auto-enables trust_cdn_headers when geo driver is headers', function () {
    app(ShortUrlSettingsManager::class)->set([
        'geo_ip_driver' => 'headers',
        'trust_cdn_headers' => false,
    ]);

    expect(config('filament-short-url.trust_cdn_headers'))->toBeTrue();
});

it('validates GA4 credentials against real measurement id via debug collector', function () {
    Http::fake([
        'https://www.google-analytics.com/debug/mp/collect*' => Http::response([
            'validationMessages' => [],
        ], 200),
    ]);

    $result = app(Ga4MeasurementProtocolService::class)->validateCredentials(
        'G-TESTMEASURE',
        'secret-value',
    );

    expect($result['valid'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'measurement_id=G-TESTMEASURE')
            && str_contains($request->url(), 'api_secret=secret-value');
    });
});

it('rejects invalid GA4 measurement id format before calling Google', function () {
    Http::fake();

    $result = app(Ga4MeasurementProtocolService::class)->validateCredentials(
        'invalid-id',
        'secret-value',
    );

    expect($result['valid'])->toBeFalse();
    Http::assertNothingSent();
});

it('rate limits ip-api lookups to protect the free tier quota', function () {
    config([
        'filament-short-url.geo_ip.enabled' => true,
        'filament-short-url.geo_ip.driver' => 'ip-api',
        'filament-short-url.geo_ip.cache_ttl' => 0,
    ]);

    GeoIpService::flush();
    Cache::flush();
    RateLimiter::clear('fsu_geo_ip_api:'.now()->format('YmdHi'));

    Http::fake([
        'http://ip-api.com/*' => Http::response([
            'status' => 'success',
            'country' => 'Poland',
            'countryCode' => 'PL',
            'city' => 'Warsaw',
        ], 200),
    ]);

    $service = app(GeoIpService::class);

    for ($i = 0; $i < 41; $i++) {
        $service->resolve('8.8.8.'.($i % 250 + 1));
    }

    expect(Http::recorded()->count())->toBeLessThanOrEqual(40);
});

it('prevents activating unverified custom domains on save', function () {
    config(['filament-short-url.custom_domains.enforce_dns_on_activate' => true]);

    $domain = ShortUrlCustomDomain::create([
        'domain' => 'not-pointing.example.com',
        'is_active' => false,
        'is_verified' => false,
    ]);

    $domain->update(['is_active' => true]);

    expect($domain->fresh()->is_active)->toBeFalse()
        ->and($domain->fresh()->is_verified)->toBeFalse();
});

it('blocks upsert updates to locked url_key via api', function () {
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'lock_url_key' => true,
        'api_keys' => [
            [
                'name' => 'Test',
                'key' => 'sh_key_locked_upsert',
                'is_active' => true,
                'scope' => 'links:read-write',
            ],
        ],
    ]);

    ShortUrl::create([
        'destination_url' => 'https://example.com/original',
        'url_key' => 'locked-upsert',
        'external_id' => 'lock-test-1',
    ]);

    $this->putJson('/api/short-url/links/upsert', [
        'external_id' => 'lock-test-1',
        'destination_url' => 'https://example.com/original',
        'url_key' => 'changed-key',
    ], ['X-Api-Key' => 'sh_key_locked_upsert'])
        ->assertStatus(422);
});

it('allows known social crawlers through VPN block mode', function () {
    config([
        'filament-short-url.vpn_detection.enabled' => true,
        'filament-short-url.vpn_detection.block_action' => 'block_with_403',
    ]);

    $handler = app(ShortUrlRedirectHandler::class);
    $proxyDetector = Mockery::mock(ProxyDetectionService::class);
    $proxyDetector->shouldReceive('detect')->once()->andReturn([
        'is_proxy' => true,
        'is_bot' => true,
    ]);
    app()->instance(ProxyDetectionService::class, $proxyDetector);

    $handler = app(ShortUrlRedirectHandler::class);

    $request = Request::create('/test-key', 'GET', [], [], [], [
        'REMOTE_ADDR' => '8.8.8.8',
        'HTTP_USER_AGENT' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    ]);

    $handler->enforceAccessGuards('test-key', $request);

    expect(true)->toBeTrue();
});

it('blocks human VPN traffic in block mode', function () {
    config([
        'filament-short-url.vpn_detection.enabled' => true,
        'filament-short-url.vpn_detection.block_action' => 'block_with_403',
    ]);

    $proxyDetector = Mockery::mock(ProxyDetectionService::class);
    $proxyDetector->shouldReceive('detect')->once()->andReturn([
        'is_proxy' => true,
        'is_bot' => false,
    ]);
    app()->instance(ProxyDetectionService::class, $proxyDetector);

    $handler = app(ShortUrlRedirectHandler::class);

    $request = Request::create('/test-key', 'GET', [], [], [], [
        'REMOTE_ADDR' => '8.8.8.8',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
    ]);

    $handler->enforceAccessGuards('test-key', $request);
})->throws(HttpException::class);
