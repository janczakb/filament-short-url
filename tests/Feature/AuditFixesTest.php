<?php

use Bjanczak\FilamentShortUrl\Jobs\SendWebhookJob;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Bjanczak\FilamentShortUrl\Services\BotDetector;
use Bjanczak\FilamentShortUrl\Services\ShortUrlPasswordHasher;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['filament-short-url.scope_links_to_user' => false]);

    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [
            ['name' => 'Test', 'key' => 'sh_key_active_token', 'is_active' => true],
        ],
    ]);
});

it('hashes passwords on save and verifies them', function () {
    $shortUrl = app(ShortUrlService::class)->create([
        'destination_url' => 'https://example.com',
        'url_key' => 'hashpw',
        'password' => 'plain-secret',
    ]);

    expect($shortUrl->password)->not->toBe('plain-secret')
        ->and(app(ShortUrlPasswordHasher::class)->isHashed($shortUrl->password))->toBeTrue()
        ->and($shortUrl->verifyPassword('plain-secret'))->toBeTrue()
        ->and($shortUrl->verifyPassword('wrong'))->toBeFalse();
});

it('does not expose password hash in API responses', function () {
    $shortUrl = app(ShortUrlService::class)->create([
        'destination_url' => 'https://example.com',
        'url_key' => 'apipw',
        'password' => 'secret',
    ]);

    $response = $this->getJson('/api/short-url/links/'.$shortUrl->id, [
        'X-Api-Key' => 'sh_key_active_token',
    ]);

    $response->assertOk()
        ->assertJsonPath('password_protected', true)
        ->assertJsonMissingPath('password');
});

it('blocks webhook delivery to disallowed urls', function () {
    Queue::fake();

    Http::fake();

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'whblock',
        'webhook_url' => 'http://127.0.0.1/hook',
    ]);

    $shortUrl->dispatchWebhook('created');

    Queue::assertNothingPushed();
});

it('applies x-robots-tag on password prompt when do_index is false', function () {
    app(ShortUrlService::class)->create([
        'destination_url' => 'https://example.com',
        'url_key' => 'robotpw',
        'password' => 'secret',
        'do_index' => false,
        'track_visits' => false,
    ]);

    $this->get('/s-auth/robotpw')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('does not track bot visits', function () {
    app(ShortUrlService::class)->create([
        'destination_url' => 'https://example.com',
        'url_key' => 'bottrack',
        'track_visits' => true,
    ]);

    $this->get('/s/bottrack', ['User-Agent' => 'facebookexternalhit/1.1'])
        ->assertStatus(302);

    expect(ShortUrlVisit::count())->toBe(0);
});

it('rejects bot query spoofing outside debug environments', function () {
    $original = app()->environment();

    try {
        app()->instance('env', 'production');

        $detector = app(BotDetector::class);
        $request = Request::create('/s/test?bot=1', 'GET');

        expect($detector->isBot($request))->toBeFalse();
    } finally {
        app()->instance('env', $original);
    }
});

it('lists visits via API', function () {
    $shortUrl = app(ShortUrlService::class)->create([
        'destination_url' => 'https://example.com',
        'url_key' => 'visitsapi',
    ]);

    ShortUrlVisit::create([
        'short_url_id' => $shortUrl->id,
        'visited_at' => now(),
        'ip_hash' => 'abc',
        'referer_host' => 'Direct',
    ]);

    $this->getJson('/api/short-url/links/'.$shortUrl->id.'/visits', [
        'X-Api-Key' => 'sh_key_active_token',
    ])
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('bulk deletes links via API', function () {
    $a = app(ShortUrlService::class)->create(['destination_url' => 'https://a.test', 'url_key' => 'bulk-a']);
    $b = app(ShortUrlService::class)->create(['destination_url' => 'https://b.test', 'url_key' => 'bulk-b']);

    $this->postJson('/api/short-url/links/bulk-delete', [
        'ids' => [$a->id, $b->id],
    ], ['X-Api-Key' => 'sh_key_active_token'])
        ->assertOk()
        ->assertJsonPath('deleted', 2);

    expect(ShortUrl::count())->toBe(0);
});

it('dispatches webhook job only for allowed urls', function () {
    Queue::fake([SendWebhookJob::class]);

    config(['filament-short-url.webhook_events' => ['created']]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'whok',
        'webhook_url' => 'https://example.com/receive',
    ]);

    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $shortUrl->dispatchWebhook('created');

    Queue::assertPushed(SendWebhookJob::class);
});
