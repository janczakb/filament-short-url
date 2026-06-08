<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a custom domain and find short URLs mapped to it', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'links.acme.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'promo',
        'custom_domain_id' => $domain->id,
    ]);

    expect($shortUrl->customDomain->domain)->toBe('links.acme.com');

    // Finding URL on the custom domain
    $found = ShortUrl::findByKey('promo', 'links.acme.com');
    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($shortUrl->id);

    // Finding URL on standard domain should fail since it's scoped to the custom domain
    $notFound = ShortUrl::findByKey('promo', 'app.com');
    expect($notFound)->toBeNull();
});

it('routes requests on custom domains at root-level fallback', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'links.acme.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'promo',
        'custom_domain_id' => $domain->id,
    ]);

    // Request to root of custom domain key should trigger redirect
    $response = $this->get('http://links.acme.com/promo');

    $response->assertRedirect('https://example.com/target');
});

it('returns 404 for fallback requests on unregistered custom domains', function () {
    // Attempting access on unregistered domain
    $response = $this->get('http://unregistered.com/somekey');

    $response->assertStatus(404);
});

it('returns 404 for path segments / subfolders under custom domains fallback', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'links.acme.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    $response = $this->get('http://links.acme.com/not-registered/subfolder');

    $response->assertStatus(404);
});

it('returns correct short URL link string based on custom domain association', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'links.acme.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'promo',
        'custom_domain_id' => $domain->id,
    ]);

    $standardUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/standard',
        'url_key' => 'stdkey',
    ]);

    config(['app.url' => 'https://maindomain.com']);
    config(['filament-short-url.route_prefix' => 's']);

    expect($shortUrl->getShortUrl())->toBe('https://links.acme.com/promo');
    expect($standardUrl->getShortUrl())->toBe('https://maindomain.com/s/stdkey');
});

it('invalidates both old and new custom domain cache keys when domain name is updated', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'old-domain.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    cache()->remember('filament-short-url:custom-domain:old-domain.com', 300, fn () => $domain);

    expect(cache()->has('filament-short-url:custom-domain:old-domain.com'))->toBeTrue();

    // Now update domain name
    $domain->update(['domain' => 'new-domain.com']);

    // Both old and new domain cache keys must be cleared
    expect(cache()->has('filament-short-url:custom-domain:old-domain.com'))->toBeFalse();
    expect(cache()->has('filament-short-url:custom-domain:new-domain.com'))->toBeFalse();
});

it('invalidates short URL redirect caches when its custom domain is updated or toggled', function () {
    $domain = ShortUrlCustomDomain::create([
        'domain' => 'old-domain.com',
        'is_verified' => true,
        'is_active' => true,
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'promo',
        'custom_domain_id' => $domain->id,
    ]);

    cache()->remember('filament-short-url:promo:old-domain.com', 3600, fn () => $shortUrl);
    expect(cache()->has('filament-short-url:promo:old-domain.com'))->toBeTrue();

    // Update the domain name
    $domain->update(['domain' => 'new-domain.com']);

    // Redirect cache for the old domain should be invalidated
    expect(cache()->has('filament-short-url:promo:old-domain.com'))->toBeFalse();
    expect(cache()->has('filament-short-url:promo:new-domain.com'))->toBeFalse();
});
