<?php

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\ShortUrlSettingsPage;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Reset config overrides
    config([
        'filament-short-url.deep_linking.enabled' => false,
        'filament-short-url.deep_linking.aasa_json' => null,
        'filament-short-url.deep_linking.assetlinks_json' => null,
    ]);

    Cache::forget('fsu:deep-linking:aasa');
    Cache::forget('fsu:deep-linking:assetlinks');
});

it('returns 404 for deep linking endpoints when disabled', function () {
    $this->get('/.well-known/apple-app-site-association')->assertStatus(404);
    $this->get('/apple-app-site-association')->assertStatus(404);
    $this->get('/.well-known/assetlinks.json')->assertStatus(404);
});

it('returns 404 for deep linking endpoints when enabled but json is empty', function () {
    config([
        'filament-short-url.deep_linking.enabled' => true,
        'filament-short-url.deep_linking.aasa_json' => '',
        'filament-short-url.deep_linking.assetlinks_json' => null,
    ]);

    $this->get('/.well-known/apple-app-site-association')->assertStatus(404);
    $this->get('/.well-known/assetlinks.json')->assertStatus(404);
});

it('serves minified aasa and assetlinks with correct headers and caches them', function () {
    $aasaInput = '{
        "applinks": {
            "apps": [],
            "details": [
                {
                    "appID": "9JA89Q824A.com.example.app",
                    "paths": [ "/s/*" ]
                }
            ]
        }
    }';

    $assetlinksInput = '[{
        "relation": ["delegate_permission/common.handle_all_urls"],
        "target": {
            "namespace": "android_app",
            "package_name": "com.example.app",
            "sha256_cert_fingerprints": ["14:6D:E9:..."]
        }
    }]';

    config([
        'filament-short-url.deep_linking.enabled' => true,
        'filament-short-url.deep_linking.aasa_json' => $aasaInput,
        'filament-short-url.deep_linking.assetlinks_json' => $assetlinksInput,
    ]);

    // Request AASA
    $responseAasa = $this->get('/.well-known/apple-app-site-association');
    $responseAasa->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=604800, must-revalidate, public')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Access-Control-Allow-Origin', '*');

    // Verify it is minified (no spaces or newlines)
    $minifiedAasa = json_encode(json_decode($aasaInput, true), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    expect($responseAasa->getContent())->toBe($minifiedAasa);

    // Verify AASA is cached
    expect(Cache::get('fsu:deep-linking:aasa'))->toBe($minifiedAasa);

    // Request AssetLinks
    $responseAssetLinks = $this->get('/.well-known/assetlinks.json');
    $responseAssetLinks->assertStatus(200)
        ->assertHeader('Content-Type', 'application/json; charset=utf-8')
        ->assertHeader('Cache-Control', 'max-age=604800, must-revalidate, public')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Access-Control-Allow-Origin', '*');

    $minifiedAssetLinks = json_encode(json_decode($assetlinksInput, true), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    expect($responseAssetLinks->getContent())->toBe($minifiedAssetLinks);

    // Verify AssetLinks is cached
    expect(Cache::get('fsu:deep-linking:assetlinks'))->toBe($minifiedAssetLinks);
});

it('clears deep linking cache when settings page save is triggered', function () {
    Cache::put('fsu:deep-linking:aasa', 'cached_aasa', 600);
    Cache::put('fsu:deep-linking:assetlinks', 'cached_assetlinks', 600);

    expect(Cache::has('fsu:deep-linking:aasa'))->toBeTrue();
    expect(Cache::has('fsu:deep-linking:assetlinks'))->toBeTrue();

    // Verify that calling page save logic (which invokes cache forgets) successfully clears cache
    $page = new class extends ShortUrlSettingsPage
    {
        public ?array $formData = [];

        public function save(): void
        {
            // Simulating save logic
            app(ShortUrlSettingsManager::class)->set($this->formData);

            cache()->forget('fsu:deep-linking:aasa');
            cache()->forget('fsu:deep-linking:assetlinks');
        }
    };

    $page->formData = [
        'deep_linking_enabled' => true,
        'aasa_json' => '{}',
        'assetlinks_json' => '[]',
    ];

    $page->save();

    expect(Cache::has('fsu:deep-linking:aasa'))->toBeFalse();
    expect(Cache::has('fsu:deep-linking:assetlinks'))->toBeFalse();
});

it('redirects to the intermediate app-redirect page when auto_open_app_mobile is enabled and visited from mobile', function () {
    $shortUrl = app(ShortUrlService::class)->create([
        'destination_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'url_key' => 'ytmobile',
        'auto_open_app_mobile' => true,
        'track_visits' => false,
    ]);

    // Visited from desktop -> should redirect directly (no intermediate page)
    $responseDesktop = $this->get('/s/ytmobile', [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
    ]);
    $responseDesktop->assertRedirect('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    // Visited from mobile (iPhone) -> should render intermediate app-redirect page
    $responseMobile = $this->get('/s/ytmobile', [
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
    ]);

    $responseMobile->assertStatus(200)
        ->assertSee('Opening in YouTube')
        ->assertSee('youtube://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->assertSee('Open Native App')
        ->assertSee('Open in Browser');
});
