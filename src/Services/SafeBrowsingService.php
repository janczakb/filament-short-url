<?php

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafeBrowsingService
{
    /**
     * Check if a URL is safe using Google Safe Browsing API.
     *
     * Returns true if safe (or if API is disabled/failed), false if a threat is detected.
     */
    public function isSafe(string $url): bool
    {
        $enabled = config('filament-short-url.safe_browsing.enabled', false);
        $apiKey = config('filament-short-url.safe_browsing.api_key');

        if (! $enabled || empty($apiKey)) {
            return true;
        }

        return $this->isSafeWithKey($url, $apiKey);
    }

    /**
     * Check if a URL is safe using Google Safe Browsing API with a specific key.
     */
    public function isSafeWithKey(string $url, string $apiKey): bool
    {
        if (empty($apiKey)) {
            return true;
        }

        try {
            $endpoint = "https://safebrowsing.googleapis.com/v4/threatMatches:find?key={$apiKey}";

            $payload = [
                'client' => [
                    'clientId' => 'filament-short-url-plugin',
                    'clientVersion' => '2.0.0',
                ],
                'threatInfo' => [
                    'threatTypes' => [
                        'MALWARE',
                        'SOCIAL_ENGINEERING',
                        'UNWANTED_SOFTWARE',
                        'POTENTIALLY_HARMFUL_APPLICATION',
                    ],
                    'platformTypes' => ['ANY_PLATFORM'],
                    'threatEntryTypes' => ['URL'],
                    'threatEntries' => [
                        ['url' => $url],
                    ],
                ],
            ];

            $response = Http::timeout(3)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, $payload);

            if ($response->failed()) {
                Log::warning("Google Safe Browsing API request failed with status: " . $response->status());
                return true; // Default to safe if API is down
            }

            $matches = $response->json('matches', []);

            // If there are matches, the URL is flagged as unsafe
            return empty($matches);
        } catch (\Throwable $e) {
            Log::warning("Google Safe Browsing check failed: " . $e->getMessage());
            return true; // Default to safe on exception
        }
    }
}
