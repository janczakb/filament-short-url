<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas;

use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ShortUrlForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                static::linkTab(),
                static::targetingTab(),
                static::trackingTab(),
                static::qrDesignTab(),
            ])->columnSpanFull(),
        ]);
    }

    private static function linkTab(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_link'))
            ->icon('heroicon-o-link')
            ->schema([
                Section::make()->schema([
                    TextInput::make('destination_url')
                        ->label(__('filament-short-url::default.destination_url'))
                        ->helperText(__('filament-short-url::default.destination_url_helper'))
                        ->required()
                        ->url()
                        ->maxLength(2048)
                        ->live(onBlur: true)
                        ->afterStateHydrated(function (TextInput $component, $state, Set $set) {
                            if (! $state) {
                                return;
                            }
                            $parts = parse_url($state);
                            if (isset($parts['query'])) {
                                parse_str($parts['query'], $query);
                                $set('utm_source', $query['utm_source'] ?? null);
                                $set('utm_medium', $query['utm_medium'] ?? null);
                                $set('utm_campaign', $query['utm_campaign'] ?? null);
                                $set('utm_term', $query['utm_term'] ?? null);
                                $set('utm_content', $query['utm_content'] ?? null);
                            }
                        })
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (! $state) {
                                return;
                            }
                            $parts = parse_url($state);
                            if (isset($parts['query'])) {
                                parse_str($parts['query'], $query);
                                $set('utm_source', $query['utm_source'] ?? null);
                                $set('utm_medium', $query['utm_medium'] ?? null);
                                $set('utm_campaign', $query['utm_campaign'] ?? null);
                                $set('utm_term', $query['utm_term'] ?? null);
                                $set('utm_content', $query['utm_content'] ?? null);
                            } else {
                                $set('utm_source', null);
                                $set('utm_medium', null);
                                $set('utm_campaign', null);
                                $set('utm_term', null);
                                $set('utm_content', null);
                            }
                        })
                        ->columnSpanFull(),

                    TextInput::make('url_key')
                        ->label(__('filament-short-url::default.url_key'))
                        ->helperText(__('filament-short-url::default.url_key_helper'))
                        ->alphaDash()
                        ->maxLength(32)
                        ->unique('short_urls', 'url_key', ignoreRecord: true)
                        ->suffixAction(
                            Action::make('regenerate')
                                ->icon('heroicon-o-arrow-path')
                                ->tooltip('Generate new key')
                                ->action(function (Set $set): void {
                                    $set('url_key', app(ShortUrlService::class)->generateKey());
                                })
                        )
                        ->placeholder('auto-generated'),

                    Select::make('redirect_status_code')
                        ->label(__('filament-short-url::default.redirect_code'))
                        ->options([
                            302 => __('filament-short-url::default.redirect_code_302'),
                            301 => __('filament-short-url::default.redirect_code_301'),
                        ])
                        ->default(302)
                        ->required(),
                ])->columns(2),

                Section::make(__('filament-short-url::default.form_section_options'))->schema([
                    Toggle::make('is_enabled')
                        ->label(__('filament-short-url::default.status'))
                        ->default(true)
                        ->inline(false),

                    Toggle::make('single_use')
                        ->label(__('filament-short-url::default.single_use'))
                        ->helperText(__('filament-short-url::default.single_use_helper'))
                        ->default(false)
                        ->inline(false),

                    Toggle::make('forward_query_params')
                        ->label(__('filament-short-url::default.forward_query_params'))
                        ->helperText(__('filament-short-url::default.forward_query_params_helper'))
                        ->default(false)
                        ->inline(false),

                    DateTimePicker::make('expires_at')
                        ->label(__('filament-short-url::default.expires_at'))
                        ->nullable()
                        ->native(false),
                ])->columns(2),

                Section::make(__('filament-short-url::default.form_section_notes'))->schema([
                    Textarea::make('notes')
                        ->label(__('filament-short-url::default.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function syncUtmToDestination(Get $get, Set $set): void
    {
        $url = $get('destination_url');
        if (! $url) {
            return;
        }

        $parts = parse_url($url);
        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // List of UTM parameters we build
        $utms = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        foreach ($utms as $utm) {
            $val = $get($utm);
            if ($val !== null && $val !== '') {
                $query[$utm] = $val;
            } else {
                unset($query[$utm]);
            }
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $queryString = ! empty($query) ? '?'.http_build_query($query) : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        $set('destination_url', $scheme.$host.$port.$path.$queryString.$fragment);
    }

    private static function trackingTab(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_tracking'))
            ->icon('heroicon-o-chart-bar')
            ->schema([
                Section::make(__('filament-short-url::default.form_section_tracking'))
                    ->schema([
                        Toggle::make('track_visits')
                            ->label(__('filament-short-url::default.track_visits'))
                            ->default(true)
                            ->live()
                            ->inline(false)
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make(__('filament-short-url::default.form_section_tracked_fields'))
                    ->schema([
                        Toggle::make('track_ip_address')
                            ->label(__('filament-short-url::default.track_ip'))
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_browser')
                            ->label(__('filament-short-url::default.track_browser'))
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_browser_version')
                            ->label(__('filament-short-url::default.track_browser_version'))
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_operating_system')
                            ->label(__('filament-short-url::default.track_os'))
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_operating_system_version')
                            ->label(__('filament-short-url::default.track_os_version'))
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_device_type')
                            ->label(__('filament-short-url::default.track_device_type'))
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),

                        Toggle::make('track_referer_url')
                            ->label(__('filament-short-url::default.track_referer'))
                            ->default(true)
                            ->inline(false)
                            ->disabled(fn (Get $get): bool => ! $get('track_visits')),
                    ])
                    ->columns(4)
                    ->hidden(fn (Get $get): bool => ! $get('track_visits')),

                Section::make(__('filament-short-url::default.utm_builder'))
                    ->description(__('filament-short-url::default.utm_builder_helper'))
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('utm_source')
                            ->label(__('filament-short-url::default.utm_source'))
                            ->placeholder(__('filament-short-url::default.utm_source_placeholder'))
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_medium')
                            ->label(__('filament-short-url::default.utm_medium'))
                            ->placeholder(__('filament-short-url::default.utm_medium_placeholder'))
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_campaign')
                            ->label(__('filament-short-url::default.utm_campaign'))
                            ->placeholder(__('filament-short-url::default.utm_campaign_placeholder'))
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_term')
                            ->label(__('filament-short-url::default.utm_term'))
                            ->placeholder(__('filament-short-url::default.utm_term_placeholder'))
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncUtmToDestination($get, $set)),

                        TextInput::make('utm_content')
                            ->label(__('filament-short-url::default.utm_content'))
                            ->placeholder(__('filament-short-url::default.utm_content_placeholder'))
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->columnSpanFull()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::syncUtmToDestination($get, $set)),
                    ])
                    ->columns(2),

                Section::make(__('filament-short-url::default.form_section_analytics'))
                    ->schema([
                        TextInput::make('ga_tracking_id')
                            ->label(__('filament-short-url::default.ga_tracking_id'))
                            ->helperText(__('filament-short-url::default.ga_tracking_id_helper'))
                            ->placeholder('G-XXXXXXXXXX')
                            ->regex('/^G-[A-Z0-9]+$/')
                            ->nullable(),
                    ]),
            ]);
    }

    private static function qrDesignTab(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_qr_design'))
            ->icon('heroicon-o-qr-code')
            ->schema([
                TextInput::make('qr_options')
                    ->extraAttributes([
                        'id' => 'qr-options-json-input',
                        'style' => 'position:absolute;width:1px;height:1px;opacity:0;pointer-events:none',
                        'aria-hidden' => 'true',
                    ])
                    ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?? '{}', true) ?: [])
                    ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                        $component->state(is_array($state) ? json_encode($state) : ($state ?? '{}'));
                    })
                    ->columnSpanFull()
                    ->label(''),

                ViewField::make('qr_designer')
                    ->view('filament-short-url::qr-designer')
                    ->columnSpanFull()
                    ->dehydrated(false),
            ]);
    }

    private static function targetingTab(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_targeting'))
            ->icon('heroicon-o-shield-check')
            ->schema([
                Section::make(__('filament-short-url::default.security_section_title'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('filament-short-url::default.password'))
                            ->helperText(__('filament-short-url::default.password_helper'))
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->maxLength(255),

                        Toggle::make('show_warning_page')
                            ->label(__('filament-short-url::default.show_warning_page'))
                            ->helperText(__('filament-short-url::default.show_warning_page_helper'))
                            ->default(false)
                            ->inline(false),
                    ])->columns(2),

                Section::make(__('filament-short-url::default.targeting_type'))
                    ->schema([
                        Select::make('targeting_rules.type')
                            ->label(__('filament-short-url::default.targeting_type'))
                            ->options([
                                'none' => __('filament-short-url::default.targeting_type_none'),
                                'device' => __('filament-short-url::default.targeting_type_device'),
                                'geo' => __('filament-short-url::default.targeting_type_country'),
                                'rotation' => __('filament-short-url::default.targeting_type_rotation'),
                            ])
                            ->default('none')
                            ->live()
                            ->required(),

                        Section::make(__('filament-short-url::default.device_targeting_rules'))
                            ->schema([
                                TextInput::make('targeting_rules.device.ios')
                                    ->label(__('filament-short-url::default.device_mobile'))
                                    ->url()
                                    ->nullable()
                                    ->maxLength(2048),
                                TextInput::make('targeting_rules.device.android')
                                    ->label(__('filament-short-url::default.device_tablet'))
                                    ->url()
                                    ->nullable()
                                    ->maxLength(2048),
                                TextInput::make('targeting_rules.device.desktop')
                                    ->label(__('filament-short-url::default.device_desktop'))
                                    ->url()
                                    ->nullable()
                                    ->maxLength(2048),
                            ])
                            ->columns(1)
                            ->visible(fn (Get $get): bool => $get('targeting_rules.type') === 'device'),

                        Repeater::make('targeting_rules.geo')
                            ->label(__('filament-short-url::default.country_targeting_rules'))
                            ->schema([
                                Select::make('country_code')
                                    ->label(__('filament-short-url::default.country_code'))
                                    ->options([
                                        'PL' => 'Poland',
                                        'US' => 'United States',
                                        'GB' => 'United Kingdom',
                                        'DE' => 'Germany',
                                        'FR' => 'France',
                                        'ES' => 'Spain',
                                        'IT' => 'Italy',
                                        'CA' => 'Canada',
                                        'AU' => 'Australia',
                                        'NL' => 'Netherlands',
                                        'UA' => 'Ukraine',
                                        'IE' => 'Ireland',
                                        'BE' => 'Belgium',
                                        'AT' => 'Austria',
                                        'CH' => 'Switzerland',
                                        'SE' => 'Sweden',
                                        'NO' => 'Norway',
                                        'DK' => 'Denmark',
                                        'FI' => 'Finland',
                                        'CZ' => 'Czech Republic',
                                        'SK' => 'Slovakia',
                                        'HU' => 'Hungary',
                                        'RO' => 'Romania',
                                        'GR' => 'Greece',
                                        'PT' => 'Portugal',
                                        'BR' => 'Brazil',
                                        'MX' => 'Mexico',
                                        'CN' => 'China',
                                        'JP' => 'Japan',
                                        'IN' => 'India',
                                    ])
                                    ->searchable()
                                    ->required(),
                                TextInput::make('url')
                                    ->label(__('filament-short-url::default.destination_url'))
                                    ->url()
                                    ->required()
                                    ->maxLength(2048),
                            ])
                            ->columns(2)
                            ->visible(fn (Get $get): bool => $get('targeting_rules.type') === 'geo'),

                        Repeater::make('targeting_rules.rotation')
                            ->label(__('filament-short-url::default.rotation_targeting_rules'))
                            ->schema([
                                TextInput::make('url')
                                    ->label(__('filament-short-url::default.rotation_url'))
                                    ->url()
                                    ->required()
                                    ->maxLength(2048),
                                TextInput::make('weight')
                                    ->label(__('filament-short-url::default.rotation_weight'))
                                    ->helperText(__('filament-short-url::default.rotation_weight_helper'))
                                    ->numeric()
                                    ->required()
                                    ->default(50),
                            ])
                            ->columns(2)
                            ->visible(fn (Get $get): bool => $get('targeting_rules.type') === 'rotation'),
                    ])
            ]);
    }
}
