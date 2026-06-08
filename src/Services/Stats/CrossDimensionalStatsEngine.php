<?php

namespace Bjanczak\FilamentShortUrl\Services\Stats;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;

/**
 * Builds and reads cross-dimensional daily rollups so filtered dashboard widgets
 * can be served from short_url_daily_stats instead of scanning raw visits.
 */
class CrossDimensionalStatsEngine
{
    /** @var list<string> */
    public const FILTER_KEYS = [
        'country_code',
        'city',
        'browser',
        'operating_system',
        'browser_language',
        'device_type',
        'referrer_category',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'selected_variant',
    ];

    /** @var array<string, string> Widget state keys => breakdown dimension id */
    public const STATE_BREAKDOWN_MAP = [
        'visitsByCountry' => 'country_code',
        'visitsByCity' => 'city',
        'visitsByDevice' => 'device_type',
        'visitsByBrowser' => 'browser',
        'visitsByOs' => 'operating_system',
        'visitsByReferer' => 'referrer_category',
        'utmSources' => 'utm_source',
        'utmMediums' => 'utm_medium',
        'utmCampaigns' => 'utm_campaign',
        'utmTerms' => 'utm_term',
        'utmContents' => 'utm_content',
        'visitsByLanguage' => 'browser_language',
        'visitsByVariant' => 'selected_variant',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function emptyBucket(): array
    {
        return [
            'device_stats' => [],
            'browser_stats' => [],
            'os_stats' => [],
            'country_stats' => [],
            'city_stats' => [],
            'referer_stats' => [],
            'utm_source_stats' => [],
            'utm_medium_stats' => [],
            'utm_campaign_stats' => [],
            'utm_terms' => [],
            'utm_contents' => [],
            'browser_versions' => [],
            'os_versions' => [],
            'language_stats' => [],
            'variant_stats' => [],
            'cross_dimensional_stats' => [],
            'cross_filter_pairs' => [],
            'filter_qr_counts' => [],
        ];
    }

    /**
     * Accumulate marginal + cross-dimensional stats from one human visit row.
     *
     * @param  array<string, mixed>  $bucket
     */
    public static function accumulateHumanVisit(object $row, array &$bucket): void
    {
        self::incrementMarginal($bucket, 'device_stats', self::stringOrNull($row->device_type ?? null));
        self::incrementMarginal($bucket, 'browser_stats', self::stringOrNull($row->browser ?? null));
        self::incrementMarginal($bucket, 'os_stats', self::stringOrNull($row->operating_system ?? null));
        $countryCode = self::stringOrNull($row->country_code ?? null);
        if ($countryCode !== null) {
            $countryCode = strtoupper($countryCode);
        }
        self::incrementMarginal($bucket, 'country_stats', $countryCode);

        $city = self::stringOrNull($row->city ?? null);
        if ($city !== null) {
            $cityKey = $countryCode ? "{$city} ({$countryCode})" : $city;
            self::incrementMarginal($bucket, 'city_stats', $cityKey);
        }

        self::incrementMarginal($bucket, 'referer_stats', self::stringOrNull($row->referer_host ?? null) ?: 'direct');
        self::incrementMarginal($bucket, 'utm_source_stats', self::stringOrNull($row->utm_source ?? null));
        self::incrementMarginal($bucket, 'utm_medium_stats', self::stringOrNull($row->utm_medium ?? null));
        self::incrementMarginal($bucket, 'utm_campaign_stats', self::stringOrNull($row->utm_campaign ?? null));
        self::incrementMarginal($bucket, 'utm_terms', self::stringOrNull($row->utm_term ?? null));
        self::incrementMarginal($bucket, 'utm_contents', self::stringOrNull($row->utm_content ?? null));
        self::incrementMarginal($bucket, 'language_stats', self::stringOrNull($row->browser_language ?? null));
        self::incrementMarginal($bucket, 'variant_stats', self::stringOrNull($row->selected_variant ?? null));

        $browser = self::stringOrNull($row->browser ?? null);
        if ($browser !== null) {
            $version = self::stringOrNull($row->browser_version ?? null) ?: 'Unknown';
            $bucket['browser_versions'][$browser][$version] = ($bucket['browser_versions'][$browser][$version] ?? 0) + 1;
        }

        $os = self::stringOrNull($row->operating_system ?? null);
        if ($os !== null) {
            $osVersion = self::stringOrNull($row->operating_system_version ?? null) ?: 'Unknown';
            $bucket['os_versions'][$os][$osVersion] = ($bucket['os_versions'][$os][$osVersion] ?? 0) + 1;
        }

        $filterValues = self::extractFilterValuesFromVisit($row);
        $isQr = (bool) ($row->is_qr_scan ?? false);

        foreach ($filterValues as $filterKey => $filterValue) {
            $filterQrKey = "{$filterKey}:{$filterValue}";
            if ($isQr) {
                $bucket['filter_qr_counts'][$filterQrKey] = ($bucket['filter_qr_counts'][$filterQrKey] ?? 0) + 1;
            }

            foreach (self::breakdownValuesFromVisit($row) as $breakdownKey => $breakdownValue) {
                $bucket['cross_dimensional_stats'][$filterKey][$filterValue][$breakdownKey][$breakdownValue] =
                    ($bucket['cross_dimensional_stats'][$filterKey][$filterValue][$breakdownKey][$breakdownValue] ?? 0) + 1;
            }
        }

        self::accumulateFilterPairs($filterValues, $row, $bucket, $isQr);
    }

    /**
     * Merge cross rollups from a daily_stats row into widget state arrays.
     *
     * @param  array<string, mixed>  $state
     * @param  array<string, mixed>  $filters
     * @return array{total: int, qr: int}
     */
    public static function mergeDailyCrossIntoState(array &$state, array $filters, object $row): array
    {
        $activeFilters = self::normalizeFilters($filters);

        if ($activeFilters === []) {
            return ['total' => 0, 'qr' => 0];
        }

        $cross = self::decodeJsonField($row->cross_dimensional_stats ?? null);
        $pairs = self::decodeJsonField($row->cross_filter_pairs ?? null);
        $filterQr = self::decodeJsonField($row->filter_qr_counts ?? null);

        $total = 0;
        $qr = 0;

        if (count($activeFilters) === 1) {
            $filterKey = array_key_first($activeFilters);
            $filterValue = $activeFilters[$filterKey];
            $total = self::filteredTotalFromMarginal($row, $filterKey, $filterValue);

            $slice = $cross[$filterKey][$filterValue] ?? [];
            self::mergeBreakdownSliceIntoState($state, $slice);

            $qr = (int) ($filterQr["{$filterKey}:{$filterValue}"] ?? 0);
        } elseif (count($activeFilters) === 2) {
            $pairKey = self::compositeFilterKey($activeFilters);
            $pairSlice = $pairs[$pairKey] ?? [];

            if ($pairSlice !== []) {
                $total = (int) ($pairSlice['_total'] ?? 0);
                $qr = (int) ($pairSlice['_qr'] ?? 0);
                unset($pairSlice['_total'], $pairSlice['_qr']);
                self::mergeBreakdownSliceIntoState($state, $pairSlice);
            } else {
                // Missing pair rollups must not fall back to min(marginals) — that overcounts.
                $total = 0;
            }
        }

        return ['total' => $total, 'qr' => $qr];
    }

    /**
     * @param  array<string, string>  $filters
     */
    public static function compositeFilterKey(array $filters): string
    {
        $normalized = self::normalizeFilters($filters);
        ksort($normalized);

        $parts = [];
        foreach ($normalized as $key => $value) {
            $parts[] = "{$key}:{$value}";
        }

        return implode('|', $parts);
    }

    /**
     * @param  array<string, string>  $filters
     */
    public static function supportsDailyCrossRead(array $filters): bool
    {
        $active = self::normalizeFilters($filters);

        return count($active) >= 1 && count($active) <= 2;
    }

    /**
     * @param  array<string, mixed>  $bucket
     */
    public static function exportForPersistence(array $bucket): array
    {
        return [
            'device_stats' => $bucket['device_stats'],
            'browser_stats' => $bucket['browser_stats'],
            'os_stats' => $bucket['os_stats'],
            'country_stats' => $bucket['country_stats'],
            'city_stats' => $bucket['city_stats'],
            'referer_stats' => $bucket['referer_stats'],
            'utm_source_stats' => $bucket['utm_source_stats'],
            'utm_medium_stats' => $bucket['utm_medium_stats'],
            'utm_campaign_stats' => $bucket['utm_campaign_stats'],
            'utm_terms' => $bucket['utm_terms'],
            'utm_contents' => $bucket['utm_contents'],
            'browser_versions' => $bucket['browser_versions'],
            'os_versions' => $bucket['os_versions'],
            'language_stats' => $bucket['language_stats'],
            'variant_stats' => $bucket['variant_stats'],
            'cross_dimensional_stats' => $bucket['cross_dimensional_stats'],
            'cross_filter_pairs' => $bucket['cross_filter_pairs'],
            'filter_qr_counts' => $bucket['filter_qr_counts'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function normalizeFilters(array $filters): array
    {
        $normalized = [];

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $canonical = match ($key) {
                'country' => 'country_code',
                'device' => 'device_type',
                default => $key,
            };

            if (! in_array($canonical, self::FILTER_KEYS, true)) {
                continue;
            }

            $normalized[$canonical] = (string) $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $filterValues
     * @param  array<string, mixed>  $bucket
     */
    private static function accumulateFilterPairs(array $filterValues, object $row, array &$bucket, bool $isQr): void
    {
        $keys = array_keys($filterValues);
        $count = count($keys);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $pairKey = self::compositeFilterKey([
                    $keys[$i] => $filterValues[$keys[$i]],
                    $keys[$j] => $filterValues[$keys[$j]],
                ]);

                $bucket['cross_filter_pairs'][$pairKey]['_total'] =
                    ($bucket['cross_filter_pairs'][$pairKey]['_total'] ?? 0) + 1;

                if ($isQr) {
                    $bucket['cross_filter_pairs'][$pairKey]['_qr'] =
                        ($bucket['cross_filter_pairs'][$pairKey]['_qr'] ?? 0) + 1;
                }

                foreach (self::breakdownValuesFromVisit($row) as $breakdownKey => $breakdownValue) {
                    $bucket['cross_filter_pairs'][$pairKey][$breakdownKey][$breakdownValue] =
                        ($bucket['cross_filter_pairs'][$pairKey][$breakdownKey][$breakdownValue] ?? 0) + 1;
                }
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private static function extractFilterValuesFromVisit(object $row): array
    {
        $values = [];

        $country = self::stringOrNull($row->country_code ?? null);
        if ($country !== null) {
            $values['country_code'] = strtoupper($country);
        }

        $city = self::stringOrNull($row->city ?? null);
        if ($city !== null) {
            $values['city'] = $country ? "{$city} ({$country})" : $city;
        }

        foreach ([
            'browser' => 'browser',
            'operating_system' => 'operating_system',
            'browser_language' => 'browser_language',
            'device_type' => 'device_type',
            'utm_source' => 'utm_source',
            'utm_medium' => 'utm_medium',
            'utm_campaign' => 'utm_campaign',
            'selected_variant' => 'selected_variant',
        ] as $key => $column) {
            $value = self::stringOrNull($row->{$column} ?? null);
            if ($value !== null) {
                $values[$key] = $value;
            }
        }

        $values['referrer_category'] = ShortUrl::resolveRefererCategory(
            self::stringOrNull($row->referer_host ?? null) ?: 'Direct'
        );

        return $values;
    }

    /**
     * @return array<string, string>
     */
    private static function breakdownValuesFromVisit(object $row): array
    {
        $values = [];

        foreach (self::STATE_BREAKDOWN_MAP as $breakdownKey) {
            $value = match ($breakdownKey) {
                'country_code' => self::stringOrNull($row->country_code ?? null),
                'city' => self::formatCityKey($row),
                'referrer_category' => ShortUrl::resolveRefererCategory(
                    self::stringOrNull($row->referer_host ?? null) ?: 'Direct'
                ),
                'device_type' => self::stringOrNull($row->device_type ?? null),
                'browser' => self::stringOrNull($row->browser ?? null),
                'operating_system' => self::stringOrNull($row->operating_system ?? null),
                'utm_source' => self::stringOrNull($row->utm_source ?? null),
                'utm_medium' => self::stringOrNull($row->utm_medium ?? null),
                'utm_campaign' => self::stringOrNull($row->utm_campaign ?? null),
                'utm_term' => self::stringOrNull($row->utm_term ?? null),
                'utm_content' => self::stringOrNull($row->utm_content ?? null),
                'browser_language' => self::stringOrNull($row->browser_language ?? null),
                'selected_variant' => self::stringOrNull($row->selected_variant ?? null),
                default => null,
            };

            if ($value === null || $value === '') {
                continue;
            }

            if ($breakdownKey === 'country_code') {
                $value = strtoupper($value);
            }

            $values[$breakdownKey] = $value;
        }

        $browser = self::stringOrNull($row->browser ?? null);
        if ($browser !== null) {
            $version = self::stringOrNull($row->browser_version ?? null) ?: 'Unknown';
            $values['browser_version'] = "{$browser}\0{$version}";
        }

        $os = self::stringOrNull($row->operating_system ?? null);
        if ($os !== null) {
            $osVersion = self::stringOrNull($row->operating_system_version ?? null) ?: 'Unknown';
            $values['operating_system_version'] = "{$os}\0{$osVersion}";
        }

        return $values;
    }

    /**
     * @param  array<string, array<string, int>>  $slice
     * @param  array<string, mixed>  $state
     */
    private static function mergeBreakdownSliceIntoState(array &$state, array $slice): void
    {
        foreach (self::STATE_BREAKDOWN_MAP as $stateKey => $breakdownKey) {
            if (! isset($slice[$breakdownKey]) || ! is_array($slice[$breakdownKey])) {
                continue;
            }

            foreach ($slice[$breakdownKey] as $label => $count) {
                $state[$stateKey][$label] = ($state[$stateKey][$label] ?? 0) + (int) $count;
            }
        }

        if (isset($slice['browser_version']) && is_array($slice['browser_version'])) {
            foreach ($slice['browser_version'] as $compound => $count) {
                [$browser, $version] = array_pad(explode("\0", (string) $compound, 2), 2, 'Unknown');
                $state['visitsByBrowserVersion'][$browser][$version] =
                    ($state['visitsByBrowserVersion'][$browser][$version] ?? 0) + (int) $count;
            }
        }

        if (isset($slice['operating_system_version']) && is_array($slice['operating_system_version'])) {
            foreach ($slice['operating_system_version'] as $compound => $count) {
                [$os, $version] = array_pad(explode("\0", (string) $compound, 2), 2, 'Unknown');
                $state['visitsByOsVersion'][$os][$version] =
                    ($state['visitsByOsVersion'][$os][$version] ?? 0) + (int) $count;
            }
        }
    }

    private static function filteredTotalFromMarginal(object $row, string $filterKey, string $filterValue): int
    {
        $column = match ($filterKey) {
            'country_code' => 'country_stats',
            'city' => 'city_stats',
            'browser' => 'browser_stats',
            'operating_system' => 'os_stats',
            'device_type' => 'device_stats',
            'utm_source' => 'utm_source_stats',
            'utm_medium' => 'utm_medium_stats',
            'utm_campaign' => 'utm_campaign_stats',
            'browser_language' => 'language_stats',
            'selected_variant' => 'variant_stats',
            'referrer_category' => 'referer_stats',
            default => null,
        };

        if ($filterKey === 'referrer_category') {
            $refererStats = self::decodeJsonField($row->referer_stats ?? null);
            $total = 0;
            foreach ($refererStats as $host => $count) {
                if (ShortUrl::resolveRefererCategory((string) $host) === $filterValue) {
                    $total += (int) $count;
                }
            }

            return $total;
        }

        if ($column === null) {
            return 0;
        }

        $stats = self::decodeJsonField($row->{$column} ?? null);
        $lookupValue = $filterKey === 'country_code' ? strtoupper($filterValue) : $filterValue;

        return (int) ($stats[$lookupValue] ?? 0);
    }

    /**
     * @param  array<string, string>  $filters
     */
    private static function filteredTotalFromMarginalPair(object $row, array $filters): int
    {
        $minimum = PHP_INT_MAX;

        foreach ($filters as $filterKey => $filterValue) {
            $minimum = min($minimum, self::filteredTotalFromMarginal($row, $filterKey, $filterValue));
        }

        return $minimum === PHP_INT_MAX ? 0 : $minimum;
    }

    /**
     * @param  array<string, array<string, int>>  $bucket
     */
    private static function incrementMarginal(array &$bucket, string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $bucket[$key][$value] = ($bucket[$key][$value] ?? 0) + 1;
    }

    private static function formatCityKey(object $row): ?string
    {
        $city = self::stringOrNull($row->city ?? null);
        if ($city === null) {
            return null;
        }

        $country = self::stringOrNull($row->country_code ?? null);

        return $country ? "{$city} ({$country})" : $city;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * @return array<string, mixed>
     */
    private static function decodeJsonField(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
