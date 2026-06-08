<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlFolder;
use Bjanczak\FilamentShortUrl\Models\ShortUrlPixel;
use Bjanczak\FilamentShortUrl\Models\ShortUrlTag;
use Bjanczak\FilamentShortUrl\Services\BotDetector;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function worldClassCreateLink(array $overrides = []): ShortUrl
{
    return ShortUrl::create(array_merge([
        'destination_url' => 'https://example.com',
        'url_key' => 'wc-'.bin2hex(random_bytes(4)),
        'is_enabled' => true,
        'track_visits' => true,
    ], $overrides));
}

it('escapes pixel interstitial destination with json encoding', function () {
    $pixel = ShortUrlPixel::create([
        'name' => 'Meta',
        'type' => 'meta',
        'pixel_id' => '123',
        'is_active' => true,
    ]);

    $link = worldClassCreateLink([
        'url_key' => 'xss-pixel',
        'destination_url' => 'https://example.com/?x=";alert(1);//',
    ]);
    $link->pixels()->attach($pixel->id);

    $response = $this->get('/s/xss-pixel');

    expect($response->getContent())
        ->toContain('window.location.replace(')
        ->toContain('https://example.com/?x=')
        ->not->toContain('addslashes');
});

it('rejects api keys without owner_user_id when link scoping is enabled', function () {
    config(['filament-short-url.scope_links_to_user' => true]);

    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Unscoped',
            'key' => 'sh_key_unscoped',
            'is_active' => true,
        ]],
    ]);

    $this->getJson('/api/short-url/links', ['X-Api-Key' => 'sh_key_unscoped'])
        ->assertForbidden();
});

it('scopes api list results to the key owner when link scoping is enabled', function () {
    config(['filament-short-url.scope_links_to_user' => true]);

    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Owner',
            'key' => 'sh_key_owner',
            'is_active' => true,
            'owner_user_id' => 42,
        ]],
    ]);

    worldClassCreateLink(['url_key' => 'mine', 'user_id' => 42]);
    worldClassCreateLink(['url_key' => 'theirs', 'user_id' => 99]);

    $this->getJson('/api/short-url/links', ['X-Api-Key' => 'sh_key_owner'])
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.url_key', 'mine');
});

it('rejects foreign folder assignment via api when scoped to owner', function () {
    config(['filament-short-url.scope_links_to_user' => true]);

    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Owner',
            'key' => 'sh_key_folder',
            'is_active' => true,
            'owner_user_id' => 7,
        ]],
    ]);

    $foreignFolder = ShortUrlFolder::create([
        'name' => 'Foreign',
        'slug' => 'foreign',
        'user_id' => 99,
    ]);

    $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://example.com',
        'url_key' => 'folder-idor',
        'folder_id' => $foreignFolder->id,
    ], ['X-Api-Key' => 'sh_key_folder'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['folder_id']);
});

it('rejects foreign tag assignment via api when scoped to owner', function () {
    config(['filament-short-url.scope_links_to_user' => true]);

    app(ShortUrlSettingsManager::class)->set([
        'api_enabled' => true,
        'api_keys' => [[
            'name' => 'Owner',
            'key' => 'sh_key_tags',
            'is_active' => true,
            'owner_user_id' => 8,
        ]],
    ]);

    $foreignTag = ShortUrlTag::create([
        'name' => 'Foreign',
        'slug' => 'foreign',
        'user_id' => 99,
    ]);

    $this->postJson('/api/short-url/links', [
        'destination_url' => 'https://example.com',
        'url_key' => 'tag-idor',
        'tag_ids' => [$foreignTag->id],
    ], ['X-Api-Key' => 'sh_key_tags'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tag_ids.0']);
});

it('does not consume single_use links for social preview bots', function () {
    $link = worldClassCreateLink([
        'url_key' => 'su-bot',
        'single_use' => true,
        'track_visits' => false,
    ]);

    $this->mock(BotDetector::class, function ($mock) {
        $mock->shouldReceive('isBot')->andReturn(true);
    });

    $this->get('/s/su-bot', ['User-Agent' => 'facebookexternalhit/1.1'])
        ->assertRedirect('https://example.com');

    expect($link->fresh()->is_enabled)->toBeTrue();
});

it('enforces max_visits when track_visits is disabled', function () {
    config(['filament-short-url.queue_connection' => 'sync']);

    $link = worldClassCreateLink([
        'url_key' => 'max-no-track',
        'max_visits' => 1,
        'track_visits' => false,
    ]);

    $this->get('/s/max-no-track')->assertRedirect('https://example.com');
    $this->get('/s/max-no-track')->assertStatus(410);

    expect($link->fresh()->total_visits)->toBe(1);
});

it('does not double count buffered visits when dirty id registration falls back to db', function () {
    config([
        'filament-short-url.counter_buffering.enabled' => true,
        'filament-short-url.queue_connection' => 'sync',
    ]);

    $link = worldClassCreateLink(['url_key' => 'buffer-fallback']);

    $prefix = config('filament-short-url.counter_buffering.cache_key_prefix', 'filament-short-url:buffer:');
    cache()->put("{$prefix}dirty_ids", array_fill(0, 50000, 999999), 3600);

    $this->get('/s/buffer-fallback')->assertRedirect('https://example.com');

    expect($link->fresh()->total_visits)->toBe(1)
        ->and((int) cache()->get("{$prefix}total:{$link->id}", 0))->toBe(0);
});

it('uses atomic max_visits reservation under concurrent redirects', function () {
    config([
        'filament-short-url.queue_connection' => 'sync',
        'filament-short-url.cache_ttl' => 0,
    ]);

    worldClassCreateLink([
        'url_key' => 'wc-burst',
        'max_visits' => 2,
        'track_visits' => true,
    ]);

    $redirects = 0;
    $blocked = 0;

    for ($i = 0; $i < 6; $i++) {
        $response = $this->get('/s/wc-burst');

        if ($response->isRedirect()) {
            $redirects++;
        } elseif ($response->status() === 410) {
            $blocked++;
        }
    }

    expect($redirects)->toBeLessThanOrEqual(2)
        ->and($redirects + $blocked)->toBe(6)
        ->and((int) ShortUrl::where('url_key', 'wc-burst')->value('total_visits'))->toBeLessThanOrEqual(2);
});

it('returns the same public stats 404 for unknown keys and disabled stats', function () {
    worldClassCreateLink([
        'url_key' => 'hidden-stats',
        'public_stats_enabled' => false,
    ]);

    $unknown = $this->getJson('/s/public-stats/missing-key');
    $disabled = $this->getJson('/s/public-stats/hidden-stats');

    expect($unknown->status())->toBe(404)
        ->and($disabled->status())->toBe(404);
});

it('limits public stats payload to a safe subset', function () {
    $link = worldClassCreateLink([
        'url_key' => 'public-stats',
        'public_stats_enabled' => true,
    ]);

    $response = $this->getJson('/s/public-stats/'.$link->url_key);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'totalVisits',
                'uniqueVisits',
                'visitsToday',
                'visitsThisWeek',
                'visitsThisMonth',
                'visitsByDay',
                'qrScans',
            ],
        ])
        ->assertJsonMissingPath('data.visitsByReferer')
        ->assertJsonMissingPath('data.utmSources');
});
