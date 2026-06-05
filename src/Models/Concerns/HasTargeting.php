<?php

namespace Bjanczak\FilamentShortUrl\Models\Concerns;

use Bjanczak\FilamentShortUrl\Services\ClientIpExtractor;
use Bjanczak\FilamentShortUrl\Services\GeoIpService;
use Bjanczak\FilamentShortUrl\Services\UserAgentParser;
use Illuminate\Http\Request;

trait HasTargeting
{
    /**
     * Resolve the destination URL for this short URL based on request properties.
     */
    public function resolveDestinationUrl(Request $request): string
    {
        $rules = $this->targeting_rules;

        // Legacy rotation support
        if (is_array($rules) && isset($rules['type']) && $rules['type'] === 'rotation') {
            return $this->resolveSplitTarget($rules['rotation'] ?? [], $this->destination_url ?? '');
        }

        // Map legacy targeting rules format to new format on the fly
        if (is_array($rules) && isset($rules['type'])) {
            $rules = $this->mapLegacyTargetingRules($rules);
        }

        // Evaluate multi-filter rule engine first
        if (is_array($rules) && ! empty($rules)) {
            // Lazy-loaded request properties for maximum performance
            $deviceType = null;
            $platformOs = null;
            $countryCode = null;
            $browserLanguages = null;

            foreach ($rules as $rule) {
                $filters = $rule['filters'] ?? [];
                if (empty($filters)) {
                    continue;
                }

                $matchType = $rule['match'] ?? 'or';
                $ruleMatches = $matchType === 'and'; // 'and' defaults to true, 'or' defaults to false

                foreach ($filters as $filter) {
                    $filterType = $filter['type'] ?? '';
                    $filterData = $filter['data'] ?? [];
                    $filterMatches = false;

                    if ($filterType === 'device') {
                        if ($deviceType === null) {
                            $deviceType = app(UserAgentParser::class)->getDeviceType($request->userAgent() ?? '');
                        }
                        $filterMatches = in_array($deviceType, $filterData['devices'] ?? []);
                    } elseif ($filterType === 'platform') {
                        if ($platformOs === null) {
                            $rawOs = app(UserAgentParser::class)->getOs($request->userAgent() ?? '') ?? '';
                            $platformOs = match (true) {
                                stripos($rawOs, 'Windows') !== false => 'windows',
                                stripos($rawOs, 'Fire OS') !== false => 'fire_os',
                                stripos($rawOs, 'iOS') !== false || stripos($rawOs, 'iPad') !== false => 'ios',
                                stripos($rawOs, 'Mac') !== false => 'mac',
                                stripos($rawOs, 'Android') !== false => 'android',
                                stripos($rawOs, 'Linux') !== false => 'linux',
                                default => strtolower($rawOs),
                            };
                        }
                        $filterMatches = in_array($platformOs, $filterData['platforms'] ?? []);
                    } elseif ($filterType === 'country') {
                        if ($countryCode === null) {
                            $countryCode = ClientIpExtractor::getCountryCode($request);
                            if (! $countryCode) {
                                $ip = ClientIpExtractor::getIp($request);
                                $geo = app(GeoIpService::class)->resolve($ip);
                                $countryCode = $geo['country_code'] ?? '';
                            }
                            $countryCode = strtoupper(trim($countryCode));
                        }
                        $filterMatches = in_array($countryCode, array_map('strtoupper', $filterData['countries'] ?? []));
                    } elseif ($filterType === 'language') {
                        if ($browserLanguages === null) {
                            $browserLanguages = array_map(function ($lang) {
                                return strtolower(trim(str_replace('_', '-', $lang)));
                            }, $request->getLanguages());
                        }

                        $filterLangs = array_map('strtolower', $filterData['languages'] ?? []);

                        // Pass 1: Exact match
                        foreach ($browserLanguages as $browserLang) {
                            if (in_array($browserLang, $filterLangs)) {
                                $filterMatches = true;
                                break;
                            }
                        }

                        // Pass 2: Fallback prefix match
                        if (! $filterMatches) {
                            foreach ($browserLanguages as $browserLang) {
                                $primaryLang = explode('-', $browserLang)[0];
                                if (in_array($primaryLang, $filterLangs)) {
                                    $filterMatches = true;
                                    break;
                                }
                            }
                        }
                    }

                    if ($matchType === 'and') {
                        if (! $filterMatches) {
                            $ruleMatches = false;
                            break; // fail fast
                        }
                    } else { // 'or'
                        if ($filterMatches) {
                            $ruleMatches = true;
                            break; // succeed fast
                        }
                    }
                }

                if ($ruleMatches) {
                    $destType = $rule['destination_type'] ?? 'single';
                    if ($destType === 'split') {
                        return $this->resolveSplitTarget($rule['variants'] ?? [], $this->destination_url ?? '');
                    }

                    if (! empty($rule['url'])) {
                        return $rule['url'];
                    }
                }
            }
        }

        // Fallback to default destination URL
        if (($this->destination_type ?? 'single') === 'split') {
            return $this->resolveSplitTarget($this->rotation_variants ?? [], $this->destination_url ?? '');
        }

        return $this->destination_url ?? '';
    }

