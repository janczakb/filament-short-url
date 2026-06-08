<?php

namespace Bjanczak\FilamentShortUrl\Models\Concerns;

use Illuminate\Support\Facades\DB;

trait HasStatsQueries
{
    /**
     * Apply active filter array to a visits query.
     */
    public function applyStatsFilters($query, array $filters): void
    {
        if (empty($filters)) {
            return;
        }

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            switch ($key) {
                case 'country':
                case 'country_code':
                    $query->where('country_code', $value);
                    break;
                case 'city':
                    if (preg_match('/^(.*?)\s*\((.*?)\)$/', $value, $matches)) {
                        $query->where('city', trim($matches[1]))
                            ->where('country_code', trim($matches[2]));
                    } else {
                        $query->where('city', $value);
                    }
                    break;
                case 'browser':
                    $query->where('browser', $value);
                    break;
                case 'operating_system':
                    $query->where('operating_system', $value);
                    break;
                case 'browser_language':
                    $query->where('browser_language', $value);
                    break;
                case 'device':
                case 'device_type':
                    $query->where('device_type', $value);
                    break;
                case 'referrer_category':
                    if ($value === 'Direct / Email / SMS') {
                        $query->where(fn ($q) => $q->whereNull('referer_host')->orWhere('referer_host', '')->orWhere('referer_host', 'direct'));
                    } elseif ($value === 'Twitter / X') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['t.co', 'twitter.com', 'x.com'])
                            ->orWhere('referer_host', 'like', '%.t.co')
                            ->orWhere('referer_host', 'like', '%.twitter.com')
                            ->orWhere('referer_host', 'like', '%.x.com')
                        );
                    } elseif ($value === 'Facebook') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['facebook.com', 'm.facebook.com', 'l.facebook.com', 'lm.facebook.com'])
                            ->orWhere('referer_host', 'like', '%.facebook.com')
                        );
                    } elseif ($value === 'Instagram') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['instagram.com', 'l.instagram.com'])
                            ->orWhere('referer_host', 'like', '%.instagram.com')
                        );
                    } elseif ($value === 'LinkedIn') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['linkedin.com', 'lnkd.in'])
                            ->orWhere('referer_host', 'like', '%.linkedin.com')
                            ->orWhere('referer_host', 'like', '%.lnkd.in')
                        );
                    } elseif ($value === 'YouTube') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['youtube.com', 'youtu.be'])
                            ->orWhere('referer_host', 'like', '%.youtube.com')
                            ->orWhere('referer_host', 'like', '%.youtu.be')
                        );
                    } elseif ($value === 'Pinterest') {
                        $query->where(fn ($q) => $q->whereIn('referer_host', ['pinterest.com', 'pin.it'])
                            ->orWhere('referer_host', 'like', '%.pinterest.com')
                            ->orWhere('referer_host', 'like', '%.pin.it')
                        );
                    } elseif ($value === 'Google') {
                        $query->where('referer_host', 'like', '%google.%');
                    } elseif ($value === 'Bing') {
                        $query->where('referer_host', 'like', '%bing.%');
                    } elseif ($value === 'Yahoo') {
                        $query->where('referer_host', 'like', '%yahoo.%');
                    } elseif ($value === 'DuckDuckGo') {
                        $query->where('referer_host', 'like', '%duckduckgo.%');
                    } else {
                        $query->where(fn ($q) => $q->where('referer_host', $value)->orWhere('referer_host', 'www.'.$value));
                    }
                    break;
                case 'utm_source':
                    $query->where('utm_source', $value);
                    break;
                case 'utm_medium':
                    $query->where('utm_medium', $value);
                    break;
                case 'utm_campaign':
                    $query->where('utm_campaign', $value);
                    break;
                case 'selected_variant':
                    $query->where('selected_variant', $value);
                    break;
            }
        }
    }

    /**
     * Group by and count a column on the raw visits query, applying filters.
     *
     * @return array<string, int>
     */
    protected function getRawVisitsDistribution($baseQuery, string $column, int $limit = 10): array
    {
        return (clone $baseQuery)
            ->select($column, DB::raw('COUNT(*) as cnt'))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('cnt')
            ->limit($limit)
            ->pluck('cnt', $column)
            ->toArray();
    }

    /**
     * Group by and count city/country_code on the raw visits query.
     *
     * @return array<string, int>
     */
    protected function getRawVisitsCityDistribution($baseQuery, int $limit = 10): array
    {
        $rows = (clone $baseQuery)
            ->select('city', 'country_code', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city', 'country_code')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($rows as $row) {
            $key = "{$row->city} ({$row->country_code})";
            $results[$key] = (int) $row->cnt;
        }

        return $results;
    }

    /**
     * Group by and count referer category on the raw visits query.
     *
     * @return array<string, int>
     */
    protected function getRawVisitsRefererDistribution($baseQuery, int $limit = 10): array
    {
        $rows = (clone $baseQuery)
            ->select('referer_host', DB::raw('COUNT(*) as cnt'))
            ->groupBy('referer_host')
            ->orderByDesc('cnt')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($rows as $row) {
            $host = $row->referer_host ?: 'Direct';
            $category = static::resolveRefererCategory($host);
            $results[$category] = ($results[$category] ?? 0) + (int) $row->cnt;
        }

        arsort($results);

        return array_slice($results, 0, $limit, true);
    }

    /**
     * Group by name and version on the raw visits query, applying filters.
     *
     * @return array<string, array<string, int>>
     */
    protected function getRawVisitsVersionDistribution($baseQuery, string $nameColumn, string $versionColumn, int $limit = 5): array
    {
        $rows = (clone $baseQuery)
            ->select($nameColumn, $versionColumn, DB::raw('COUNT(*) as cnt'))
            ->whereNotNull($nameColumn)
            ->where($nameColumn, '!=', '')
            ->groupBy($nameColumn, $versionColumn)
            ->orderByDesc('cnt')
            ->get();

        $results = [];
        foreach ($rows as $row) {
            $name = $row->$nameColumn;
            $version = $row->$versionColumn ?: 'Unknown';
            if (! isset($results[$name])) {
                $results[$name] = [];
            }
            if (count($results[$name]) < $limit) {
                $results[$name][$version] = (int) $row->cnt;
            }
        }

        return $results;
    }

    /**
     * Map a raw host to a clean Referer Category name.
     */
    public static function resolveRefererCategory(?string $host): string
    {
        if (empty($host) || strtolower($host) === 'direct') {
            return 'Direct / Email / SMS';
        }

        $host = strtolower(trim($host));

        $map = [
            't.co' => 'Twitter / X',
            'twitter.com' => 'Twitter / X',
            'x.com' => 'Twitter / X',
            'facebook.com' => 'Facebook',
            'm.facebook.com' => 'Facebook',
            'l.facebook.com' => 'Facebook',
            'lm.facebook.com' => 'Facebook',
            'instagram.com' => 'Instagram',
            'l.instagram.com' => 'Instagram',
            'linkedin.com' => 'LinkedIn',
            'lnkd.in' => 'LinkedIn',
            'youtube.com' => 'YouTube',
            'youtu.be' => 'YouTube',
            'tiktok.com' => 'TikTok',
            'reddit.com' => 'Reddit',
            'pinterest.com' => 'Pinterest',
            'pin.it' => 'Pinterest',
            'google.com' => 'Google',
            'google.pl' => 'Google',
            'google.co.uk' => 'Google',
            'google.de' => 'Google',
            'google.fr' => 'Google',
        ];

        foreach ($map as $domain => $name) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return $name;
            }
        }

        if (str_contains($host, 'google.')) {
            return 'Google';
        }
        if (str_contains($host, 'bing.')) {
            return 'Bing';
        }
        if (str_contains($host, 'yahoo.')) {
            return 'Yahoo';
        }
        if (str_contains($host, 'duckduckgo.')) {
            return 'DuckDuckGo';
        }

        if (str_starts_with($host, 'www.')) {
            return substr($host, 4);
        }

        return $host;
    }
}
