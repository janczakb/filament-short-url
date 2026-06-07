<?php

use Bjanczak\FilamentShortUrl\Services\UrlMetaScraper;

it('parses open graph metadata from the head section only', function () {
    $html = <<<'HTML'
    <!doctype html>
    <html>
    <head>
        <title>Fallback Title</title>
        <meta property="og:title" content="OG Title">
        <meta property="og:description" content="OG Description">
        <meta property="og:image" content="/images/preview.jpg">
    </head>
    <body><p>Large body content that should never be parsed.</p></body>
    </html>
    HTML;

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com/blog/post');

    expect($meta)->toMatchArray([
        'title' => 'OG Title',
        'description' => 'OG Description',
        'image' => 'https://example.com/images/preview.jpg',
    ]);
});

it('falls back to twitter and standard meta tags when open graph is missing', function () {
    $html = <<<'HTML'
    <head>
        <meta name="twitter:title" content="Twitter Title">
        <meta name="twitter:description" content="Twitter Description">
        <meta name="twitter:image" content="https://cdn.example.com/card.png">
    </head>
    HTML;

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com');

    expect($meta)->toMatchArray([
        'title' => 'Twitter Title',
        'description' => 'Twitter Description',
        'image' => 'https://cdn.example.com/card.png',
    ]);
});

it('supports reversed meta attribute order and html entities', function () {
    $html = <<<'HTML'
    <head>
        <meta content="Tom &amp; Jerry" property="og:title">
        <meta content="Classic cartoon" name="description">
        <meta content="//cdn.example.com/og.jpg" property="og:image">
    </head>
    HTML;

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com/page');

    expect($meta)->toMatchArray([
        'title' => 'Tom & Jerry',
        'description' => 'Classic cartoon',
        'image' => 'https://cdn.example.com/og.jpg',
    ]);
});

it('returns title only when no other metadata is present', function () {
    $html = '<head><title>Only Title</title></head>';

    $meta = app(UrlMetaScraper::class)->parseFromHtml($html, 'https://example.com');

    expect($meta)->toBe([
        'title' => 'Only Title',
    ]);
});
