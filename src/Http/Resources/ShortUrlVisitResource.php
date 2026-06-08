<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortUrlVisitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'short_url_id' => $this->short_url_id,
            'visited_at' => $this->visited_at?->toIso8601String(),
            'browser' => $this->browser,
            'browser_version' => $this->browser_version,
            'operating_system' => $this->operating_system,
            'operating_system_version' => $this->operating_system_version,
            'device_type' => $this->device_type,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'city' => $this->city,
            'referer_url' => $this->referer_url,
            'referer_host' => $this->referer_host,
            'is_proxy' => (bool) $this->is_proxy,
            'is_qr_scan' => (bool) $this->is_qr_scan,
            'browser_language' => $this->browser_language,
            'selected_variant' => $this->selected_variant,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
        ];
    }
}
