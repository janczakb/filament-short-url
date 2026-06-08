<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support;

use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;

use function Filament\Support\generate_icon_html;

class TabCardHeader
{
    public static function make(
        string $heroicon,
        string $iconColorClass,
        string $titleKey,
        string $subtitleKey,
        bool $compact = false,
    ): HtmlString {
        $icon = generate_icon_html($heroicon, attributes: new ComponentAttributeBag([
            'class' => 'fsu-tab-card-icon-svg',
        ]));

        $headerClass = $compact
            ? 'validity-tab-card-header validity-tab-card-header--compact'
            : 'validity-tab-card-header';

        return new HtmlString(
            '<div class="'.e($headerClass).'">'.
            '<div class="validity-tab-card-icon '.e($iconColorClass).'">'.
            ($icon?->toHtml() ?? '').
            '</div>'.
            '<div class="validity-tab-card-toolbar-main">'.
            '<p class="validity-tab-card-title">'.e(__("filament-short-url::default.{$titleKey}")).'</p>'.
            '<p class="validity-tab-card-subtitle">'.e(__("filament-short-url::default.{$subtitleKey}")).'</p>'.
            '</div>'.
            '</div>'
        );
    }
}
