<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\TabCardHeader;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class TrackingTab
{
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_tracking'))
            ->icon('heroicon-o-chart-bar')
            ->schema([
                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card'])
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 12])
                            ->extraAttributes(['class' => 'validity-tab-card-toolbar-grid'])
                            ->schema([
                                Placeholder::make('tracking_visit_card_header')
                                    ->hiddenLabel()
                                    ->content(TabCardHeader::make(
                                        'heroicon-o-chart-bar',
                                        'validity-tab-card-icon--tracking',
                                        'tracking_visit_card_title',
                                        'tracking_visit_card_subtitle',
                                    ))
                                    ->columnSpan(['default' => 12, 'md' => 9]),

                                Toggle::make('track_visits')
                                    ->label(__('filament-short-url::default.track_visits'))
                                    ->hiddenLabel()
                                    ->default(fn () => config('filament-short-url.tracking.enabled', true))
                                    ->live()
                                    ->inline(false)
                                    ->extraFieldWrapperAttributes([
                                        'class' => 'validity-tab-card-toolbar-action tracking-card-toolbar-action',
                                    ])
                                    ->extraAttributes([
                                        'aria-label' => __('filament-short-url::default.track_visits'),
                                    ])
                                    ->columnSpan(['default' => 12, 'md' => 3]),
                            ]),

                        Placeholder::make('tracking_visit_empty_state')
                            ->hiddenLabel()
                            ->visible(fn (Get $get): bool => ! $get('track_visits'))
                            ->content(new HtmlString(
                                '<div class="validity-tab-empty">'.
                                '<div class="validity-tab-empty-icon">'.
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>'.
                                '</div>'.
                                '<p class="validity-tab-empty-title">'.e(__('filament-short-url::default.tracking_visit_empty_title')).'</p>'.
                                '<p class="validity-tab-empty-desc">'.e(__('filament-short-url::default.tracking_visit_empty_desc')).'</p>'.
                                '</div>'
                            )),

                        Group::make()
                            ->visible(fn (Get $get): bool => (bool) $get('track_visits'))
                            ->extraAttributes(['class' => 'tracking-fields-panel'])
                            ->schema([
                                Placeholder::make('tracking_fields_identity_heading')
                                    ->hiddenLabel()
                                    ->content(new HtmlString(
                                        '<p class="tracking-fields-category">'.e(__('filament-short-url::default.tracking_fields_identity_title')).'</p>'
                                    ))
                                    ->columnSpanFull(),

                                Grid::make(['default' => 1, 'md' => 2])
                                    ->schema([
                                        ...self::trackedFieldCards([
                                            ['track_ip_address', 'track_ip', 'track_ip_desc', 'filament-short-url.tracking.fields.ip_address'],
                                            ['track_referer_url', 'track_referer', 'track_referer_desc', 'filament-short-url.tracking.fields.referer_url'],
                                        ]),
                                    ]),

                                Placeholder::make('tracking_fields_device_heading')
                                    ->hiddenLabel()
                                    ->content(new HtmlString(
                                        '<p class="tracking-fields-category">'.e(__('filament-short-url::default.tracking_fields_device_title')).'</p>'
                                    ))
                                    ->columnSpanFull(),

                                Grid::make(['default' => 1, 'md' => 2])
                                    ->schema([
                                        ...self::trackedFieldCards([
                                            ['track_browser', 'track_browser', 'track_browser_desc', 'filament-short-url.tracking.fields.browser'],
                                            ['track_browser_version', 'track_browser_version', 'track_browser_version_desc', 'filament-short-url.tracking.fields.browser_version'],
                                            ['track_operating_system', 'track_os', 'track_os_desc', 'filament-short-url.tracking.fields.operating_system'],
                                            ['track_operating_system_version', 'track_os_version', 'track_os_version_desc', 'filament-short-url.tracking.fields.operating_system_version'],
                                            ['track_device_type', 'track_device_type', 'track_device_type_desc', 'filament-short-url.tracking.fields.device_type'],
                                            ['track_browser_language', 'track_browser_language', 'track_browser_language_desc', 'filament-short-url.tracking.fields.browser_language'],
                                        ]),
                                    ]),
                            ]),
                    ]),

                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card tracking-utm-card'])
                    ->schema([
                        Placeholder::make('tracking_utm_card_header')
                            ->hiddenLabel()
                            ->content(TabCardHeader::make(
                                'heroicon-o-megaphone',
                                'validity-tab-card-icon--utm',
                                'tracking_utm_card_title',
                                'tracking_utm_card_subtitle',
                                compact: true,
                            )),

                        Grid::make(['default' => 1, 'md' => 2])
                            ->extraAttributes(['class' => 'tracking-utm-fields'])
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
                            ]),
                    ]),

                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card'])
                    ->schema([
                        Placeholder::make('tracking_ga_card_header')
                            ->hiddenLabel()
                            ->content(TabCardHeader::make(
                                'heroicon-o-chart-pie',
                                'validity-tab-card-icon--ga',
                                'tracking_ga_card_title',
                                'tracking_ga_card_subtitle',
                                compact: true,
                            )),

                        TextInput::make('ga_tracking_id')
                            ->label(__('filament-short-url::default.ga_tracking_id'))
                            ->hintIcon('heroicon-m-information-circle', tooltip: __('filament-short-url::default.ga_tracking_id_helper'))
                            ->placeholder('G-XXXXXXXXXX')
                            ->prefixIcon('heroicon-m-chart-bar')
                            ->regex('/^G-[A-Z0-9]+$/')
                            ->nullable()
                            ->extraFieldWrapperAttributes(['class' => 'tracking-ga-field']),
                    ]),
            ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: string, 3: string}>  $fields
     * @return array<int, Group>
     */
    private static function trackedFieldCards(array $fields): array
    {
        return array_map(
            fn (array $field): Group => self::trackedFieldCard(...$field),
            $fields,
        );
    }

    private static function trackedFieldCard(
        string $name,
        string $labelKey,
        string $descKey,
        string $configKey,
    ): Group {
        return Group::make()
            ->extraAttributes(['class' => 'validity-limit-block tracking-field-card'])
            ->schema([
                Placeholder::make("{$name}_header")
                    ->hiddenLabel()
                    ->content(new HtmlString(
                        '<div class="tracking-field-card-copy">'.
                        '<p class="tracking-field-card-title">'.e(__("filament-short-url::default.{$labelKey}")).'</p>'.
                        '<p class="tracking-field-card-desc">'.e(__("filament-short-url::default.{$descKey}")).'</p>'.
                        '</div>'
                    )),

                Toggle::make($name)
                    ->label(__("filament-short-url::default.{$labelKey}"))
                    ->hiddenLabel()
                    ->default(fn () => config($configKey, true))
                    ->inline(false)
                    ->extraFieldWrapperAttributes([
                        'class' => 'tracking-field-card-toggle',
                    ])
                    ->extraAttributes([
                        'aria-label' => __("filament-short-url::default.{$labelKey}"),
                    ]),
            ]);
    }
}
