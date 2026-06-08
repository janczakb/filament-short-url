<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

/**
 * Pure-PHP User Agent parser.
 *
 * No external dependencies. Uses ordered regex matching for accuracy.
 * Detects: browsers, browser versions, operating systems, OS versions, device types.
 *
 * @internal
 */
class UserAgentParser
{
    public function __construct(
        private readonly BotDetector $botDetector,
    ) {}

    /**
     * @return array{
     *     browser: string|null,
     *     browser_version: string|null,
     *     operating_system: string|null,
     *     operating_system_version: string|null,
     *     device_type: string,
     * }
     */
    public function parse(string $userAgent): array
    {
        return [
            'browser' => $this->parseBrowser($userAgent),
            'browser_version' => $this->parseBrowserVersion($userAgent),
            'operating_system' => $this->parseOs($userAgent),
            'operating_system_version' => $this->parseOsVersion($userAgent),
            'device_type' => $this->parseDeviceType($userAgent),
        ];
    }

    public function getDeviceType(string $userAgent): string
    {
        return $this->parseDeviceType($userAgent);
    }

    public function getOs(string $userAgent): ?string
    {
        return $this->parseOs($userAgent);
    }

    private function parseBrowser(string $ua): ?string
    {
        // Order matters — check specific first, generic last
        $patterns = [
            'Edg/' => 'Edge',
            'EdgiOS' => 'Edge',
            'EdgA' => 'Edge',
            'OPR/' => 'Opera',
            'Opera' => 'Opera',
            'SamsungBrowser' => 'Samsung Browser',
            'Chrome' => 'Chrome',
            'CriOS' => 'Chrome',         // Chrome iOS
            'Firefox' => 'Firefox',
            'FxiOS' => 'Firefox',        // Firefox iOS
            'Safari' => 'Safari',
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
            'Googlebot' => 'Googlebot',
            'bingbot' => 'Bingbot',
            'DuckDuckBot' => 'DuckDuckBot',
            'Slurp' => 'Yahoo! Slurp',
            'curl' => 'cURL',
        ];

        foreach ($patterns as $token => $name) {
            if (stripos($ua, $token) !== false) {
                return $name;
            }
        }

        return null;
    }

    private function parseBrowserVersion(string $ua): ?string
    {
        $patterns = [
            '/Edg\/([0-9.]+)/',
            '/EdgiOS\/([0-9.]+)/',
            '/EdgA\/([0-9.]+)/',
            '/OPR\/([0-9.]+)/',
            '/SamsungBrowser\/([0-9.]+)/',
            '/CriOS\/([0-9.]+)/',
            '/Chrome\/([0-9.]+)/',
            '/FxiOS\/([0-9.]+)/',
            '/Firefox\/([0-9.]+)/',
            '/Version\/([0-9.]+).*Safari/',
            '/MSIE ([0-9.]+)/',
            '/rv:([0-9.]+).*Trident/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $ua, $matches)) {
                // Return major.minor only
                $parts = explode('.', $matches[1]);

                return implode('.', array_slice($parts, 0, 2));
            }
        }

        return null;
    }

    private function parseOs(string $ua): ?string
    {
        return match (true) {
            (stripos($ua, 'Silk/') !== false || stripos($ua, 'Kindle') !== false) => 'Fire OS',
            stripos($ua, 'Windows') !== false => 'Windows',
            stripos($ua, 'iPad') !== false => 'iPadOS',
            stripos($ua, 'iPhone') !== false => 'iOS',
            stripos($ua, 'Mac OS X') !== false => 'macOS',
            stripos($ua, 'Android') !== false => 'Android',
            stripos($ua, 'Linux') !== false => 'Linux',
            stripos($ua, 'CrOS') !== false => 'Chrome OS',
            default => null,
        };
    }

    private function parseOsVersion(string $ua): ?string
    {
        // Windows: Windows NT 10.0
        if (preg_match('/Windows NT ([0-9.]+)/', $ua, $m)) {
            return $this->windowsVersion($m[1]);
        }

        // Android: Android 13
        if (preg_match('/Android ([0-9.]+)/', $ua, $m)) {
            return $m[1];
        }

        // iOS / iPadOS: OS 17_0
        if (preg_match('/OS ([0-9_]+) like Mac/', $ua, $m)) {
            return str_replace('_', '.', $m[1]);
        }

        // macOS: Mac OS X 10_15_7 or 14.0
        if (preg_match('/Mac OS X ([0-9_.]+)/', $ua, $m)) {
            return str_replace('_', '.', $m[1]);
        }

        // Linux — usually no version
        if (preg_match('/Linux ([0-9.]+)/', $ua, $m)) {
            return $m[1];
        }

        return null;
    }

    private function parseDeviceType(string $ua): string
    {
        if ($this->botDetector->isBotUserAgent($ua)) {
            return 'robot';
        }
        // Tablets before phones — iPad + Android tablets
        if (
            stripos($ua, 'iPad') !== false ||
            (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)
        ) {
            return 'tablet';
        }

        // Mobile phones
        $mobileKeywords = ['Mobile', 'iPhone', 'iPod', 'BlackBerry', 'IEMobile', 'Opera Mini', 'SamsungBrowser', 'Kindle'];
        foreach ($mobileKeywords as $keyword) {
            if (stripos($ua, $keyword) !== false) {
                return 'mobile';
            }
        }

        return 'desktop';
    }

    private function windowsVersion(string $ntVersion): string
    {
        return match ($ntVersion) {
            '10.0' => '10/11',
            '6.3' => '8.1',
            '6.2' => '8',
            '6.1' => '7',
            '6.0' => 'Vista',
            '5.1' => 'XP',
            default => $ntVersion,
        };
    }
}
