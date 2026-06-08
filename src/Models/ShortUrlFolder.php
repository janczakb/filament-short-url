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
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortUrlFolder extends Model
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

    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('filament-short-url.user.model', User::class),
            'user_id'
        );
    }

    public static function getColors(): array
    {
        return [
            'gray' => ['bg' => '#f3f4f6', 'text' => '#4b5563', 'solid' => '#737373', 'label' => 'color_gray'],
            'red' => ['bg' => '#fee2e2', 'text' => '#dc2626', 'solid' => '#ef4444', 'label' => 'color_red'],
            'blue' => ['bg' => '#dbeafe', 'text' => '#2563eb', 'solid' => '#3b82f6', 'label' => 'color_blue'],
            'green' => ['bg' => '#dcfce7', 'text' => '#16a34a', 'solid' => '#10b981', 'label' => 'color_green'],
            'yellow' => ['bg' => '#fef9c3', 'text' => '#ca8a04', 'solid' => '#f59e0b', 'label' => 'color_yellow'],
            'indigo' => ['bg' => '#e0e7ff', 'text' => '#4f46e5', 'solid' => '#6366f1', 'label' => 'color_indigo'],
            'purple' => ['bg' => '#f3e8ff', 'text' => '#7c3aed', 'solid' => '#a855f7', 'label' => 'color_purple'],
            'pink' => ['bg' => '#fce7f3', 'text' => '#db2777', 'solid' => '#ec4899', 'label' => 'color_pink'],
        ];
    }

    public static function getColorOptions(): array
    {
        return collect(self::getColors())->mapWithKeys(fn ($item, $key) => [
            $key => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: '.$item['solid'].';"></span><span>'.__('filament-short-url::default.'.$item['label']).'</span></span>',
        ])->toArray();
    }

    public function getOptionHtml(): string
    {
        $color = $this->color ?? 'gray';
        $colors = self::getColors();
        $style = $colors[$color] ?? $colors['gray'];
        $bgColor = $style['bg'];
        $textColor = $style['text'];

        return '
            <div class="flex items-center gap-2">
                <span class="flex items-center justify-center rounded-lg w-7 h-7" style="background-color: '.$bgColor.'; color: '.$textColor.'; padding: 4px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                </span>
                <span class="font-medium text-gray-900 dark:text-gray-100">'.e($this->name).'</span>
            </div>
        ';
    }
}
