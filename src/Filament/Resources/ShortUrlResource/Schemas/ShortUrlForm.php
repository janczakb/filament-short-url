<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\AppLinkingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\LinkTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\MarketingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\QrDesignTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\TargetingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\TrackingTab;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class ShortUrlForm
{
    /**
     * Configure the short URL resource form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->tabs([
                    LinkTab::make(),
                    TargetingTab::make(),
                    AppLinkingTab::make(),
                    TrackingTab::make(),
                    MarketingTab::make(),
                    QrDesignTab::make(),
                ])->columnSpanFull(),
        ]);
    }
}
