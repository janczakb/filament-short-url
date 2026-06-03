<?php

namespace Bjanczak\FilamentShortUrl\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShortUrlPixel extends Model
{
    protected $fillable = [
        'name',
        'type',
        'pixel_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the short URLs associated with this pixel.
     */
    public function shortUrls(): BelongsToMany
    {
        return $this->belongsToMany(ShortUrl::class, 'short_url_pixel', 'pixel_id', 'short_url_id');
    }
}
