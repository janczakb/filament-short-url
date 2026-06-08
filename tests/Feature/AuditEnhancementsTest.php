<?php

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlFolder;
use Bjanczak\FilamentShortUrl\Models\ShortUrlTag;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\OutboundUrlValidator;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Bjanczak\FilamentShortUrl\Services\UrlMetaScraper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'global_webhook_enabled' => false,
        'global_webhook_url' => 'https://example.com/hook',
        'webhook_events' => ['visited'],
        'api_keys' => [
            [
                'name' => 'Test',
                'key' => 'sh_key_active_token',
                'is_active' => true,
                'scope' => 'links:read-write',
            ],
        ],
    ]);
});

it('resolves hostnames to public ips for outbound urls', function () {
    $scraper = app(UrlMetaScraper::class);

    expect($scraper->isAllowedOutboundUrl('http://127.0.0.1/hook'))->toBeFalse()
        ->and($scraper->isAllowedOutboundUrl('http://localhost/hook'))->toBeFalse();
});

it('does not dispatch global webhook when disabled in settings', function () {
    Queue::fake();

    config([
        'filament-short-url.global_webhook_enabled' => false,
        'filament-short-url.global_webhook_url' => 'https://example.com/global-hook',
        'filament-short-url.webhook_events' => ['visited'],
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'gwh-off',
    ]);

    $shortUrl->dispatchWebhook('visited');

    Queue::assertNothingPushed();
});

it('dispatches global webhook when enabled in settings', function () {
    Queue::fake();

    config([
        'filament-short-url.global_webhook_enabled' => true,
        'filament-short-url.global_webhook_url' => 'https://example.com/global-hook',
        'filament-short-url.webhook_events' => ['visited'],
    ]);

    Http::fake(['*' => Http::response([], 200)]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'gwh-on',
    ]);

    $shortUrl->dispatchWebhook('visited');

    Queue::assertPushed(SendWebhookJob::class);
});

it('creates links via bulk API endpoint', function () {
    $response = $this->postJson('/api/short-url/links/bulk', [
        'links' => [
            ['destination_url' => 'https://a.com', 'url_key' => 'bulk-a'],
            ['destination_url' => 'https://b.com', 'url_key' => 'bulk-b'],
        ],
    ], ['X-Api-Key' => 'sh_key_active_token']);

    $response->assertCreated()
        ->assertJsonCount(2, 'data');

    $this->assertDatabaseHas('short_urls', ['url_key' => 'bulk-a']);
    $this->assertDatabaseHas('short_urls', ['url_key' => 'bulk-b']);
});

it('upserts links by external id', function () {
    ShortUrl::create([
        'destination_url' => 'https://old.com',
        'url_key' => 'upsert1',
        'external_id' => 'crm-123',
    ]);

    $response = $this->putJson('/api/short-url/links/upsert', [
        'external_id' => 'crm-123',
        'destination_url' => 'https://new.com',
        'url_key' => 'upsert1',
        'notes' => 'Updated via upsert',
    ], ['X-Api-Key' => 'sh_key_active_token']);

    $response->assertOk()
        ->assertJsonFragment(['notes' => 'Updated via upsert']);

    $this->assertDatabaseHas('short_urls', [
        'external_id' => 'crm-123',
        'destination_url' => 'https://new.com',
    ]);
});

it('reports slug availability via exists endpoint', function () {
    ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'taken-key',
    ]);

    $this->getJson('/api/short-url/links/exists?url_key=taken-key', [
        'X-Api-Key' => 'sh_key_active_token',
    ])->assertOk()->assertJson(['exists' => true, 'url_key' => 'taken-key']);

    $this->getJson('/api/short-url/links/exists?url_key=free-key', [
        'X-Api-Key' => 'sh_key_active_token',
    ])->assertOk()->assertJson(['exists' => false, 'url_key' => 'free-key']);
});

it('merges link-level utm params into destination on redirect', function () {
    $shortUrl = app(ShortUrlService::class)->create([
        'destination_url' => 'https://example.com/page',
        'url_key' => 'utm-merge',
        'utm_source' => 'newsletter',
        'utm_medium' => 'email',
    ]);

    $resolved = app(ShortUrlService::class)->resolveRedirectUrl($shortUrl, Request::create('/s/utm-merge', 'GET'));

    expect($resolved)->toContain('utm_source=newsletter')
        ->and($resolved)->toContain('utm_medium=email');
});

it('scopes API listing to owner user id on the key', function () {
    config(['filament-short-url.scope_links_to_user' => true]);

    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [
            [
                'name' => 'Scoped',
                'key' => 'sh_key_scoped',
                'is_active' => true,
                'owner_user_id' => 7,
            ],
        ],
    ]);

    ShortUrl::create(['destination_url' => 'https://mine.com', 'url_key' => 'mine', 'user_id' => 7]);
    ShortUrl::create(['destination_url' => 'https://other.com', 'url_key' => 'other', 'user_id' => 99]);

    $response = $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_scoped',
    ]);

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('url_key')->all())->toBe(['mine']);
});

it('exposes public stats when enabled', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'public1',
        'public_stats_enabled' => true,
        'track_visits' => false,
    ]);

    $this->getJson('/s/public-stats/'.$shortUrl->url_key)
        ->assertOk()
        ->assertJsonStructure(['data' => ['totalVisits', 'uniqueVisits']]);
});

it('creates tags and folders via API', function () {
    $tagResponse = $this->postJson('/api/short-url/tags', [
        'name' => 'Campaign',
        'color' => 'blue',
    ], ['X-Api-Key' => 'sh_key_active_token']);

    $tagResponse->assertCreated();

    $folderResponse = $this->postJson('/api/short-url/folders', [
        'name' => 'Marketing',
    ], ['X-Api-Key' => 'sh_key_active_token']);

    $folderResponse->assertCreated();

    expect(ShortUrlTag::count())->toBe(1)
        ->and(ShortUrlFolder::count())->toBe(1);
});

it('blocks outbound webhook urls that fail dns validation', function () {
    expect(app(OutboundUrlValidator::class)->isAllowed('http://127.0.0.1/private'))->toBeFalse();
});

it('does not track visit when single-use link was already consumed', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'single2',
        'single_use' => true,
        'is_enabled' => false,
        'track_visits' => true,
    ]);

    $this->get('/s/single2')->assertStatus(410);
    expect(ShortUrlVisit::where('short_url_id', $shortUrl->id)->count())->toBe(0);
});
