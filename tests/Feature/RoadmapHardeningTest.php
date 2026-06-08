<?php

use App\Models\User;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Policies\ShortUrlPolicy;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['filament-short-url.scope_links_to_user' => false]);
});

if (! function_exists('roadmapCreateLink')) {
    function roadmapCreateLink(array $attrs = []): ShortUrl
    {
        return app(ShortUrlService::class)->create(array_merge([
            'destination_url' => 'https://example.com',
        ], $attrs));
    }
}

if (! function_exists('roadmapCreateUser')) {
    function roadmapCreateUser(): User
    {
        return User::factory()->create();
    }
}

it('registers ShortUrlPolicy and enforces user ownership when scoped', function () {
    config(['filament-short-url.scope_links_to_user' => true]);

    expect(Gate::getPolicyFor(ShortUrl::class))->toBeInstanceOf(ShortUrlPolicy::class);

    $owner = roadmapCreateUser();
    $other = roadmapCreateUser();

    $link = roadmapCreateLink([
        'url_key' => 'policy-owned',
        'user_id' => $owner->id,
    ]);

    expect(Gate::forUser($owner)->allows('view', $link))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('update', $link))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $link))->toBeTrue()
        ->and(Gate::forUser($other)->allows('view', $link))->toBeFalse()
        ->and(Gate::forUser($other)->allows('update', $link))->toBeFalse()
        ->and(Gate::forUser($other)->allows('delete', $link))->toBeFalse();
});

it('allows all authenticated actions when link scoping is disabled', function () {
    config(['filament-short-url.scope_links_to_user' => false]);

    $owner = roadmapCreateUser();
    $other = roadmapCreateUser();

    $link = roadmapCreateLink([
        'url_key' => 'policy-shared',
        'user_id' => $owner->id,
    ]);

    expect(Gate::forUser($other)->allows('view', $link))->toBeTrue()
        ->and(Gate::forUser($other)->allows('update', $link))->toBeTrue();
});

it('returns filtered stats from GET links stats with panel-compatible filters', function () {
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Stats',
            'key' => 'sh_key_stats_filters',
            'is_active' => true,
            'scope' => 'links:read',
        ]],
    ]);

    $link = roadmapCreateLink(['url_key' => 'api-stats-filter']);

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

    $response = $this->getJson(
        '/api/short-url/links/'.$link->id.'/stats?country_code=PL',
        ['X-Api-Key' => 'sh_key_stats_filters']
    );

    $response->assertOk()
        ->assertJsonPath('meta.filters.country_code', 'PL')
        ->assertJsonPath('data.totalVisits', 1)
        ->assertJsonPath('data.visitsByCountry.PL', 1);
});

it('maps device alias onto device_type filter in stats api', function () {
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Stats device alias',
            'key' => 'sh_key_stats_device_alias',
            'is_active' => true,
            'scope' => 'links:read',
        ]],
    ]);

    $link = roadmapCreateLink(['url_key' => 'api-stats-device']);

    $link->visits()->create([
        'ip_address' => '1.1.1.1',
        'ip_hash' => hash('sha256', '1.1.1.1'),
        'country_code' => 'US',
        'device_type' => 'mobile',
        'visited_at' => now(),
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $this->getJson(
        '/api/short-url/links/'.$link->id.'/stats?device=mobile',
        ['X-Api-Key' => 'sh_key_stats_device_alias']
    )
        ->assertOk()
        ->assertJsonPath('meta.filters.device_type', 'mobile')
        ->assertJsonPath('data.totalVisits', 1);
});

it('accepts country alias on stats api', function () {
    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Stats aliases',
            'key' => 'sh_key_stats_alias',
            'is_active' => true,
            'scope' => 'links:read',
        ]],
    ]);

    $link = roadmapCreateLink(['url_key' => 'api-stats-alias']);

    $link->visits()->create([
        'ip_address' => '1.1.1.1',
        'ip_hash' => hash('sha256', '1.1.1.1'),
        'country_code' => 'US',
        'device_type' => 'mobile',
        'visited_at' => now(),
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $this->getJson(
        '/api/short-url/links/'.$link->id.'/stats?country=US&device=mobile',
        ['X-Api-Key' => 'sh_key_stats_alias']
    )
        ->assertOk()
        ->assertJsonPath('meta.filters.country_code', 'US')
        ->assertJsonPath('meta.filters.device_type', 'mobile')
        ->assertJsonPath('data.totalVisits', 1);
});

