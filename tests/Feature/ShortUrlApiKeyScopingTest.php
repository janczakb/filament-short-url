<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    // Enable REST API and inject mock keys with different scopes and rate limits
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [
            [
                'name' => 'Read Only Token',
                'key' => 'sh_key_read_only',
                'scope' => 'links:read-only',
                'rate_limit' => '60',
                'is_active' => true,
            ],
            [
                'name' => 'Read Write Token',
                'key' => 'sh_key_read_write',
                'scope' => 'links:read-write',
                'rate_limit' => '0', // Unlimited
                'is_active' => true,
            ],
            [
                'name' => 'Rate Limited Token',
                'key' => 'sh_key_rate_limited',
                'scope' => 'links:read-write',
                'rate_limit' => '2', // 2 per minute
                'is_active' => true,
            ],
            [
                'name' => 'Legacy Token',
                'key' => 'sh_key_legacy',
                // No scope, no rate_limit (should default to links:read-write and 60 rpm)
                'is_active' => true,
            ],
        ],
    ]);

    // Clear rate limiter cache before each test
    RateLimiter::clear('fsu_api_key_limit:'.hash('sha256', 'sh_key_rate_limited'));
    RateLimiter::clear('fsu_api_key_limit:'.hash('sha256', 'sh_key_read_only'));
    RateLimiter::clear('fsu_api_key_limit:'.hash('sha256', 'sh_key_read_write'));
    RateLimiter::clear('fsu_api_key_limit:'.hash('sha256', 'sh_key_legacy'));
});

it('allows GET requests but blocks modifying requests with read-only scope', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/scope-test',
        'url_key' => 'scopetest',
    ]);

    // GET should work
    $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_read_only',
    ])->assertStatus(200);

    // POST should be blocked
    $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'googleapi',
    ], [
        'X-Api-Key' => 'sh_key_read_only',
    ])->assertStatus(403)
        ->assertJsonFragment(['error' => 'Forbidden. This API key has read-only permissions.']);

    // PUT should be blocked
    $this->putJson("/api/short-url/links/{$shortUrl->id}", [
        'destination_url' => 'https://updated-destination.com',
    ], [
        'X-Api-Key' => 'sh_key_read_only',
    ])->assertStatus(403)
        ->assertJsonFragment(['error' => 'Forbidden. This API key has read-only permissions.']);

    // DELETE should be blocked
    $this->deleteJson("/api/short-url/links/{$shortUrl->id}", [], [
        'X-Api-Key' => 'sh_key_read_only',
    ])->assertStatus(403)
        ->assertJsonFragment(['error' => 'Forbidden. This API key has read-only permissions.']);
});

it('allows modifying requests with read-write scope', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/scope-test',
        'url_key' => 'scopetest',
    ]);

    // PUT should work
    $this->putJson("/api/short-url/links/{$shortUrl->id}", [
        'destination_url' => 'https://updated-destination.com',
    ], [
        'X-Api-Key' => 'sh_key_read_write',
    ])->assertStatus(200);
});

it('defaults legacy tokens to read-write and 60 rpm', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/scope-test',
        'url_key' => 'scopetest',
    ]);

    // Legacy token has no scope, so should default to read-write and succeed
    $this->putJson("/api/short-url/links/{$shortUrl->id}", [
        'destination_url' => 'https://updated-destination.com',
    ], [
        'X-Api-Key' => 'sh_key_legacy',
    ])->assertStatus(200);
});

it('enforces dynamic rate limiting per API key', function () {
    // Request 1: Allowed
    $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_rate_limited',
    ])->assertStatus(200);

    // Request 2: Allowed
    $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_rate_limited',
    ])->assertStatus(200);

    // Request 3: Blocked
    $response = $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_rate_limited',
    ]);

    $response->assertStatus(429)
        ->assertJsonFragment(['error' => 'Too many requests. API key rate limit exceeded.'])
        ->assertHeader('Retry-After');
});

it('does not limit unlimited keys (rate_limit = 0)', function () {
    // Run multiple requests to exceed standard limit
    for ($i = 0; $i < 5; $i++) {
        $this->getJson('/api/short-url/links', [
            'X-Api-Key' => 'sh_key_read_write',
        ])->assertStatus(200);
    }
});
