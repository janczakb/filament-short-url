<?php

namespace Bjanczak\FilamentShortUrl\Services;

class AppLinkingEngine
{
    /**
     * List of supported apps with their metadata.
     *
     * @return array<string, array{name: string, color: string, icon: string, domains: string[]}>
     */
    public static function getSupportedApps(): array
    {
        return [
            'youtube' => [
                'name' => 'YouTube',
                'color' => '#ff0000',
                'icon' => 'fab-youtube',
                'domains' => ['youtube.com', 'youtu.be', 'youtube-nocookie.com'],
            ],
            'tiktok' => [
                'name' => 'TikTok',
                'color' => '#fc295d',
                'icon' => 'fab-tiktok',
                'domains' => ['tiktok.com'],
            ],
            'instagram' => [
                'name' => 'Instagram',
                'color' => '#e1306c',
                'icon' => 'fab-instagram',
                'domains' => ['instagram.com', 'instagr.am'],
            ],
            'x' => [
                'name' => 'X (Twitter)',
                'color' => '#1da1f2',
                'icon' => 'fab-x-twitter',
                'domains' => ['x.com', 'twitter.com'],
            ],
            'spotify' => [
                'name' => 'Spotify',
                'color' => '#2cd85c',
                'icon' => 'fab-spotify',
                'domains' => ['spotify.com', 'open.spotify.com', 'spotify.link'],
            ],
            'facebook' => [
                'name' => 'Facebook',
                'color' => '#1877f2',
                'icon' => 'fab-facebook',
                'domains' => ['facebook.com', 'fb.com', 'fb.me'],
            ],
            'reddit' => [
                'name' => 'Reddit',
                'color' => '#fc3a06',
                'icon' => 'fab-reddit',
                'domains' => ['reddit.com', 'redd.it'],
            ],
            'snapchat' => [
                'name' => 'Snapchat',
                'color' => '#ffb700',
                'icon' => 'fab-snapchat',
                'domains' => ['snapchat.com'],
            ],
            'whatsapp' => [
                'name' => 'WhatsApp',
                'color' => '#128c7e',
                'icon' => 'fab-whatsapp',
                'domains' => ['whatsapp.com', 'wa.me', 'api.whatsapp.com'],
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'color' => '#0a66c2',
                'icon' => 'fab-linkedin',
                'domains' => ['linkedin.com'],
            ],
            'pinterest' => [
                'name' => 'Pinterest',
                'color' => '#e60023',
                'icon' => 'fab-pinterest',
                'domains' => ['pinterest.com', 'pin.it'],
            ],
            'twitch' => [
                'name' => 'Twitch',
                'color' => '#9146ff',
                'icon' => 'fab-twitch',
                'domains' => ['twitch.tv'],
            ],
            'netflix' => [
                'name' => 'Netflix',
                'color' => '#e50914',
                'icon' => 'fas-film',
                'domains' => ['netflix.com'],
            ],
            'google_sheets' => [
                'name' => 'Google Sheets',
                'color' => '#25a465',
                'icon' => 'fas-file',
                'domains' => ['docs.google.com/spreadsheets'],
            ],
            'google_docs' => [
                'name' => 'Google Docs',
                'color' => '#2a7efc',
                'icon' => 'fas-file-word',
                'domains' => ['docs.google.com/document'],
            ],
            'google_slides' => [
                'name' => 'Google Slides',
                'color' => '#fabe0b',
                'icon' => 'fas-image',
                'domains' => ['docs.google.com/presentation'],
            ],
            'google_maps' => [
                'name' => 'Google Maps',
                'color' => '#4285f4',
                'icon' => 'fas-map-location-dot',
                'domains' => ['google.com/maps', 'maps.google.com', 'maps.app.goo.gl'],
            ],
            'facebook_messenger' => [
                'name' => 'Facebook Messenger',
                'color' => '#0084ff',
                'icon' => 'fab-facebook-messenger',
                'domains' => ['messenger.com'],
            ],
            'apple_music' => [
                'name' => 'Apple Music',
                'color' => '#f8506b',
                'icon' => 'fab-apple',
                'domains' => ['music.apple.com'],
            ],
            'airbnb' => [
                'name' => 'Airbnb',
                'color' => '#ff5a5f',
                'icon' => 'fab-airbnb',
                'domains' => ['airbnb.com', 'airbnb.pl', 'airbnb.de', 'airbnb.fr'],
            ],
            'tripadvisor' => [
                'name' => 'TripAdvisor',
                'color' => '#00af87',
                'icon' => 'fas-plane',
                'domains' => ['tripadvisor.com', 'tripadvisor.pl'],
            ],
            'amazon' => [
                'name' => 'Amazon',
                'color' => '#ff9900',
                'icon' => 'fab-amazon',
                'domains' => ['amazon.com', 'amazon.co.uk', 'amazon.de', 'amazon.pl', 'amazon.fr', 'amazon.it', 'amazon.es'],
            ],
            'stockx' => [
                'name' => 'StockX',
                'color' => '#006341',
                'icon' => 'fas-chart-line',
                'domains' => ['stockx.com'],
            ],
            'booking' => [
                'name' => 'Booking.com',
                'color' => '#003580',
                'icon' => 'fas-hotel',
                'domains' => ['booking.com'],
            ],
            'aliexpress' => [
                'name' => 'AliExpress',
                'color' => '#e62e04',
                'icon' => 'fas-cart-shopping',
                'domains' => ['aliexpress.com', 'aliexpress.ru'],
            ],
        ];
    }

