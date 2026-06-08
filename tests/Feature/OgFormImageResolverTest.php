<?php

use Bjanczak\FilamentShortUrl\Services\OgFormImageResolver;
use Illuminate\Support\Facades\Storage;

it('resolves uploaded og image paths to public storage urls', function () {
    Storage::fake('public');
    Storage::disk('public')->put('short-urls/tmp/2026/06/08/12/preview.webp', 'fake');

    $resolver = app(OgFormImageResolver::class);

    expect($resolver->resolvePreviewUrl('short-urls/tmp/2026/06/08/12/preview.webp'))
        ->toBe(Storage::disk('public')->url('short-urls/tmp/2026/06/08/12/preview.webp'));
});

it('prefers uploaded og image over scraped remote url', function () {
    Storage::fake('public');
    Storage::disk('public')->put('short-urls/og/custom.webp', 'fake');

    $resolver = app(OgFormImageResolver::class);

    expect($resolver->resolvePreviewUrl(
        'short-urls/og/custom.webp',
        'https://example.com/scraped.jpg',
    ))->toBe(Storage::disk('public')->url('short-urls/og/custom.webp'));
});

it('falls back to scraped remote url when no upload exists', function () {
    $resolver = app(OgFormImageResolver::class);

    expect($resolver->resolvePreviewUrl(null, 'https://example.com/scraped.jpg'))
        ->toBe('https://example.com/scraped.jpg');
});

it('resolves filament file upload array state', function () {
    Storage::fake('public');
    Storage::disk('public')->put('short-urls/tmp/2026/06/08/12/upload.webp', 'fake');

    $resolver = app(OgFormImageResolver::class);

    expect($resolver->resolvePreviewUrl([
        'upload-key' => 'short-urls/tmp/2026/06/08/12/upload.webp',
    ]))->toBe(Storage::disk('public')->url('short-urls/tmp/2026/06/08/12/upload.webp'));
});

it('detects when a manual og image is present', function () {
    $resolver = app(OgFormImageResolver::class);

    expect($resolver->hasUploadedImage('short-urls/og/custom.webp'))->toBeTrue()
        ->and($resolver->hasUploadedImage(['key' => 'short-urls/og/custom.webp']))->toBeTrue()
        ->and($resolver->hasUploadedImage(null))->toBeFalse()
        ->and($resolver->hasUploadedImage('https://example.com/scraped.jpg'))->toBeTrue();
});
