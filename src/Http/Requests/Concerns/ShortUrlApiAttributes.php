<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Requests\Concerns;

use Bjanczak\FilamentShortUrl\Models\ShortUrlFolder;
use Bjanczak\FilamentShortUrl\Models\ShortUrlTag;
use Bjanczak\FilamentShortUrl\Support\CustomDomainValidator;
use Bjanczak\FilamentShortUrl\Support\ResourceOwnershipValidator;

trait ShortUrlApiAttributes
{
    /**
     * @return array<string, mixed>
     */
    protected function apiAttributeRules(): array
    {
        $ownerUserId = CustomDomainValidator::ownerUserIdFromRequest($this);

        return [
            'folder_id' => [
                'nullable',
                'integer',
                'exists:short_url_folders,id',
                ResourceOwnershipValidator::ownershipClosure(ShortUrlFolder::class, $ownerUserId),
            ],
            'tag_ids' => 'nullable|array|max:5',
            'tag_ids.*' => [
                'integer',
                'exists:short_url_tags,id',
                ResourceOwnershipValidator::ownershipClosure(ShortUrlTag::class, $ownerUserId),
            ],
            'is_archived' => 'sometimes|boolean',
            'is_cloaked' => 'sometimes|boolean',
            'do_index' => 'sometimes|boolean',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|url|max:2048',
            'external_id' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'ref' => 'nullable|string|max:255',
            'public_stats_enabled' => 'sometimes|boolean',
            'public_stats_password' => 'nullable|string|max:255',
        ];
    }
}