    /**
     * Resolve destination URL from a split testing configuration.
     */
    public function resolveSplitTarget(array $variants, string $fallbackUrl): string
    {
        if (empty($variants)) {
            return $fallbackUrl;
        }

        $totalWeight = array_sum(array_column($variants, 'weight'));
        if ($totalWeight <= 0) {
            $selected = $variants[0];
        } else {
            $rand = mt_rand(1, $totalWeight);
            $currentWeight = 0;
            $selected = $variants[0];
            foreach ($variants as $variant) {
                $currentWeight += (int) ($variant['weight'] ?? 0);
                if ($rand <= $currentWeight) {
                    $selected = $variant;
                    break;
                }
            }
        }

        $selectedUrl = $selected['url'] ?? $fallbackUrl;
        $selectedLabel = $selected['label'] ?? $selectedUrl;

        // Share the selected variant with the application container so the tracker can access it
        app()->instance('resolved_ab_variant', $selectedLabel);

        return $selectedUrl;
    }

    /**
     * Map legacy targeting rules format to the new multi-filter engine format.
     */
    public function mapLegacyTargetingRules(array $state): array
    {
        $type = $state['type'] ?? '';
        $newRules = [];

        if ($type === 'device') {
            $devices = $state['device'] ?? [];
            $mobileUrl = $devices['mobile'] ?? $devices['ios'] ?? null;
            if ($mobileUrl) {
                $newRules[] = [
                    'match' => 'or',
                    'destination_type' => 'single',
                    'url' => $mobileUrl,
                    'filters' => [
                        [
                            'type' => 'device',
                            'data' => ['devices' => ['mobile']],
                        ],
                    ],
                ];
            }
            $tabletUrl = $devices['tablet'] ?? $devices['android'] ?? null;
            if ($tabletUrl) {
                $newRules[] = [
                    'match' => 'or',
                    'destination_type' => 'single',
                    'url' => $tabletUrl,
                    'filters' => [
                        [
                            'type' => 'device',
                            'data' => ['devices' => ['tablet']],
                        ],
                    ],
                ];
            }
            $desktopUrl = $devices['desktop'] ?? null;
            if ($desktopUrl) {
                $newRules[] = [
                    'match' => 'or',
                    'destination_type' => 'single',
                    'url' => $desktopUrl,
                    'filters' => [
                        [
                            'type' => 'device',
                            'data' => ['devices' => ['desktop']],
                        ],
                    ],
                ];
            }
        } elseif ($type === 'geo') {
            foreach ($state['geo'] ?? [] as $geoRule) {
                if (! empty($geoRule['url']) && ! empty($geoRule['country_code'])) {
                    $newRules[] = [
                        'match' => 'or',
                        'destination_type' => 'single',
                        'url' => $geoRule['url'],
                        'filters' => [
                            [
                                'type' => 'country',
                                'data' => ['countries' => [strtoupper($geoRule['country_code'])]],
                            ],
                        ],
                    ];
                }
            }
        } elseif ($type === 'language') {
            foreach ($state['language'] ?? [] as $langRule) {
                if (! empty($langRule['url']) && ! empty($langRule['language_code'])) {
                    $newRules[] = [
                        'match' => 'or',
                        'destination_type' => 'single',
                        'url' => $langRule['url'],
                        'filters' => [
                            [
                                'type' => 'language',
                                'data' => ['languages' => [strtolower($langRule['language_code'])]],
                            ],
                        ],
                    ];
                }
            }
        }

        return $newRules;
    }
}
