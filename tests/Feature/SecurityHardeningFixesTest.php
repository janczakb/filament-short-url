<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('defaults redirect middleware without web group', function () {
    $packageConfig = require dirname(__DIR__, 2).'/config/filament-short-url.php';

    expect($packageConfig['middleware'])->not->toContain('web')
        ->and($packageConfig['middleware'])->toContain('throttle:120,1');

    $redirectRoute = collect(Route::getRoutes())->first(
        fn ($route) => $route->getName() === 'short-url.redirect'
    );

    expect($redirectRoute)->not->toBeNull()
        ->and($redirectRoute->gatherMiddleware())->not->toContain('web');

    $passwordRoute = collect(Route::getRoutes())->first(
        fn ($route) => $route->getName() === 'short-url.password-auth'
    );

    expect($passwordRoute)->not->toBeNull()
        ->and($passwordRoute->gatherMiddleware())->toContain('web');
});

it('scopes api exists checks by domain scope id', function () {
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Test',
            'key' => 'sh_key_exists_scope',
            'is_active' => true,
            'scope' => 'links:read-write',
            'owner_user_id' => 1,
        ]],
    ]);

    ShortUrl::create([
        'destination_url' => 'https://example.com/default',
        'url_key' => 'shared-slug',
        'custom_domain_id' => null,
    ]);

    $domain = ShortUrlCustomDomain::create([
        'domain' => 'links.example.test',
        'is_active' => true,
        'is_verified' => true,
    ]);

    ShortUrl::create([
        'destination_url' => 'https://example.com/custom',
        'url_key' => 'shared-slug',
        'custom_domain_id' => $domain->id,
    ]);

    $headers = ['X-Api-Key' => 'sh_key_exists_scope'];

    $this->getJson('/api/short-url/links/exists?url_key=shared-slug', $headers)
        ->assertOk()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('domain_scope_id', 0);

    $this->getJson('/api/short-url/links/exists?url_key=shared-slug&custom_domain_id='.$domain->id, $headers)
        ->assertOk()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('domain_scope_id', $domain->id);

    $this->getJson('/api/short-url/links/exists?url_key=missing-slug&custom_domain_id='.$domain->id, $headers)
        ->assertOk()
        ->assertJsonPath('exists', false);
});

it('rejects assigning another users custom domain via api', function () {
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Owner A',
            'key' => 'sh_key_owner_a',
            'is_active' => true,
            'scope' => 'links:read-write',
            'owner_user_id' => 10,
        ]],
    ]);

    $foreignDomain = ShortUrlCustomDomain::create([
        'domain' => 'foreign.example.test',
        'user_id' => 99,
        'is_active' => true,
        'is_verified' => true,
    ]);

    $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://example.com',
        'url_key' => 'owned-domain-test',
        'custom_domain_id' => $foreignDomain->id,
    ], ['X-Api-Key' => 'sh_key_owner_a'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['custom_domain_id']);
});

it('caches safe browsing redirect checks', function () {
    config([
        'filament-short-url.safe_browsing.enabled' => true,
        'filament-short-url.safe_browsing.api_key' => 'test-key',
        'filament-short-url.safe_browsing.check_on_redirect' => true,
        'filament-short-url.safe_browsing.redirect_cache_ttl' => 3600,
    ]);

    Http::fake([
        'safebrowsing.googleapis.com/*' => Http::response(['matches' => []], 200),
    ]);

    $service = app(SafeBrowsingService::class);

    expect($service->isSafeCached('https://safe.example.com'))->toBeTrue()
        ->and($service->isSafeCached('https://safe.example.com'))->toBeTrue();

    Http::assertSentCount(1);
});

it('requires webhook signing secret when enabling global webhook in settings', function () {
    config(['filament-short-url.webhook_signing_required' => true]);

    expect(fn () => app(ShortUrlSettingsManager::class)->set([
        'global_webhook_enabled' => true,
        'global_webhook_url' => 'https://hooks.example.com/filament-short-url',
        'webhook_events' => ['visited'],
        'webhook_signing_secret' => null,
    ]))->toThrow(ValidationException::class);
});
