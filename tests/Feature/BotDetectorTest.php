<?php

use Bjanczak\FilamentShortUrl\Services\BotDetector;
use Illuminate\Http\Request;

it('detects major social preview crawlers', function (string $userAgent) {
    $detector = app(BotDetector::class);

    expect($detector->isBotUserAgent($userAgent))->toBeTrue();
})->with([
    'facebook' => ['facebookexternalhit/1.1'],
    'google' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
    'pinterest' => ['Pinterestbot/1.0'],
    'iframely' => ['Iframely/1.3.1 (+https://iframely.com/docs/about)'],
    'discord' => ['Discordbot/2.0'],
    'meta agent' => ['meta-externalagent/1.1'],
    'chatgpt' => ['ChatGPT-User'],
]);

it('does not flag real browser user agents as bots', function (string $userAgent) {
    $detector = app(BotDetector::class);

    expect($detector->isBotUserAgent($userAgent))->toBeFalse();
})->with([
    'chrome desktop' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'],
    'safari ios' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'],
    'firefox' => ['Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0'],
]);

it('treats head requests as bots', function () {
    $detector = app(BotDetector::class);
    $request = Request::create('/s/test', 'HEAD');

    expect($detector->isBot($request))->toBeTrue();
});

it('detects preview bots from referer headers', function () {
    $detector = app(BotDetector::class);
    $request = Request::create('/s/test', 'GET', server: [
        'HTTP_REFERER' => 'https://url.emailprotection.link/s/example',
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);

    expect($detector->isBot($request))->toBeTrue();
});

it('detects generic bot signatures via regex', function () {
    $detector = app(BotDetector::class);

    expect($detector->isBotUserAgent('MyCustomCrawler/2.0'))->toBeTrue()
        ->and($detector->isBotUserAgent('SomeCompany Spider 1.0'))->toBeTrue();
});
