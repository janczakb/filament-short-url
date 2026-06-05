<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class QrDefaultsTab
{
    /**
     * Build the QR defaults settings form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_qr'))
            ->key('qr-defaults')
            ->icon('heroicon-o-qr-code')
            ->schema([
                Section::make(__('filament-short-url::default.settings_section_qr_defaults'))
                    ->description(__('filament-short-url::default.settings_section_qr_defaults_helper'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('qr_size')
                            ->label(__('filament-short-url::default.settings_qr_size'))
                            ->numeric()
                            ->integer()
                            ->minValue(100)
                            ->maxValue(2000)
                            ->required(),

                        Select::make('qr_margin')
                            ->label(__('filament-short-url::default.settings_qr_margin'))
                            ->options(array_combine(range(0, 10), range(0, 10)))
                            ->required(),

                        Select::make('qr_dot_style')
                            ->label(__('filament-short-url::default.settings_qr_dot_style'))
                            ->options([
                                'square' => __('filament-short-url::default.qr_option_square'),
                                'dots' => __('filament-short-url::default.qr_option_dots'),
                                'rounded' => __('filament-short-url::default.qr_option_rounded'),
                                'classy' => __('filament-short-url::default.qr_option_classy'),
                                'classy-rounded' => __('filament-short-url::default.qr_option_classy_rounded'),
                                'extra-rounded' => __('filament-short-url::default.qr_option_extra_rounded'),
                            ])
                            ->required(),

                        ColorPicker::make('qr_foreground_color')
                            ->label(__('filament-short-url::default.settings_qr_foreground_color'))
                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                            ->placeholder('#000000')
                            ->required(),

                        ColorPicker::make('qr_background_color')
                            ->label(__('filament-short-url::default.settings_qr_background_color'))
                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                            ->placeholder('#ffffff')
                            ->required(),

                        Toggle::make('qr_gradient_enabled')
                            ->label(__('filament-short-url::default.settings_qr_gradient_enabled'))
                            ->live()
                            ->inline(false),

                        ColorPicker::make('qr_gradient_from')
                            ->label(__('filament-short-url::default.settings_qr_gradient_from'))
                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                            ->placeholder('#4f46e5')
                            ->required(fn (Get $get): bool => (bool) $get('qr_gradient_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('qr_gradient_enabled')),

                        ColorPicker::make('qr_gradient_to')
                            ->label(__('filament-short-url::default.settings_qr_gradient_to'))
                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                            ->placeholder('#06b6d4')
                            ->required(fn (Get $get): bool => (bool) $get('qr_gradient_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('qr_gradient_enabled')),

                        Select::make('qr_gradient_type')
                            ->label(__('filament-short-url::default.settings_qr_gradient_type'))
                            ->options([
                                'linear' => __('filament-short-url::default.qr_gradient_linear'),
                                'radial' => __('filament-short-url::default.qr_gradient_radial'),
                            ])
                            ->required(fn (Get $get): bool => (bool) $get('qr_gradient_enabled'))
                            ->visible(fn (Get $get): bool => (bool) $get('qr_gradient_enabled')),
                    ]),
            ]);
    }
}
