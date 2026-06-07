<?php

use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('returns scraped metadata for a valid public url', function () {
    Cache::flush();

    $this->mock(ShortUrlService::class, function ($mock) {
        $mock->shouldReceive('scrapeMetaTags')
            ->once()
            ->with('https://example.com/page')
            ->andReturn([
                'title' => 'Example Page',
                'description' => 'An example description',
                'image' => 'https://example.com/image.jpg',
            ]);
    });

    $this->actingAs(createShortUrlUser())
        ->getJson('/short-url/scrape-meta?url='.urlencode('https://example.com/page'))
        ->assertOk()
        ->assertJson([
            'title' => 'Example Page',
            'description' => 'An example description',
            'image' => 'https://example.com/image.jpg',
        ]);
});

it('rejects invalid scrape meta urls', function () {
    $this->actingAs(createShortUrlUser())
        ->getJson('/short-url/scrape-meta?url=not-a-url')
        ->assertStatus(400)
        ->assertJson(['error' => 'Invalid URL']);
});

if (! function_exists('createShortUrlUser')) {
    function createShortUrlUser(): \App\Models\User
    {
        return \App\Models\User::factory()->create();
    }
}
