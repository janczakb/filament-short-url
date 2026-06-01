<?php

namespace Bjanczak\FilamentShortUrl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $short_url_id
 * @property string|null $ip_address
 * @property string|null $ip_hash
 * @property string|null $browser
 * @property string|null $browser_version
 * @property string|null $operating_system
 * @property string|null $operating_system_version
 * @property string|null $device_type
 * @property string|null $referer_url
 * @property string|null $country
 * @property string|null $country_code
 * @property Carbon $visited_at
 */
class ShortUrlVisit extends Model
{
    public $timestamps = false;

    protected $table = 'short_url_visits';

    protected $fillable = [
        'short_url_id',
        'ip_address',
        'ip_hash',
        'browser',
        'browser_version',
        'operating_system',
        'operating_system_version',
        'device_type',
        'referer_url',
        'country',
        'country_code',
        'visited_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(ShortUrl::class, 'short_url_id');
    }
}
