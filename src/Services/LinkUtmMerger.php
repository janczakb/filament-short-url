<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Services;

class LinkUtmMerger
{
    /**
     * Append stored link-level UTM/ref parameters without overwriting existing destination query keys.
     */
    public function merge(string $destination, object $link): string
    {
        $stored = array_filter([
            'utm_source' => $link->utm_source ?? null,
            'utm_medium' => $link->utm_medium ?? null,
            'utm_campaign' => $link->utm_campaign ?? null,
            'utm_term' => $link->utm_term ?? null,
            'utm_content' => $link->utm_content ?? null,
            'ref' => $link->ref ?? null,
        ], fn (?string $value): bool => filled($value));

        if ($stored === []) {
            return $destination;
        }

        $parts = parse_url($destination);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return $destination;
        }

        parse_str($parts['query'] ?? '', $existing);
        $merged = array_merge($stored, $existing);
        $query = http_build_query($merged);

        $path = $parts['path'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port.$path.($query !== '' ? '?'.$query : '').$fragment;
    }
}
