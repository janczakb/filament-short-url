<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class TrackingTab
{
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_tracking'))
            ->icon('heroicon-o-chart-bar')
            ->schema([
                Section::make(__('filament-short-url::default.form_section_tracking'))
                    ->description('Włącz lub wyłącz globalne śledzenie ruchu i statystyk dla tego skróconego linku.')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->aside()
                    ->schema([
                        Toggle::make('track_visits')
                            ->label(__('filament-short-url::default.track_visits'))
                            ->default(fn () => config('filament-short-url.tracking.enabled', true))
                            ->live()
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-short-url::default.form_section_tracked_fields'))
                    ->description('Dostosuj poziom szczegółowości zbieranych danych, włączając lub wyłączając konkretne metryki ze względów analitycznych i prywatności.')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->aside()
                    ->hidden(fn (Get $get): bool => ! $get('track_visits'))
                    ->schema([
                        Toggle::make('track_ip_address')
                            ->label(__('filament-short-url::default.track_ip'))
                            ->default(fn () => config('filament-short-url.tracking.fields.ip_address', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_browser')
                            ->label(__('filament-short-url::default.track_browser'))
                            ->default(fn () => config('filament-short-url.tracking.fields.browser', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_browser_version')
                            ->label(__('filament-short-url::default.track_browser_version'))
                            ->default(fn () => config('filament-short-url.tracking.fields.browser_version', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_operating_system')
                            ->label(__('filament-short-url::default.track_os'))
                            ->default(fn () => config('filament-short-url.tracking.fields.operating_system', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_operating_system_version')
                            ->label(__('filament-short-url::default.track_os_version'))
                            ->default(fn () => config('filament-short-url.tracking.fields.operating_system_version', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_device_type')
                            ->label(__('filament-short-url::default.track_device_type'))
                            ->default(fn () => config('filament-short-url.tracking.fields.device_type', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_referer_url')
                            ->label(__('filament-short-url::default.track_referer'))
                            ->default(fn () => config('filament-short-url.tracking.fields.referer_url', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_browser_language')
                            ->label(__('filament-short-url::default.track_browser_language'))
                            ->default(fn () => config('filament-short-url.tracking.fields.browser_language', true))
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),
                    ])
                    ->columns(2),

                Section::make(__('filament-short-url::default.utm_builder'))
                    ->description(__('filament-short-url::default.utm_builder_helper'))
                    ->icon('heroicon-o-tag')
                    ->aside()
                    ->schema([
                        TextInput::make('utm_source')
                            ->label(__('filament-short-url::default.utm_source'))
                            ->placeholder(__('filament-short-url::default.utm_source_placeholder'))
                            ->prefixIcon('heroicon-m-megaphone')
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => LinkTab::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_medium')
                            ->label(__('filament-short-url::default.utm_medium'))
                            ->placeholder(__('filament-short-url::default.utm_medium_placeholder'))
                            ->prefixIcon('heroicon-m-device-phone-mobile')
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => LinkTab::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_campaign')
                            ->label(__('filament-short-url::default.utm_campaign'))
                            ->placeholder(__('filament-short-url::default.utm_campaign_placeholder'))
                            ->prefixIcon('heroicon-m-flag')
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => LinkTab::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_term')
                            ->label(__('filament-short-url::default.utm_term'))
                            ->placeholder(__('filament-short-url::default.utm_term_placeholder'))
                            ->prefixIcon('heroicon-m-magnifying-glass')
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => LinkTab::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_content')
                            ->label(__('filament-short-url::default.utm_content'))
                            ->placeholder(__('filament-short-url::default.utm_content_placeholder'))
                            ->prefixIcon('heroicon-m-document-text')
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->columnSpanFull()
                            ->afterStateUpdated(fn (Get $get, Set $set) => LinkTab::syncUtmToDestination($get, $set)),
                    ])
                    ->columns(2),

                Section::make(__('filament-short-url::default.form_section_analytics'))
                    ->description('Zintegruj ten link z Google Analytics, podając identyfikator strumienia danych.')
                    ->icon('heroicon-o-chart-pie')
                    ->aside()
                    ->schema([
                        TextInput::make('ga_tracking_id')
                            ->label(__('filament-short-url::default.ga_tracking_id'))
                            ->hintIcon('heroicon-m-information-circle', tooltip: __('filament-short-url::default.ga_tracking_id_helper'))
                            ->placeholder('G-XXXXXXXXXX')
                            ->prefixIcon('heroicon-m-chart-bar')
                            ->regex('/^G-[A-Z0-9]+$/')
                            ->nullable(),
                    ]),
            ]);
    }
}
