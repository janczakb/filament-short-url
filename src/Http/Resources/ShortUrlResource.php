<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortUrlResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pixels = $this->relationLoaded('pixels') ? $this->pixels : $this->pixels()->get();

        return [
            'id' => $this->id,
            'destination_type' => $this->destination_type,
            'destination_url' => $this->destination_url,
            'rotation_variants' => $this->rotation_variants,
            'custom_domain_id' => $this->custom_domain_id,
            'url_key' => $this->url_key,
            'short_url' => $this->getShortUrl(),
            'is_enabled' => (bool) $this->is_enabled,
            'redirect_status_code' => (int) $this->redirect_status_code,
            'total_visits' => (int) $this->total_visits,
            'unique_visits' => (int) $this->unique_visits,
            'max_visits' => $this->max_visits ? (int) $this->max_visits : null,
            'activated_at' => $this->activated_at ? $this->activated_at->toIso8601String() : null,
            'expires_at' => $this->expires_at ? $this->expires_at->toIso8601String() : null,
            'webhook_url' => $this->webhook_url,
            'targeting_rules' => $this->targeting_rules,
            'password' => $this->password,
            'show_warning_page' => (bool) $this->show_warning_page,
            'auto_open_app_mobile' => (bool) $this->auto_open_app_mobile,
            'ga_tracking_id' => $this->ga_tracking_id,
            'track_visits' => (bool) $this->track_visits,
            'track_ip_address' => (bool) $this->track_ip_address,
            'track_browser' => (bool) $this->track_browser,
            'track_browser_version' => (bool) $this->track_browser_version,
            'track_operating_system' => (bool) $this->track_operating_system,
            'track_operating_system_version' => (bool) $this->track_operating_system_version,
            'track_device_type' => (bool) $this->track_device_type,
            'track_referer_url' => (bool) $this->track_referer_url,
            'track_browser_language' => (bool) $this->track_browser_language,
            'pixels' => $pixels->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'pixel_id' => $p->pixel_id,
                'is_active' => (bool) $p->is_active,
            ])->toArray(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
