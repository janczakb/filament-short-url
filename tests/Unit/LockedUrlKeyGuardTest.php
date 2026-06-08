<?php

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Support\LockedUrlKeyGuard;
use Illuminate\Validation\ValidationException;

it('strips locked url key fields from filament save data', function () {
    config(['filament-short-url.lock_url_key' => true]);

    $record = new ShortUrl([
        'url_key' => 'locked-key',
        'custom_domain_id' => 5,
    ]);
    $record->exists = true;

    $sanitized = LockedUrlKeyGuard::sanitizeSaveData([
        'url_key' => 'hacked-key',
        'custom_domain_id' => 99,
        'destination_url' => 'https://example.com',
    ], $record);

    expect($sanitized['url_key'])->toBe('locked-key')
        ->and($sanitized['custom_domain_id'])->toBe(5)
        ->and($sanitized['destination_url'])->toBe('https://example.com');
});

it('throws when a locked model tries to change url key', function () {
    config(['filament-short-url.lock_url_key' => true]);

    $record = new ShortUrl([
        'url_key' => 'original',
        'custom_domain_id' => null,
    ]);
    $record->exists = true;
    $record->syncOriginal();

    $record->url_key = 'changed';

    expect(fn () => LockedUrlKeyGuard::assertModelCanPersistKeyChanges($record))
        ->toThrow(ValidationException::class);
});

it('allows url key changes when lock is disabled', function () {
    config(['filament-short-url.lock_url_key' => false]);

    $record = new ShortUrl(['url_key' => 'original']);
    $record->exists = true;
    $record->syncOriginal();
    $record->url_key = 'changed';

    LockedUrlKeyGuard::assertModelCanPersistKeyChanges($record);

    expect($record->url_key)->toBe('changed');
});
