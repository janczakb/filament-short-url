<?php

use Bjanczak\FilamentShortUrl\FilamentShortUrlPlugin;
use Illuminate\Http\Request;

it('shows footer only on plugin routes', function () {
    config(['filament-short-url.enabled' => true]);

    expect(FilamentShortUrlPlugin::shouldShowPluginFooter(Request::create('/admin/short-urls')))
        ->toBeTrue()
        ->and(FilamentShortUrlPlugin::shouldShowPluginFooter(Request::create('/admin/short-url-settings')))
        ->toBeTrue()
        ->and(FilamentShortUrlPlugin::shouldShowPluginFooter(Request::create('/admin/short-url-pixels')))
        ->toBeTrue()
        ->and(FilamentShortUrlPlugin::shouldShowPluginFooter(Request::create('/admin/short-urls/create')))
        ->toBeTrue()
        ->and(FilamentShortUrlPlugin::shouldShowPluginFooter(Request::create('/admin/users')))
        ->toBeFalse();
});

it('hides footer when plugin is disabled', function () {
    config(['filament-short-url.enabled' => false]);

    expect(FilamentShortUrlPlugin::shouldShowPluginFooter(Request::create('/admin/short-urls')))
        ->toBeFalse();
});

it('renders footer view with package name and author handle', function () {
    $this->app->instance('request', Request::create('/admin/short-urls'));

    $html = FilamentShortUrlPlugin::renderFooter();

    expect($html)
        ->toContain('Filament Short URL')
        ->toContain(FilamentShortUrlPlugin::POWERED_BY_LABEL)
        ->toContain(FilamentShortUrlPlugin::AUTHOR_HANDLE)
        ->toContain(FilamentShortUrlPlugin::PACKAGE_NAME);
});

it('returns empty footer html outside plugin routes', function () {
    $this->app->instance('request', Request::create('/admin/users'));

    expect(FilamentShortUrlPlugin::renderFooter())->toBe('');
});
