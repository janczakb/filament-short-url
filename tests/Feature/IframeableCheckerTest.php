<?php

use Bjanczak\FilamentShortUrl\Services\IframeableChecker;
use Illuminate\Support\Facades\Http;

it('determines if a url is iframeable based on headers', function () {
    $checker = app(IframeableChecker::class);

    // 1. Safe URL without restrictive headers should be iframeable
    Http::fake([
        'https://example.com/ok' => Http::response('ok', 200),
    ]);
    expect($checker->isIframeable('https://example.com/ok'))->toBeTrue();

    // 2. X-Frame-Options: DENY should not be iframeable
    Http::fake([
        'https://example.com/deny' => Http::response('deny', 200, ['X-Frame-Options' => 'DENY']),
    ]);
    expect($checker->isIframeable('https://example.com/deny'))->toBeFalse();

    // 3. X-Frame-Options: SAMEORIGIN should not be iframeable
    Http::fake([
        'https://example.com/sameorigin' => Http::response('sameorigin', 200, ['X-Frame-Options' => 'SAMEORIGIN']),
    ]);
    expect($checker->isIframeable('https://example.com/sameorigin'))->toBeFalse();

    // 4. CSP frame-ancestors * should be iframeable
    Http::fake([
        'https://example.com/csp-star' => Http::response('star', 200, ['Content-Security-Policy' => 'frame-ancestors *']),
    ]);
    expect($checker->isIframeable('https://example.com/csp-star'))->toBeTrue();

    // 5. CSP frame-ancestors 'none' should not be iframeable
    Http::fake([
        'https://example.com/csp-none' => Http::response('none', 200, ['Content-Security-Policy' => "frame-ancestors 'none'"]),
    ]);
    expect($checker->isIframeable('https://example.com/csp-none'))->toBeFalse();

    // 6. CSP frame-ancestors 'self' should not be iframeable
    Http::fake([
        'https://example.com/csp-self' => Http::response('self', 200, ['Content-Security-Policy' => "frame-ancestors 'self'"]),
    ]);
    expect($checker->isIframeable('https://example.com/csp-self'))->toBeFalse();
});
