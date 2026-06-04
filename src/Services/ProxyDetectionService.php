<?php

/**
 * @package    janczakb/filament-short-url
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxyDetectionService
{
    /**
     * Detect if an IP address belongs to a VPN, proxy, Tor node, or data center hosting.
     *
     * @return array{is_proxy: bool, is_bot: bool}
     */
    public function detect(string $ip): array
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return ['is_proxy' => false, 'is_bot' => false];
        }

        $enabled = config('filament-short-url.vpn_detection.enabled', false);
        if (! $enabled) {
            return ['is_proxy' => false, 'is_bot' => false];
        }

        $cacheKey = "short-url:proxy-check:{$ip}";
        $cacheTtl = (int) config('filament-short-url.vpn_detection.cache_ttl', 86400);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($ip) {
            $driver = config('filament-short-url.vpn_detection.driver', 'ip-api');
            $timeout = (int) config('filament-short-url.vpn_detection.timeout', 2);

            try {
                if ($driver === 'vpnapi') {
                    return $this->queryVpnApi($ip, $timeout);
                }

                // Default to ip-api
                return $this->queryIpApi($ip, $timeout);
            } catch (\Throwable $e) {
                Log::warning("Proxy detection failed for IP: {$ip}. Error: ".$e->getMessage());

                return ['is_proxy' => false, 'is_bot' => false];
            }
        });
    }

    /**
     * Query ip-api.com endpoint.
     *
     * @return array{is_proxy: bool, is_bot: bool}
     */
    private function queryIpApi(string $ip, int $timeout): array
    {
        $url = "http://ip-api.com/json/{$ip}?fields=status,message,proxy,hosting";

        $response = Http::timeout($timeout)->get($url);

        if ($response->failed() || $response->json('status') === 'fail') {
            return ['is_proxy' => false, 'is_bot' => false];
        }

        $isProxy = (bool) $response->json('proxy', false);
        $isBot = (bool) $response->json('hosting', false); // hosting indicates data centers (bots/scrapers)

        return [
            'is_proxy' => $isProxy,
            'is_bot' => $isBot,
        ];
    }

    /**
     * Query vpnapi.io endpoint.
     *
     * @return array{is_proxy: bool, is_bot: bool}
     */
    private function queryVpnApi(string $ip, int $timeout): array
    {
        $key = config('filament-short-url.vpn_detection.vpnapi_key');

        if (empty($key)) {
            return ['is_proxy' => false, 'is_bot' => false];
        }

        $url = "https://vpnapi.io/api/{$ip}?key={$key}";

        $response = Http::timeout($timeout)->get($url);

        if ($response->failed()) {
            return ['is_proxy' => false, 'is_bot' => false];
        }

        $security = $response->json('security', []);
        $isVpn = (bool) ($security['vpn'] ?? false);
        $isProxy = (bool) ($security['proxy'] ?? false);
        $isTor = (bool) ($security['tor'] ?? false);
        $isRelay = (bool) ($security['relay'] ?? false);

        // Treat hosting/data center as bot traffic
        $isHosting = (bool) ($response->json('security.hosting') ?? false);

        return [
            'is_proxy' => $isVpn || $isProxy || $isTor || $isRelay,
            'is_bot' => $isHosting,
        ];
    }
}
