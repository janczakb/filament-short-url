<?php

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\PasswordOpenGraphGuard;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlPasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('strips open graph fields from save data when a password is set', function () {
    $sanitized = PasswordOpenGraphGuard::sanitizeSaveData([
        'password' => 'secret-key',
        'og_title' => 'Preview Title',
        'og_description' => 'Preview Description',
        'og_image' => 'short-urls/og/example.webp',
    ]);

    expect($sanitized)->toMatchArray([
        'password' => 'secret-key',
        'og_title' => null,
        'og_description' => null,
        'og_image' => null,
    ]);
});

it('purges open graph metadata and deletes the stored image when saving a password protected link', function () {
    Storage::fake('public');
    Storage::disk('public')->put('short-urls/og/existing.webp', 'image-bytes');

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'og-purge',
        'og_title' => 'Old Title',
        'og_description' => 'Old Description',
        'og_image' => 'short-urls/og/existing.webp',
    ]);

    $shortUrl->password = 'new-secret';
    $shortUrl->save();

    $shortUrl->refresh();

    expect($shortUrl->og_title)->toBeNull()
        ->and($shortUrl->og_description)->toBeNull()
        ->and($shortUrl->og_image)->toBeNull()
        ->and(Storage::disk('public')->exists('short-urls/og/existing.webp'))->toBeFalse();
});

it('does not persist open graph metadata when creating a password protected link', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/target',
        'url_key' => 'og-create-pw',
        'password' => 'secret-key',
        'og_title' => 'Should Not Persist',
        'og_description' => 'Should Not Persist',
        'og_image' => 'short-urls/og/temp.webp',
    ]);

    expect($shortUrl->fresh())
        ->og_title->toBeNull()
        ->og_description->toBeNull()
        ->og_image->toBeNull()
        ->and(app(ShortUrlPasswordHasher::class)->verify('secret-key', $shortUrl->password))->toBeTrue();
});
