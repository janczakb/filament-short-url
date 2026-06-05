<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class AppLinkingTab
{
    /**
     * Build the app linking details form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_app_linking'))
            ->icon('heroicon-o-device-phone-mobile')
            ->schema([
                Section::make(__('filament-short-url::default.form_section_app_linking'))
                    ->schema([
                        Toggle::make('auto_open_app_mobile')
                            ->label(__('filament-short-url::default.auto_open_app_mobile'))
                            ->helperText(__('filament-short-url::default.auto_open_app_mobile_helper'))
                            ->default(false)
                            ->inline(false)
                            ->live(),

                        ViewField::make('app_linking_preview')
                            ->view('filament-short-url::app-linking-preview')
                            ->viewData(fn (Get $get) => [
                                'destinationUrl' => $get('destination_url'),
                            ])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('auto_open_app_mobile')),
                    ]),
            ]);
    }
}
