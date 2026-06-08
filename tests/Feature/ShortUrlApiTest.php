<?php

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlPixel;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['filament-short-url.scope_links_to_user' => false]);

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

    config(['filament-short-url.webhook_events' => ['created']]);

    $pixel = ShortUrlPixel::create([
        'name' => 'Meta Pixel Test',
        'type' => 'meta',
        'pixel_id' => '12345',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'googleapi',
        'notes' => 'API Generated link',
        'single_use' => true,
        'pixels' => [$pixel->id],
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

    $shortUrl = ShortUrl::where('url_key', 'googleapi')->first();
    $this->assertDatabaseHas('short_url_pixel', [
        'short_url_id' => $shortUrl->id,
        'pixel_id' => $pixel->id,
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

it('allows updating a short link via PUT', function () {
    $link = ShortUrl::create([
        'destination_url' => 'https://initial-destination.com',
        'url_key' => 'initkey',
        'notes' => 'Initial notes',
    ]);

    $response = $this->putJson("/api/short-url/links/{$link->id}", [
        'destination_url' => 'https://updated-destination.com',
        'notes' => 'Updated notes',
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['destination_url' => 'https://updated-destination.com'])
        ->assertJsonFragment(['notes' => 'Updated notes'])
        ->assertJsonFragment(['url_key' => 'initkey']);

    $this->assertDatabaseHas('short_urls', [
        'id' => $link->id,
        'destination_url' => 'https://updated-destination.com',
        'notes' => 'Updated notes',
        'url_key' => 'initkey',
    ]);
});

it('allows showing a single short link via GET by ID or key', function () {
    $link = ShortUrl::create([
        'destination_url' => 'https://single-show.com',
        'url_key' => 'showkey',
    ]);

    // Show by ID
    $response = $this->getJson("/api/short-url/links/{$link->id}", [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['url_key' => 'showkey']);

    // Show by Key
    $responseKey = $this->getJson('/api/short-url/links/showkey', [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $responseKey->assertStatus(200)
        ->assertJsonFragment(['id' => $link->id]);
});

it('allows fetching link statistics via GET', function () {
    $link = ShortUrl::create([
        'destination_url' => 'https://stats-test.com',
        'url_key' => 'statskey',
    ]);

    $response = $this->getJson("/api/short-url/links/{$link->id}/stats", [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'totalVisits',
                'uniqueVisits',
                'visitsToday',
                'visitsByDay',
            ],
        ]);
});

it('validates new targeting rules in API payload', function () {
    // 1. Empty filters array validation
    $responseEmpty = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'rule_empty',
        'targeting_rules' => [
            [
                'match' => 'or',
                'url' => 'https://mobile.com',
                'filters' => [], // Empty filters array, should fail
            ],
        ],
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $responseEmpty->assertStatus(422)
        ->assertJsonValidationErrors(['targeting_rules.0.filters']);

    // 2. Duplicate filter type validation
    $responseDuplicate = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'rule_dup',
        'targeting_rules' => [
            [
                'match' => 'or',
                'url' => 'https://mobile.com',
                'filters' => [
                    [
                        'type' => 'device',
                        'data' => ['devices' => ['mobile']],
                    ],
                    [
                        'type' => 'device', // Duplicate filter type, should fail
                        'data' => ['devices' => ['desktop']],
                    ],
                ],
            ],
        ],
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $responseDuplicate->assertStatus(422)
        ->assertJsonValidationErrors(['targeting_rules.0.filters']);

    // 3. Valid rules should succeed
    $responseValid = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'rule_valid',
        'targeting_rules' => [
            [
                'match' => 'or',
                'url' => 'https://mobile.com',
                'filters' => [
                    [
                        'type' => 'device',
                        'data' => ['devices' => ['mobile']],
                    ],
                    [
                        'type' => 'platform',
                        'data' => ['platforms' => ['ios']],
                    ],
                ],
            ],
        ],
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $responseValid->assertStatus(201);

    // 4. Extraneous key in targeting rules validation
    $responseExtraRuleKey = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'rule_extra_key',
        'targeting_rules' => [
            [
                'match' => 'or',
                'url' => 'https://mobile.com',
                'random_junk_key' => 'garbage', // extraneous key, should fail
                'filters' => [
                    [
                        'type' => 'device',
                        'data' => ['devices' => ['mobile']],
                    ],
                ],
            ],
        ],
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $responseExtraRuleKey->assertStatus(422)
        ->assertJsonValidationErrors(['targeting_rules']);

    // 5. Extraneous key in filters validation
    $responseExtraFilterKey = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'filter_extra_key',
        'targeting_rules' => [
            [
                'match' => 'or',
                'url' => 'https://mobile.com',
                'filters' => [
                    [
                        'type' => 'device',
                        'data' => ['devices' => ['mobile']],
                        'random_junk_key' => 'garbage', // extraneous key, should fail
                    ],
                ],
            ],
        ],
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $responseExtraFilterKey->assertStatus(422)
        ->assertJsonValidationErrors(['targeting_rules.0.filters']);

    // 6. Invalid values in targeting rules (e.g. invalid country code and invalid device)
    $responseInvalidValues = $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://google.com',
        'url_key' => 'rule_invalid_values',
        'targeting_rules' => [
            [
                'match' => 'or',
                'url' => 'https://mobile.com',
                'filters' => [
                    [
                        'type' => 'device',
                        'data' => ['devices' => ['microwave']], // invalid device type
                    ],
                ],
            ],
        ],
    ], [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $responseInvalidValues->assertStatus(422)
        ->assertJsonValidationErrors(['targeting_rules.0.filters.0.data.devices.0']);
});
