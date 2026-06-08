<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $short_url_id
 * @property Carbon $date
 * @property int $visits_count
 * @property int $unique_visits_count
 * @property array|null $device_stats
 * @property array|null $browser_stats
 * @property array|null $os_stats
 * @property array|null $country_stats
 * @property array|null $city_stats
 * @property array|null $referer_stats
 * @property array|null $utm_source_stats
 * @property array|null $utm_medium_stats
 * @property array|null $utm_campaign_stats
 * @property array|null $utm_terms
 * @property array|null $utm_contents
 * @property array|null $browser_versions
 * @property array|null $os_versions
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ShortUrlDailyStats extends Model
{
    protected $table = 'short_url_daily_stats';

    protected $fillable = [
        'short_url_id',
        'date',
        'visits_count',
        'unique_visits_count',
        'all_visits_count',
        'bot_visits_count',
        'proxy_visits_count',
        'device_stats',
        'browser_stats',
        'os_stats',
        'country_stats',
        'city_stats',
        'referer_stats',
        'utm_source_stats',
        'utm_medium_stats',
        'utm_campaign_stats',
        'utm_terms',
        'utm_contents',
        'browser_versions',
        'os_versions',
        'qr_visits_count',
        'language_stats',
        'variant_stats',
        'cross_dimensional_stats',
        'cross_filter_pairs',
        'filter_qr_counts',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'date',
        'visits_count' => 'integer',
        'unique_visits_count' => 'integer',
        'all_visits_count' => 'integer',
        'bot_visits_count' => 'integer',
        'proxy_visits_count' => 'integer',
        'device_stats' => 'array',
        'browser_stats' => 'array',
        'os_stats' => 'array',
        'country_stats' => 'array',
        'city_stats' => 'array',
        'referer_stats' => 'array',
        'utm_source_stats' => 'array',
        'utm_medium_stats' => 'array',
        'utm_campaign_stats' => 'array',
        'utm_terms' => 'array',
        'utm_contents' => 'array',
        'browser_versions' => 'array',
        'os_versions' => 'array',
        'qr_visits_count' => 'integer',
        'language_stats' => 'array',
        'variant_stats' => 'array',
        'cross_dimensional_stats' => 'array',
        'cross_filter_pairs' => 'array',
        'filter_qr_counts' => 'array',
    ];

    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(ShortUrl::class, 'short_url_id');
    }
}
