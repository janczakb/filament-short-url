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

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $driver = config('filament-short-url.vpn_detection.driver', 'ip-api');
        // Aggressive 800ms timeout to avoid hanging the redirect thread on API lag
        $timeout = 0.8;

        try {
            if ($driver === 'vpnapi') {
                $result = $this->queryVpnApi($ip, $timeout);
            } else {
                $result = $this->queryIpApi($ip, $timeout);
            }

            $cacheTtl = (int) config('filament-short-url.vpn_detection.cache_ttl', 86400);
            Cache::put($cacheKey, $result, $cacheTtl);

            return $result;
        } catch (\Throwable $e) {
            Log::warning("Proxy detection failed or timed out for IP: {$ip}. Error: ".$e->getMessage());

            // Temporary cache for failure (60 seconds) to allow retry and prevent whitelisting IPs long-term
            $failResult = ['is_proxy' => false, 'is_bot' => false];
            Cache::put($cacheKey, $failResult, 60);

            return $failResult;
        }
    }

    /**
     * Query ip-api.com endpoint.
     *
     * @return array{is_proxy: bool, is_bot: bool}
     */
    private function queryIpApi(string $ip, float|int $timeout): array
    {
        $url = "http://ip-api.com/json/{$ip}?fields=status,message,proxy,hosting";

        $response = Http::timeout($timeout)->get($url);

        if ($response->failed()) {
            throw new \RuntimeException('HTTP request failed with status: '.$response->status());
        }

        if ($response->json('status') === 'fail') {
            throw new \RuntimeException('API returned failure: '.$response->json('message'));
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
    private function queryVpnApi(string $ip, float|int $timeout): array
    {
        $key = config('filament-short-url.vpn_detection.vpnapi_key');

        if (empty($key)) {
            throw new \RuntimeException('vpnapi.io API key is empty.');
        }

        $url = "https://vpnapi.io/api/{$ip}?key={$key}";

        $response = Http::timeout($timeout)->get($url);

        if ($response->failed()) {
            throw new \RuntimeException('HTTP request failed with status: '.$response->status());
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
