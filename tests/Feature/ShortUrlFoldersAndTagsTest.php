<?php

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlLiveFeedWidget;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlFolder;
use Bjanczak\FilamentShortUrl\Models\ShortUrlTag;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('can assign a short URL to a folder and tags', function () {
    $folder = ShortUrlFolder::create([
        'name' => 'Marketing Campaigns',
        'slug' => 'marketing-campaigns',
        'color' => 'blue',
    ]);

    $tag1 = ShortUrlTag::create([
        'name' => 'Summer 2026',
        'slug' => 'summer-2026',
        'color' => 'red',
    ]);

    $tag2 = ShortUrlTag::create([
        'name' => 'Newsletter',
        'slug' => 'newsletter',
        'color' => 'green',
    ]);

    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/promo',
        'url_key' => 'summer',
        'folder_id' => $folder->id,
    ]);

    $shortUrl->tags()->attach([$tag1->id, $tag2->id]);

    // Assertions
    expect($shortUrl->folder->name)->toBe('Marketing Campaigns')
        ->and($shortUrl->tags)->toHaveCount(2)
        ->and($shortUrl->tags->pluck('name'))->toContain('Summer 2026', 'Newsletter');

    // Assert reverse relations
    expect($folder->shortUrls)->toHaveCount(1)
        ->and($tag1->shortUrls)->toHaveCount(1);
});

it('can archive and restore a short URL', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/archive-test',
        'url_key' => 'archivekey',
        'is_archived' => false,
    ]);

    expect($shortUrl->is_archived)->toBeFalse();

    $shortUrl->update(['is_archived' => true]);
    expect($shortUrl->fresh()->is_archived)->toBeTrue();

    $shortUrl->update(['is_archived' => false]);
    expect($shortUrl->fresh()->is_archived)->toBeFalse();
});

it('renders live activity feed widget with filtered data', function () {
    $shortUrl = ShortUrl::create([
        'destination_url' => 'https://example.com/live-test',
        'url_key' => 'livekey',
    ]);

    // Seed some visits
    $visitPl = ShortUrlVisit::create([
        'short_url_id' => $shortUrl->id,
        'country_code' => 'PL',
        'country' => 'Poland',
        'device_type' => 'mobile',
        'browser' => 'Chrome',
        'operating_system' => 'Android',
        'visited_at' => now(),
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    $visitUs = ShortUrlVisit::create([
        'short_url_id' => $shortUrl->id,
        'country_code' => 'US',
        'country' => 'United States',
        'device_type' => 'desktop',
        'browser' => 'Safari',
        'operating_system' => 'OS X',
        'visited_at' => now()->subMinute(),
        'is_bot' => false,
        'is_proxy' => false,
    ]);

    // Test without filters
    Livewire::test(ShortUrlLiveFeedWidget::class, ['record' => $shortUrl])
        ->assertViewHas('visits', function ($visits) {
            return count($visits) === 2;
        });

    // Test with cross-filtering (PL only)
    Livewire::test(ShortUrlLiveFeedWidget::class, [
        'record' => $shortUrl,
        'filters' => ['country_code' => 'PL'],
    ])
        ->assertViewHas('visits', function ($visits) {
            return count($visits) === 1 && $visits->first()->country_code === 'PL';
        });
});
