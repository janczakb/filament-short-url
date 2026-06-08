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
        $tags = $this->relationLoaded('tags') ? $this->tags : $this->tags()->get();
        $folder = $this->relationLoaded('folder') ? $this->folder : $this->folder()->first();

        return [
            'id' => $this->id,
            'destination_type' => $this->destination_type,
            'destination_url' => $this->destination_url,
            'rotation_variants' => $this->rotation_variants,
            'custom_domain_id' => $this->custom_domain_id,
            'folder_id' => $this->folder_id,
            'folder' => $folder ? [
                'id' => $folder->id,
                'name' => $folder->name,
                'slug' => $folder->slug,
                'color' => $folder->color,
            ] : null,
            'url_key' => $this->url_key,
            'external_id' => $this->external_id,
            'short_url' => $this->getShortUrl(),
            'is_enabled' => (bool) $this->is_enabled,
            'is_archived' => (bool) $this->is_archived,
            'redirect_status_code' => (int) $this->redirect_status_code,
            'total_visits' => (int) $this->total_visits,
            'unique_visits' => (int) $this->unique_visits,
            'qr_scans' => (int) ($this->qr_scans ?? 0),
            'max_visits' => $this->max_visits ? (int) $this->max_visits : null,
            'single_use' => (bool) $this->single_use,
            'forward_query_params' => (bool) $this->forward_query_params,
            'activated_at' => $this->activated_at ? $this->activated_at->toIso8601String() : null,
            'deactivated_at' => $this->deactivated_at ? $this->deactivated_at->toIso8601String() : null,
            'expires_at' => $this->expires_at ? $this->expires_at->toIso8601String() : null,
            'expiration_redirect_url' => $this->expiration_redirect_url,
            'webhook_url' => $this->webhook_url,
            'targeting_rules' => $this->targeting_rules,
            'password_protected' => $this->hasPassword(),
            'show_warning_page' => (bool) $this->show_warning_page,
            'auto_open_app_mobile' => (bool) $this->auto_open_app_mobile,
            'is_cloaked' => (bool) $this->is_cloaked,
            'do_index' => (bool) $this->do_index,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
            'ref' => $this->ref,
            'public_stats_enabled' => (bool) $this->public_stats_enabled,
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
            'tags' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ])->toArray(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
