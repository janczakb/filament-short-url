<?php

/**
 * @package    janczakb/filament-short-url
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
        'device_stats',
        'browser_stats',
        'os_stats',
        'country_stats',
        'city_stats',
        'referer_stats',
        'utm_source_stats',
        'utm_medium_stats',
        'utm_campaign_stats',
        'qr_visits_count',
        'language_stats',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'date' => 'date',
        'visits_count' => 'integer',
        'unique_visits_count' => 'integer',
        'device_stats' => 'array',
        'browser_stats' => 'array',
        'os_stats' => 'array',
        'country_stats' => 'array',
        'city_stats' => 'array',
        'referer_stats' => 'array',
        'utm_source_stats' => 'array',
        'utm_medium_stats' => 'array',
        'utm_campaign_stats' => 'array',
        'qr_visits_count' => 'integer',
        'language_stats' => 'array',
    ];

    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(ShortUrl::class, 'short_url_id');
    }
}
