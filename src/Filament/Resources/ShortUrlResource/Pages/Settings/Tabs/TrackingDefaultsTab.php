<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class TrackingDefaultsTab
{
    /**
     * Build the tracking defaults form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_tracking_defaults'))
            ->key('tracking-defaults')
            ->icon('heroicon-o-chart-bar')
            ->schema([
                Section::make(__('filament-short-url::default.settings_section_tracking_defaults'))
                    ->description(__('filament-short-url::default.settings_section_tracking_defaults_helper'))
                    ->schema([
                        Toggle::make('tracking_enabled')
                            ->label(__('filament-short-url::default.settings_track_visits_default'))
                            ->live()
                            ->inline(false)
                            ->columnSpanFull(),

                        Toggle::make('tracking_anonymize_ips')
                            ->label(__('filament-short-url::default.settings_track_anonymize_ips'))
                            ->helperText(__('filament-short-url::default.settings_track_anonymize_ips_helper'))
                            ->inline(false)
                            ->columnSpanFull()
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_ip_address')
                            ->label(__('filament-short-url::default.settings_track_ip_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_browser')
                            ->label(__('filament-short-url::default.settings_track_browser_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_browser_version')
                            ->label(__('filament-short-url::default.settings_track_browser_version_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_operating_system')
                            ->label(__('filament-short-url::default.settings_track_os_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_operating_system_version')
                            ->label(__('filament-short-url::default.settings_track_os_version_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_browser_language')
                            ->label(__('filament-short-url::default.settings_track_browser_language_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_referer_url')
                            ->label(__('filament-short-url::default.settings_track_referer_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                        Toggle::make('tracking_fields_device_type')
                            ->label(__('filament-short-url::default.settings_track_device_type_default'))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),
                    ])
                    ->columns(4),
            ]);
    }
}
