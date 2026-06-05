<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class GeoIpTab
{
    /**
     * Build the geo-ip settings form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_geoip'))
            ->key('geoip')
            ->icon('heroicon-o-globe-alt')
            ->schema([
                Section::make(__('filament-short-url::default.settings_section_geoip'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('geo_ip_enabled')
                            ->label(__('filament-short-url::default.settings_geoip_enabled'))
                            ->helperText(__('filament-short-url::default.settings_geoip_enabled_helper'))
                            ->columnSpanFull()
                            ->inline(false)
                            ->live(),

                        Select::make('geo_ip_driver')
                            ->label(__('filament-short-url::default.settings_geoip_driver'))
                            ->helperText(__('filament-short-url::default.settings_geoip_driver_helper'))
                            ->options([
                                'headers' => __('filament-short-url::default.settings_geoip_driver_headers'),
                                'maxmind' => __('filament-short-url::default.settings_geoip_driver_maxmind'),
                                'ip-api' => __('filament-short-url::default.settings_geoip_driver_ipapi'),
                            ])
                            ->required()
                            ->live()
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled')),

                        Placeholder::make('geoip_headers_warning')
                            ->content(function () {
                                $html = __('filament-short-url::default.settings_geoip_headers_warning');

                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled') &&
                                $get('geo_ip_driver') === 'headers' &&
                                ! (bool) $get('trust_cdn_headers')
                            )
                            ->columnSpanFull(),

                        TextInput::make('geo_ip_cache_ttl')
                            ->label(__('filament-short-url::default.settings_geoip_cache_ttl'))
                            ->helperText(__('filament-short-url::default.settings_geoip_cache_ttl_helper'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(31536000)
                            ->suffix('s')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled')),

                        TextInput::make('geo_ip_stats_cache_ttl')
                            ->label(__('filament-short-url::default.settings_geoip_stats_cache_ttl'))
                            ->helperText(__('filament-short-url::default.settings_geoip_stats_cache_ttl_helper'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(86400)
                            ->suffix('s')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled')),

                        TextInput::make('geo_ip_timeout')
                            ->label(__('filament-short-url::default.settings_geoip_timeout'))
                            ->helperText(__('filament-short-url::default.settings_geoip_timeout_helper'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(30)
                            ->suffix('s')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled') && $get('geo_ip_driver') === 'ip-api'),

                        Placeholder::make('maxmind_info')
                            ->content(function () {
                                $html = __('filament-short-url::default.settings_maxmind_info_callout');

                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled') && $get('geo_ip_driver') === 'maxmind')
                            ->columnSpanFull(),

                        TextInput::make('maxmind_database_path')
                            ->label(__('filament-short-url::default.settings_maxmind_path'))
                            ->helperText(__('filament-short-url::default.settings_maxmind_path_helper'))
                            ->columnSpanFull()
                            ->placeholder('/var/www/html/database/geoip/GeoLite2-Country.mmdb')
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled') && $get('geo_ip_driver') === 'maxmind'),

                        Actions::make([
                            Action::make('verifyMaxmindPath')
                                ->label(__('filament-short-url::default.settings_maxmind_verify'))
                                ->icon('heroicon-o-check-circle')
                                ->color('gray')
                                ->action(function (Get $get): void {
                                    $path = trim($get('maxmind_database_path') ?? '');

                                    if (empty($path)) {
                                        Notification::make()
                                            ->title(__('filament-short-url::default.settings_maxmind_verify_empty'))
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    if (file_exists($path) && is_readable($path) && str_ends_with($path, '.mmdb')) {
                                        $sizeKb = round(filesize($path) / 1024);
                                        Notification::make()
                                            ->title(__('filament-short-url::default.settings_maxmind_verify_ok'))
                                            ->body("{$path} ({$sizeKb} KB)")
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title(__('filament-short-url::default.settings_maxmind_verify_fail'))
                                            ->body($path)
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])
                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled') && $get('geo_ip_driver') === 'maxmind'),
                    ]),
            ]);
    }
}
