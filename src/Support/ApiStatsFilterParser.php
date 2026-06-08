<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Support;

use Bjanczak\FilamentShortUrl\Services\Stats\CrossDimensionalStatsEngine;
use Illuminate\Http\Request;

class ApiStatsFilterParser
{
    /**
     * @return array<string, string>
     */
    public static function fromRequest(Request $request): array
    {
        $filters = [];

        foreach (CrossDimensionalStatsEngine::FILTER_KEYS as $key) {
            $value = $request->query($key);

            if (is_string($value) && $value !== '') {
                $filters[$key] = $value;
            }
        }

        if ($request->filled('country')) {
            $filters['country_code'] = (string) $request->query('country');
        }

        if ($request->filled('device')) {
            $filters['device_type'] = (string) $request->query('device');
        }

        return $filters;
    }

    /**
     * @return array<string, string>
     */
    public static function validationRules(): array
    {
        $rules = [
            'country' => 'nullable|string|size:2',
            'device' => 'nullable|string|max:64',
        ];

        foreach (CrossDimensionalStatsEngine::FILTER_KEYS as $key) {
            $rules[$key] = 'nullable|string|max:255';
        }

        return $rules;
    }
}
