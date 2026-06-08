<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlPasswordHasher;

it('builds a public stats url from the short url key', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'stats-link',
        'track_visits' => false,
    ]);

    expect($shortUrl->getPublicStatsUrl())->toBe(
        rtrim(config('app.url'), '/').'/'.trim(config('filament-short-url.route_prefix', 's'), '/').'/public-stats/stats-link'
    );
});

it('renders a public stats html page when enabled', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'html-stats',
        'public_stats_enabled' => true,
        'track_visits' => false,
    ]);

    $this->get('/s/public-stats/'.$shortUrl->url_key)
        ->assertOk()
        ->assertSee(__('filament-short-url::default.public_stats_page_title'), false)
        ->assertSee('html-stats', false);
});

it('still returns json when requested explicitly', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'json-stats',
        'public_stats_enabled' => true,
        'track_visits' => false,
    ]);

    $this->getJson('/s/public-stats/'.$shortUrl->url_key)
        ->assertOk()
        ->assertJsonStructure(['data' => ['totalVisits', 'uniqueVisits']]);

    $this->get('/s/public-stats/'.$shortUrl->url_key.'?format=json')
        ->assertOk()
        ->assertJsonStructure(['data' => ['totalVisits', 'uniqueVisits']]);
});

it('shows a password form for protected public stats html', function () {
    $hasher = app(ShortUrlPasswordHasher::class);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'protected-html-stats',
        'public_stats_enabled' => true,
        'public_stats_password' => $hasher->hash('secret-stats'),
        'track_visits' => false,
    ]);

    $this->get('/s/public-stats/'.$shortUrl->url_key)
        ->assertOk()
        ->assertSee(__('filament-short-url::default.public_stats_password_title'), false);

    $this->post('/s/public-stats/'.$shortUrl->url_key, [
        'password' => 'secret-stats',
    ])->assertRedirect('/s/public-stats/'.$shortUrl->url_key);

    $this->get('/s/public-stats/'.$shortUrl->url_key)
        ->assertOk()
        ->assertSee(__('filament-short-url::default.public_stats_total_visits'), false);
});

it('returns 404 for disabled public stats html page', function () {
    ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'hidden-html-stats',
        'public_stats_enabled' => false,
        'track_visits' => false,
    ]);

    $this->get('/s/public-stats/hidden-html-stats')->assertNotFound();
});
