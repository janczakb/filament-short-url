<?php

it('embeds parseable qr options json in the share after create modal', function () {
    $qrOptions = [
        'type' => 'svg',
        'width' => 200,
        'height' => 200,
        'margin' => 1,
        'dotsOptions' => ['type' => 'square', 'color' => '#000000'],
        'backgroundOptions' => ['color' => '#ffffff'],
        'cornersSquareOptions' => ['type' => 'square', 'color' => '#000000'],
        'cornersDotOptions' => ['type' => 'square', 'color' => '#000000'],
        'image' => null,
        'imageOptions' => [
            'crossOrigin' => 'anonymous',
            'hideBackgroundDots' => true,
            'imageSize' => 0.3,
            'margin' => 9,
            'logoShape' => 'square',
        ],
        'qrOptions' => ['errorCorrectionLevel' => 'M'],
    ];

    $html = view('filament-short-url::modals.share-after-create', [
        'shortUrl' => 'https://example.test/abc',
        'qrTargetUrl' => 'https://example.test/abc?source=qr',
        'destHost' => 'example.com',
        'urlKey' => 'abc',
        'eid' => 'fsu_test1234',
        'qrOptions' => $qrOptions,
        'successTitle' => 'Ready',
        'successSubtitle' => 'Subtitle',
        'successHelper' => 'Helper',
        'downloadSvgText' => 'SVG',
        'downloadPngText' => 'PNG',
        'closeButtonText' => 'Close',
        'copyLinkText' => 'Copy',
        'qrCodeText' => 'QR',
        'openLinkText' => 'Open',
        'dontShowAgainText' => 'Hide',
    ])->render();

    expect(preg_match("/data-qr-options='([^']+)'/", $html, $matches))->toBe(1);

    $decoded = json_decode($matches[1], true);

    expect($decoded)->toBeArray()
        ->and($decoded['type'])->toBe('svg')
        ->and($matches[1])->not->toContain('&quot;');
});
