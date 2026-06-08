<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTempStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores uploads in hourly buckets', function () {
    $temp = app(ShortUrlTempStorage::class);

    expect($temp->bucketDirectory(Carbon::parse('2026-06-08 14:30:00')))
        ->toBe('short-urls/tmp/2026/06/08/14');
});

it('promotes tmp og images to permanent storage on save', function () {
    Storage::fake('public');

    $tmpPath = 'short-urls/tmp/2026/06/08/10/test.webp';
    Storage::disk('public')->put($tmpPath, 'fake-image');

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'og-promote',
        'og_image' => $tmpPath,
    ]);

    expect($shortUrl->og_image)->toBe('short-urls/og/test.webp')
        ->and(Storage::disk('public')->exists('short-urls/og/test.webp'))->toBeTrue()
        ->and(Storage::disk('public')->exists($tmpPath))->toBeFalse();
});

it('promotes tmp qr logos to permanent storage on save', function () {
    Storage::fake('public');

    $tmpPath = 'short-urls/tmp/2026/06/08/10/logo.webp';
    Storage::disk('public')->put($tmpPath, 'fake-logo');

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com',
        'url_key' => 'logo-promote',
        'qr_logo' => $tmpPath,
    ]);

    expect($shortUrl->qr_logo)->toBe('short-urls/logos/logo.webp')
        ->and(Storage::disk('public')->exists('short-urls/logos/logo.webp'))->toBeTrue()
        ->and(Storage::disk('public')->exists($tmpPath))->toBeFalse();
});

it('prunes expired hour buckets without scanning every file', function () {
    Storage::fake('public');

    $expiredBucket = 'short-urls/tmp/2026/06/06/10';
    $freshBucket = 'short-urls/tmp/'.now()->format('Y/m/d/H');

    Storage::disk('public')->put($expiredBucket.'/old-1.webp', 'a');
    Storage::disk('public')->put($expiredBucket.'/old-2.webp', 'b');
    Storage::disk('public')->put($freshBucket.'/fresh.webp', 'c');
    Storage::disk('public')->put('short-urls/tmp/legacy.webp', 'd');

    Carbon::setTestNow(Carbon::parse('2026-06-08 12:00:00'));

    $pruned = app(ShortUrlTempStorage::class)->pruneBucketsOlderThanHours(24);

    expect($pruned)->toBe(2)
        ->and(Storage::disk('public')->exists($expiredBucket))->toBeFalse()
        ->and(Storage::disk('public')->exists($freshBucket.'/fresh.webp'))->toBeTrue()
        ->and(Storage::disk('public')->exists('short-urls/tmp/legacy.webp'))->toBeTrue();

    Carbon::setTestNow();
});
