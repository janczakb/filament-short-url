<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class DeepLinkingTab
{
    /**
     * Build the deep linking settings form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_deep_linking'))
            ->key('deep-linking')
            ->icon('heroicon-o-device-phone-mobile')
            ->schema([
                Section::make(__('filament-short-url::default.settings_section_deep_linking'))
                    ->schema([
                        Toggle::make('deep_linking_enabled')
                            ->label(__('filament-short-url::default.settings_deep_linking_enabled'))
                            ->helperText(__('filament-short-url::default.settings_deep_linking_enabled_helper'))
                            ->default(false)
                            ->inline(false)
                            ->live(),

                        Textarea::make('aasa_json')
                            ->label(__('filament-short-url::default.settings_aasa_json'))
                            ->helperText(__('filament-short-url::default.settings_aasa_json_helper'))
                            ->nullable()
                            ->visible(fn (Get $get): bool => (bool) $get('deep_linking_enabled'))
                            ->columnSpanFull()
                            ->extraInputAttributes(['style' => 'font-family: monospace;'])
                            ->rows(8)
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (empty($value)) {
                                            return;
                                        }
                                        json_decode($value);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $fail(__('filament-short-url::default.validation_invalid_json'));
                                        }
                                    };
                                },
                            ]),

                        Textarea::make('assetlinks_json')
                            ->label(__('filament-short-url::default.settings_assetlinks_json'))
                            ->helperText(__('filament-short-url::default.settings_assetlinks_json_helper'))
                            ->nullable()
                            ->visible(fn (Get $get): bool => (bool) $get('deep_linking_enabled'))
                            ->columnSpanFull()
                            ->extraInputAttributes(['style' => 'font-family: monospace;'])
                            ->rows(8)
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (empty($value)) {
                                            return;
                                        }
                                        json_decode($value);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $fail(__('filament-short-url::default.validation_invalid_json'));
                                        }
                                    };
                                },
                            ]),
                    ]),
            ]);
    }
}