    /**
     * Identify the app matching the destination URL.
     */
    public static function matchApp(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $urlLower = strtolower($url);

        foreach (static::getSupportedApps() as $appId => $app) {
            foreach ($app['domains'] as $domain) {
                if (str_contains($urlLower, $domain)) {
                    return $appId;
                }
            }
        }

        return null;
    }

    /**
     * Convert standard HTTP URL to its native deep link scheme.
     */
    public static function convertToScheme(string $url, string $appId): string
    {
        // Standard fallback: replace http(s) with scheme
        $scheme = match ($appId) {
            'youtube' => 'youtube://',
            'tiktok' => 'tiktok://',
            'instagram' => 'instagram://',
            'x' => 'twitter://',
            'spotify' => 'spotify://',
            'facebook' => 'fb://',
            'reddit' => 'reddit://',
            'snapchat' => 'snapchat://',
            'whatsapp' => 'whatsapp://',
            'linkedin' => 'linkedin://',
            'pinterest' => 'pinterest://',
            'twitch' => 'twitch://',
            'netflix' => 'netflix://',
            'facebook_messenger' => 'fb-messenger://',
            'apple_music' => 'music://',
            'airbnb' => 'airbnb://',
            'tripadvisor' => 'tripadvisor://',
            'amazon' => 'amazon://',
            'stockx' => 'stockx://',
            'booking' => 'booking://',
            'aliexpress' => 'aliexpress://',
            'google_sheets' => 'googlesheets://',
            'google_docs' => 'googledocs://',
            'google_slides' => 'googleslides://',
            'google_maps' => 'googlemaps://',
            default => null,
        };

        if ($scheme === null) {
            return $url;
        }

        // Strip http:// or https://
        $urlWithoutProtocol = preg_replace('/^https?:\/\//i', '', $url);

        // Spotify URI format (special case)
        if ($appId === 'spotify') {
            // open.spotify.com/track/123 -> spotify:track:123
            if (preg_match('/open\.spotify\.com\/(track|playlist|album|artist)\/([a-zA-Z0-9]+)/i', $url, $matches)) {
                return "spotify:{$matches[1]}:{$matches[2]}";
            }
        }

        return $scheme . $urlWithoutProtocol;
    }
}
