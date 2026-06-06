<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShortUrlTag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (auth()->check() && empty($m->user_id)) {
                $m->user_id = auth()->id();
            }
        });
    }

    public function shortUrls(): BelongsToMany
    {
        return $this->belongsToMany(
            ShortUrl::class,
            'short_url_tag',
            'tag_id',
            'short_url_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-short-url.user.model', User::class),
            'user_id'
        );
    }
}
