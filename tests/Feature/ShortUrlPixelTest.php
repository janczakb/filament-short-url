<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlPixel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the pixel-loading interstitial view when pixels are set', function () {
    $link = ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'pixelkey',
    ]);

    $metaPixel = ShortUrlPixel::create([
        'name' => 'My Meta Pixel',
        'type' => 'meta',
        'pixel_id' => 'META-12345',
        'is_active' => true,
    ]);

    $googlePixel = ShortUrlPixel::create([
        'name' => 'My Google Tag',
        'type' => 'google',
        'pixel_id' => 'G-GA54321',
        'is_active' => true,
    ]);

    $linkedinPixel = ShortUrlPixel::create([
        'name' => 'My LinkedIn Tag',
        'type' => 'linkedin',
        'pixel_id' => 'LNK-98765',
        'is_active' => true,
    ]);

    $tiktokPixel = ShortUrlPixel::create([
        'name' => 'My TikTok Tag',
        'type' => 'tiktok',
        'pixel_id' => 'TT-65432',
        'is_active' => true,
    ]);

    $pinterestPixel = ShortUrlPixel::create([
        'name' => 'My Pinterest Tag',
        'type' => 'pinterest',
        'pixel_id' => 'PIN-78901',
        'is_active' => true,
    ]);

    $link->pixels()->sync([
        $metaPixel->id,
        $googlePixel->id,
        $linkedinPixel->id,
        $tiktokPixel->id,
        $pinterestPixel->id,
    ]);

    $response = $this->get('/s/pixelkey');

    $response->assertStatus(200);
    $response->assertViewIs('filament-short-url::pixel-loading');

    // Assert Meta Pixel is rendered
    $response->assertSee("fbq('init', 'META-12345')", false);

    // Assert Google Analytics is rendered
    $response->assertSee('https://www.googletagmanager.com/gtag/js?id=G-GA54321', false);
    $response->assertSee("gtag('config', 'G-GA54321')", false);

    // Assert LinkedIn Insight is rendered
    $response->assertSee('window._linkedin_data_partner_ids.push("LNK-98765")', false);

    // Assert TikTok Pixel is rendered
    $response->assertSee("ttq.load('TT-65432')", false);

    // Assert Pinterest Tag is rendered
    $response->assertSee("pintrk('load', 'PIN-78901')", false);
});

it('bypasses interstitial loading when no pixels are set', function () {
    $link = ShortUrl::create([
        'destination_url' => 'https://example.com/direct',
        'url_key' => 'directkey',
    ]);

    $response = $this->get('/s/directkey');

    $response->assertRedirect('https://example.com/direct');
});

it('does not load inactive pixels from registry', function () {
    $link = ShortUrl::create([
        'destination_url' => 'https://example.com/inactive-target',
        'url_key' => 'inactivekey',
    ]);

    $activePixel = ShortUrlPixel::create([
        'name' => 'Active Meta Pixel',
        'type' => 'meta',
        'pixel_id' => 'META-ACTIVE',
        'is_active' => true,
    ]);

    $inactivePixel = ShortUrlPixel::create([
        'name' => 'Inactive Google Tag',
        'type' => 'google',
        'pixel_id' => 'G-INACTIVE',
        'is_active' => false,
    ]);

    $link->pixels()->sync([
        $activePixel->id,
        $inactivePixel->id,
    ]);

    $response = $this->get('/s/inactivekey');

    $response->assertStatus(200);
    $response->assertViewIs('filament-short-url::pixel-loading');

    // Assert active is rendered
    $response->assertSee("fbq('init', 'META-ACTIVE')", false);

    // Assert inactive is NOT rendered
    $response->assertDontSee('G-INACTIVE', false);
});
