<?php

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Enable REST API and inject mock keys
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [
            ['name' => 'Active Test Token', 'key' => 'sh_key_active_token', 'is_active' => true],
            ['name' => 'Inactive Test Token', 'key' => 'sh_key_inactive_token', 'is_active' => false],
        ],
    ]);
});

it('rejects API requests without a key', function () {
    $response = $this->getJson('/api/short-url/links');

    $response->assertStatus(401)
        ->assertJsonFragment(['error' => 'Unauthorized. API Key is missing.']);
});

it('returns 503 when the REST API is disabled in settings', function () {
    app(ShortUrlSettingsManager::class)->set(['api_enabled' => false]);

    $response = $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertStatus(503)
        ->assertJsonPath('error', fn ($v) => str_contains($v, 'disabled'));
});

it('rejects API requests with an invalid key', function () {
    $response = $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_invalid_value',
    ]);

    $response->assertStatus(401)
        ->assertJsonFragment(['error' => 'Unauthorized. Invalid or inactive API Key.']);
});

it('rejects API requests with an inactive key', function () {
    $response = $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_inactive_token',
    ]);

    $response->assertStatus(401)
        ->assertJsonFragment(['error' => 'Unauthorized. Invalid or inactive API Key.']);
});

it('allows API requests with a valid key', function () {
    // Seed at least one short URL
    ShortUrl::create([
        'destination_url' => 'https://example.com/api-test',
        'url_key' => 'apitest',
    ]);

    $response = $this->getJson('/api/short-url/links', [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonFragment(['url_key' => 'apitest']);
});

it('allows creating a short link programmatically via POST', function () {
    Queue::fake([SendWebhookJob::class]);

    $response = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'googleapi',
        'notes' => 'API Generated link',
        'single_use' => true,
        'pixel_meta_id' => '12345',
        'webhook_url' => 'https://webhook.site/test',
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['url_key' => 'googleapi'])
        ->assertJsonFragment(['notes' => 'API Generated link']);

    $this->assertDatabaseHas('short_urls', [
        'url_key' => 'googleapi',
        'single_use' => true,
        'webhook_url' => 'https://webhook.site/test',
    ]);

    $this->assertDatabaseHas('short_url_pixels', [
        'type' => 'meta',
        'pixel_id' => '12345',
    ]);

    // SendWebhookJob should be dispatched for 'created' event since custom webhook_url is set
    Queue::assertPushed(SendWebhookJob::class, function ($job) {
        return $job->url === 'https://webhook.site/test' && $job->event === 'created';
    });
});

it('allows deleting a short link via DELETE', function () {
    $link = ShortUrl::create([
        'destination_url' => 'https://delete-me.com',
        'url_key' => 'delkey',
    ]);

    $response = $this->deleteJson("/api/short-url/links/{$link->id}", [], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['message' => 'Short URL deleted successfully.']);

    $this->assertDatabaseMissing('short_urls', [
        'id' => $link->id,
    ]);
});