it('delivers visit webhooks with verifiable HMAC signature over HTTP fake', function () {
    Http::fake([
        'https://webhook.site/*' => Http::response(['ok' => true], 200),
    ]);

    app(ShortUrlSettingsManager::class)->set([
        'webhook_signing_secret' => 'roadmap-hmac-secret',
        'webhook_events' => ['visited'],
        'queue_connection' => 'sync',
    ]);

    config([
        'filament-short-url.webhook_events' => ['visited'],
        'filament-short-url.webhook_signing_secret' => 'roadmap-hmac-secret',
        'filament-short-url.queue_connection' => 'sync',
    ]);

    cache()->forget('filament-short-url:settings');

    roadmapCreateLink([
        'url_key' => 'webhook-hmac-e2e',
        'track_visits' => true,
        'webhook_url' => 'https://webhook.site/roadmap-hmac',
    ]);

    $this->get('/s/webhook-hmac-e2e', [
        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ])->assertRedirect('https://example.com');

    Http::assertSent(function ($request) {
        expect($request->hasHeader('X-ShortUrl-Signature'))->toBeTrue();

        $payload = $request->data();
        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac('sha256', $payloadJson, 'roadmap-hmac-secret');

        expect($request->header('X-ShortUrl-Signature')[0])->toBe($expected)
            ->and($payload['event'] ?? null)->toBe('visited');

        return true;
    });
});

it('does not allow more successful redirects than max_visits during a burst', function () {
    config([
        'filament-short-url.queue_connection' => 'sync',
        'filament-short-url.cache_ttl' => 0,
    ]);

    roadmapCreateLink([
        'url_key' => 'burst-max-visits',
        'max_visits' => 3,
        'track_visits' => true,
    ]);

    $redirects = 0;
    $blocked = 0;

    for ($i = 0; $i < 8; $i++) {
        $response = $this->get('/s/burst-max-visits');

        if ($response->isRedirect()) {
            $redirects++;
        } elseif ($response->status() === 410) {
            $blocked++;
        }
    }

    expect($redirects)->toBeLessThanOrEqual(3)
        ->and($redirects + $blocked)->toBe(8);
});

it('runs the stress redirect artisan command for baseline timing', function () {
    roadmapCreateLink(['url_key' => 'stress-key', 'track_visits' => false]);

    Artisan::call('short-url:stress-redirect', [
        'key' => 'stress-key',
        '--requests' => 5,
        '--warmup' => 1,
    ]);

    expect(Artisan::output())->toContain('Measured requests');
});

it('ships a k6 redirect baseline script for http load testing', function () {
    $scriptPath = dirname(__DIR__, 2).'/scripts/k6/redirect-baseline.js';

    expect(file_exists($scriptPath))->toBeTrue()
        ->and(file_get_contents($scriptPath))->toContain('BASE_URL')
        ->and(file_get_contents($scriptPath))->toContain('redirect_duration');
});

it('streams live feed for authorized viewers', function () {
    config(['filament-short-url.live_feed.sse_max_duration_seconds' => 0]);

    $owner = roadmapCreateUser();
    $link = roadmapCreateLink([
        'url_key' => 'sse-auth',
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)
        ->get(route('short-url.live-feed.stream', ['shortUrl' => $link->id]));

    $response->assertOk();

    expect(str_starts_with((string) $response->headers->get('Content-Type'), 'text/event-stream'))->toBeTrue();
});

it('denies live feed sse stream for links owned by another user when scoped', function () {
    config([
        'filament-short-url.scope_links_to_user' => true,
        'filament-short-url.live_feed.sse_max_duration_seconds' => 0,
    ]);

    $owner = roadmapCreateUser();
    $other = roadmapCreateUser();

    $link = roadmapCreateLink([
        'url_key' => 'sse-scope',
        'user_id' => $owner->id,
    ]);

    $this->actingAs($other)
        ->get(route('short-url.live-feed.stream', ['shortUrl' => $link->id]))
        ->assertForbidden();
});
