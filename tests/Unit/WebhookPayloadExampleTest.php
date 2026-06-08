<?php

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\WebhookPayloadExample;

it('documents the visited webhook payload shape used in production', function () {
    $sample = WebhookPayloadExample::visitedEventSample();

    expect($sample)->toHaveKeys(['event', 'timestamp', 'short_url', 'visit'])
        ->and($sample['event'])->toBe('visited')
        ->and(array_keys($sample['short_url']))->toBe(WebhookPayloadExample::visitedShortUrlKeys())
        ->and(array_keys($sample['visit']))->toBe(WebhookPayloadExample::visitedVisitKeys())
        ->and($sample['visit'])->not->toHaveKey('ip_address');
});
