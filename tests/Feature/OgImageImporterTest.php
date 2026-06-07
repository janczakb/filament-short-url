<?php

use Bjanczak\FilamentShortUrl\Services\OgImageImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('imports a remote image into the public tmp directory', function () {
    Storage::fake('public');

    $jpeg = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAAA//EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AP//Z');

    Http::fake([
        'https://example.com/og.jpg' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $path = app(OgImageImporter::class)->importFromUrl('https://example.com/og.jpg');

    expect($path)->not->toBeNull()
        ->and($path)->toMatch('#^short-urls/tmp/\d{4}/\d{2}/\d{2}/\d{2}/#')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

it('rejects unsafe image urls', function () {
    Storage::fake('public');

    $path = app(OgImageImporter::class)->importFromUrl('http://127.0.0.1/secret.jpg');

    expect($path)->toBeNull();
});
