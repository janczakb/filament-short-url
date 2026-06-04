<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ShortUrlForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([
                static::linkTab(),
                static::targetingTab(),
                static::appLinkingTab(),
                static::trackingTab(),
                static::marketingTab(),
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
                        ->rules([
                            fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                $safeBrowsing = app(SafeBrowsingService::class);
                                if (! $safeBrowsing->isSafe($value)) {
                                    $fail(__('filament-short-url::default.safe_browsing_error') ?? 'This URL has been flagged by Google Safe Browsing as unsafe.');
                                }
                            },
                        ])
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
                        ->default(fn () => config('filament-short-url.redirect_status_code', 302))
                        ->required(),
                ])->columns(2),

                Section::make(__('filament-short-url::default.form_section_options'))->schema([
                    Toggle::make('is_enabled')
                        ->label(__('filament-short-url::default.status'))
                        ->default(true)
                        ->inline(false),

                    Toggle::make('forward_query_params')
                        ->label(__('filament-short-url::default.forward_query_params'))
                        ->helperText(__('filament-short-url::default.forward_query_params_helper'))
                        ->default(false)
                        ->inline(false),
                ])->columns(2),

                Section::make(__('filament-short-url::default.form_section_validity'))
                    ->schema([
                        Toggle::make('use_date_validity')
                            ->label(__('filament-short-url::default.use_date_validity'))
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(function (Toggle $component, $state, Get $get, Set $set) {
                                $set('use_date_validity', $get('activated_at') !== null || $get('expires_at') !== null);
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $set('activated_at', now()->startOfMinute());
                                } else {
                                    $set('activated_at', null);
                                    $set('expires_at', null);
                                    $set('expiration_redirect_url', null);
                                }
                            })
                            ->columnSpanFull(),

                        DateTimePicker::make('activated_at')
                            ->label(__('filament-short-url::default.activated_at'))
                            ->nullable()
                            ->native(false)
                            ->withoutSeconds()
                            ->live(onBlur: true)
                            ->required(fn (Get $get): bool => (bool) $get('use_date_validity'))
                            ->visible(fn (Get $get): bool => (bool) $get('use_date_validity'))
                            ->minDate(now()->startOfDay())
                            ->maxDate(fn (Get $get) => $get('expires_at')),

                        DateTimePicker::make('expires_at')
                            ->label(__('filament-short-url::default.expires_at'))
                            ->nullable()
                            ->native(false)
                            ->withoutSeconds()
                            ->live(onBlur: true)
                            ->visible(fn (Get $get): bool => (bool) $get('use_date_validity'))
                            ->minDate(fn (Get $get) => $get('activated_at') ?: now()->startOfDay()),

                        TextInput::make('expiration_redirect_url')
                            ->label(__('filament-short-url::default.expiration_redirect_url'))
                            ->helperText(__('filament-short-url::default.expiration_redirect_url_helper'))
                            ->url()
                            ->maxLength(2048)
                            ->nullable()
                            ->visible(fn (Get $get): bool => (bool) $get('use_date_validity'))
                            ->columnSpanFull(),

                        Toggle::make('single_use')
                            ->label(__('filament-short-url::default.single_use'))
                            ->helperText(__('filament-short-url::default.single_use_helper'))
                            ->default(false)
                            ->inline(false)
                            ->live(),

                        TextInput::make('max_visits')
                            ->label(__('filament-short-url::default.max_visits'))
                            ->helperText(__('filament-short-url::default.max_visits_helper'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->nullable()
                            ->hidden(fn (Get $get): bool => (bool) $get('single_use')),
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
                            ->default(fn () => config('filament-short-url.tracking.enabled', true))
                            ->live()
                            ->inline(false)
                            ->columnSpanFull(),
                    ])->columns(1),

                Section::make(__('filament-short-url::default.form_section_tracked_fields'))
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
                Group::make([
                    TextInput::make('qr_options')
                        ->extraAttributes([
                            'style' => 'display: none !important;',
                        ])
                        ->extraInputAttributes([
                            'id' => 'qr-options-json-input',
                        ])
                        ->hiddenLabel()
                        ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?? '{}', true) ?: [])
                        ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                            $component->state(is_array($state) ? json_encode($state) : ($state ?? '{}'));
                        }),

                    TextInput::make('qr_logo')
                        ->extraAttributes([
                            'style' => 'display: none !important;',
                        ])
                        ->extraInputAttributes([
                            'id' => 'qr-logo-path-input',
                        ])
                        ->hiddenLabel()
                        ->nullable(),
                ])->extraAttributes([
                    'style' => 'display: none !important;',
                ]),

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
                        Hidden::make('password'),

                        Hidden::make('password_active_flag')
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Hidden $component, $state, ?ShortUrl $record) {
                                $component->state($record && ! empty($record->password));
                            }),

                        Hidden::make('is_entering_password')
                            ->dehydrated(false)
                            ->default(false),

                        // State 1: Password is active (password_active_flag is true)
                        Group::make([
                            Placeholder::make('password_status')
                                ->label(__('filament-short-url::default.password'))
                                ->content(new HtmlString(
                                    '<div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold">'.
                                    '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>'.
                                    '<span>'.(__('filament-short-url::default.password_status_active') ?? 'Password protection is enabled.').'</span>'.
                                    '</div>'
                                ))
                                ->columnSpan(1),

                            Actions::make([
                                Action::make('change_password')
                                    ->label(__('filament-short-url::default.change_password'))
                                    ->icon('heroicon-o-pencil')
                                    ->color('primary')
                                    ->action(function (Set $set) {
                                        $set('password_active_flag', false);
                                        $set('is_entering_password', true);
                                        $set('new_password_input', null);
                                        $set('new_password_confirmation_input', null);
                                    }),
                                Action::make('remove_password')
                                    ->label(__('filament-short-url::default.remove_password'))
                                    ->icon('heroicon-o-trash')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->action(function (Set $set, ?ShortUrl $record) {
                                        $set('password', null);
                                        $set('new_password_input', null);
                                        $set('new_password_confirmation_input', null);
                                        $set('password_active_flag', false);
                                        $set('is_entering_password', false);
                                        if ($record) {
                                            $record->password = null;
                                        }
                                    }),
                            ])
                                ->columnSpan(1),
                        ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('password_active_flag')),

                        // State 2: No Password, Setup Button Group (password_active_flag is false, is_entering_password is false)
                        Group::make([
                            Actions::make([
                                Action::make('setup_password')
                                    ->label(__('filament-short-url::default.set_password'))
                                    ->icon('heroicon-o-key')
                                    ->color('primary')
                                    ->action(function (Set $set) {
                                        $set('is_entering_password', true);
                                    }),
                            ])
                                ->columnSpanFull(),
                        ])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => ! $get('password_active_flag') && ! $get('is_entering_password')),

                        // State 3: Entering Password Group (password_active_flag is false, is_entering_password is true)
                        Group::make([
                            Group::make([
                                TextInput::make('new_password_input')
                                    ->label(__('filament-short-url::default.new_password'))
                                    ->password()
                                    ->revealable()
                                    ->live()
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->required(fn (Get $get): bool => ! $get('password_active_flag') && $get('is_entering_password'))
                                    ->columnSpan(1),

                                TextInput::make('new_password_confirmation_input')
                                    ->label(__('filament-short-url::default.confirm_password'))
                                    ->password()
                                    ->revealable()
                                    ->same('new_password_input')
                                    ->maxLength(255)
                                    ->dehydrated(false)
                                    ->required(fn (Get $get): bool => ! empty($get('new_password_input')))
                                    ->columnSpan(1),
                            ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Actions::make([
                                Action::make('confirm_password')
                                    ->label(__('filament-short-url::default.confirm'))
                                    ->icon('heroicon-o-check')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set) {
                                        $password = $get('new_password_input');
                                        $confirm = $get('new_password_confirmation_input');
                                        if (empty($password)) {
                                            Notification::make()
                                                ->title(__('filament-short-url::default.password_required_error') ?? 'Password is required.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }
                                        if ($password !== $confirm) {
                                            Notification::make()
                                                ->title(__('filament-short-url::default.password_mismatch_error') ?? 'Passwords do not match.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }
                                        $set('password', $password);
                                        $set('password_active_flag', true);
                                        $set('is_entering_password', false);
                                    }),

                                Action::make('cancel_password')
                                    ->label(__('filament-short-url::default.cancel'))
                                    ->icon('heroicon-o-x-mark')
                                    ->color('gray')
                                    ->action(function (Get $get, Set $set, ?ShortUrl $record) {
                                        if ($record && ! empty($record->password)) {
                                            $set('password_active_flag', true);
                                        } else {
                                            $set('password_active_flag', false);
                                        }
                                        $set('is_entering_password', false);
                                        $set('new_password_input', null);
                                        $set('new_password_confirmation_input', null);
                                    }),
                            ])
                                ->columnSpanFull(),
                        ])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => ! $get('password_active_flag') && $get('is_entering_password')),

                        Toggle::make('show_warning_page')
                            ->label(__('filament-short-url::default.show_warning_page'))
                            ->helperText(__('filament-short-url::default.show_warning_page_helper'))
                            ->default(false)
                            ->inline(false),
                    ])->columns(2),

                Section::make(__('filament-short-url::default.targeting_rules'))
                    ->compact()
                    ->schema([
                        Repeater::make('targeting_rules')
                            ->label(__('filament-short-url::default.targeting_rules'))
                            ->hiddenLabel()
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => filled($state['url'] ?? null) ? __('filament-short-url::default.direct_to_url').': '.$state['url'] : null)
                            ->columns(12)
                            ->afterStateHydrated(function (Repeater $component, $state) {
                                if (! is_array($state)) {
                                    $component->state([]);

                                    return;
                                }

                                // If it is legacy format (has 'type' key)
                                if (isset($state['type'])) {
                                    $type = $state['type'];
                                    $newRules = [];

                                    if ($type === 'device') {
                                        $devices = $state['device'] ?? [];
                                        $mobileUrl = $devices['mobile'] ?? $devices['ios'] ?? null;
                                        if ($mobileUrl) {
                                            $newRules[] = [
                                                'match' => 'or',
                                                'url' => $mobileUrl,
                                                'filters' => [
                                                    [
                                                        'type' => 'device',
                                                        'data' => ['devices' => ['mobile']],
                                                    ],
                                                ],
                                            ];
                                        }
                                        $tabletUrl = $devices['tablet'] ?? $devices['android'] ?? null;
                                        if ($tabletUrl) {
                                            $newRules[] = [
                                                'match' => 'or',
                                                'url' => $tabletUrl,
                                                'filters' => [
                                                    [
                                                        'type' => 'device',
                                                        'data' => ['devices' => ['tablet']],
                                                    ],
                                                ],
                                            ];
                                        }
                                        $desktopUrl = $devices['desktop'] ?? null;
                                        if ($desktopUrl) {
                                            $newRules[] = [
                                                'match' => 'or',
                                                'url' => $desktopUrl,
                                                'filters' => [
                                                    [
                                                        'type' => 'device',
                                                        'data' => ['devices' => ['desktop']],
                                                    ],
                                                ],
                                            ];
                                        }
                                    } elseif ($type === 'geo') {
                                        foreach ($state['geo'] ?? [] as $geoRule) {
                                            if (! empty($geoRule['url']) && ! empty($geoRule['country_code'])) {
                                                $newRules[] = [
                                                    'match' => 'or',
                                                    'url' => $geoRule['url'],
                                                    'filters' => [
                                                        [
                                                            'type' => 'country',
                                                            'data' => ['countries' => [strtoupper($geoRule['country_code'])]],
                                                        ],
                                                    ],
                                                ];
                                            }
                                        }
                                    } elseif ($type === 'language') {
                                        foreach ($state['language'] ?? [] as $langRule) {
                                            if (! empty($langRule['url']) && ! empty($langRule['language_code'])) {
                                                $newRules[] = [
                                                    'match' => 'or',
                                                    'url' => $langRule['url'],
                                                    'filters' => [
                                                        [
                                                            'type' => 'language',
                                                            'data' => ['languages' => [strtolower($langRule['language_code'])]],
                                                        ],
                                                    ],
                                                ];
                                            }
                                        }
                                    }

                                    $component->state($newRules);

                                    return;
                                }

                                if (! array_is_list($state)) {
                                    $component->state([]);

                                    return;
                                }

                                $component->state($state);
                            })
                            ->schema([
                                Select::make('match')
                                    ->label(__('filament-short-url::default.match'))
                                    ->options([
                                        'or' => __('filament-short-url::default.match_or'),
                                        'and' => __('filament-short-url::default.match_and'),
                                    ])
                                    ->default('or')
                                    ->required()
                                    ->columnSpan(3),

                                TextInput::make('url')
                                    ->label(__('filament-short-url::default.direct_to_url'))
                                    ->url()
                                    ->required()
                                    ->maxLength(2048)
                                    ->columnSpan(9),

                                Builder::make('filters')
                                    ->label(__('filament-short-url::default.add_filter'))
                                    ->hiddenLabel()
                                    ->addActionLabel(__('filament-short-url::default.add_filter'))
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->blockNumbers(false)
                                    ->addBetweenAction(fn ($action) => $action->hidden())
                                    ->minItems(1)
                                    ->blocks([
                                        Block::make('device')
                                            ->label(fn (?array $state) => $state === null
                                                ? __('filament-short-url::default.filter_device')
                                                : new HtmlString(__('filament-short-url::default.select_devices').' <sup class="text-danger-600 dark:text-danger-400 fi-fo-field-label-required-mark" style="color: rgb(220, 38, 38) !important;">*</sup>')
                                            )
                                            ->icon('heroicon-o-device-phone-mobile')
                                            ->schema([
                                                CheckboxList::make('devices')
                                                    ->label(__('filament-short-url::default.select_devices'))
                                                    ->hiddenLabel()
                                                    ->options([
                                                        'desktop' => __('filament-short-url::default.device_desktop_label'),
                                                        'mobile' => __('filament-short-url::default.device_mobile_label'),
                                                        'tablet' => __('filament-short-url::default.device_tablet_label'),
                                                    ])
                                                    ->required()
                                                    ->columns(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->maxItems(1),
                                        Block::make('platform')
                                            ->label(fn (?array $state) => $state === null
                                                ? __('filament-short-url::default.filter_platform')
                                                : new HtmlString(__('filament-short-url::default.select_platforms').' <sup class="text-danger-600 dark:text-danger-400 fi-fo-field-label-required-mark" style="color: rgb(220, 38, 38) !important;">*</sup>')
                                            )
                                            ->icon('heroicon-o-computer-desktop')
                                            ->schema([
                                                CheckboxList::make('platforms')
                                                    ->label(__('filament-short-url::default.select_platforms'))
                                                    ->hiddenLabel()
                                                    ->options([
                                                        'android' => 'Android',
                                                        'fire_os' => 'Fire OS',
                                                        'ios' => 'iOS / iPadOS',
                                                        'linux' => 'Linux',
                                                        'mac' => 'macOS',
                                                        'windows' => 'Windows',
                                                    ])
                                                    ->required()
                                                    ->columns(3)
                                                    ->columnSpanFull(),
                                            ])
                                            ->maxItems(1),
                                        Block::make('country')
                                            ->label(fn (?array $state) => $state === null
                                                ? __('filament-short-url::default.filter_country')
                                                : new HtmlString(__('filament-short-url::default.select_countries').' <sup class="text-danger-600 dark:text-danger-400 fi-fo-field-label-required-mark" style="color: rgb(220, 38, 38) !important;">*</sup>')
                                            )
                                            ->icon('heroicon-o-globe-alt')
                                            ->schema([
                                                Select::make('countries')
                                                    ->label(__('filament-short-url::default.select_countries'))
                                                    ->hiddenLabel()
                                                    ->multiple()
                                                    ->searchable()
                                                    ->allowHtml()
                                                    ->options(function (): array {
                                                        $countries = __('filament-short-url::countries');
                                                        if (is_array($countries)) {
                                                            asort($countries, SORT_LOCALE_STRING);

                                                            $htmlOptions = [];
                                                            foreach ($countries as $code => $name) {
                                                                $lowerCode = strtolower($code);
                                                                $htmlOptions[$code] = "<span class=\"flex items-center gap-2\"><img src=\"https://flagcdn.com/h20/{$lowerCode}.webp\" class=\"w-5 h-auto rounded-sm inline-block mr-2\" alt=\"{$name}\" style=\"vertical-align: middle;\" /><span>{$name}</span></span>";
                                                            }

                                                            return $htmlOptions;
                                                        }

                                                        return [];
                                                    })
                                                    ->optionsLimit(300)
                                                    ->required()
                                                    ->columnSpanFull(),
                                            ])
                                            ->maxItems(1),
                                        Block::make('language')
                                            ->label(fn (?array $state) => $state === null
                                                ? __('filament-short-url::default.filter_language')
                                                : new HtmlString(__('filament-short-url::default.select_languages').' <sup class="text-danger-600 dark:text-danger-400 fi-fo-field-label-required-mark" style="color: rgb(220, 38, 38) !important;">*</sup>')
                                            )
                                            ->icon('heroicon-o-language')
                                            ->schema([
                                                Select::make('languages')
                                                    ->label(__('filament-short-url::default.select_languages'))
                                                    ->hiddenLabel()
                                                    ->multiple()
                                                    ->searchable()
                                                    ->options(function (): array {
                                                        $languages = __('filament-short-url::languages');
                                                        if (is_array($languages)) {
                                                            asort($languages, SORT_LOCALE_STRING);

                                                            return $languages;
                                                        }

                                                        return [];
                                                    })
                                                    ->required()
                                                    ->columnSpanFull(),
                                            ])
                                            ->maxItems(1),
                                    ])
                                    ->rules([
                                        function () {
                                            return function (string $attribute, $value, \Closure $fail) {
                                                if (! is_array($value)) {
                                                    return;
                                                }
                                                $types = collect($value)->pluck('type');
                                                if ($types->duplicates()->isNotEmpty()) {
                                                    $fail('Each filter type (Device, Platform, Country, Language) can only be added once.');
                                                }
                                            };
                                        },
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->default([]),
                    ]),
            ]);
    }

    private static function marketingTab(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_marketing'))
            ->icon('heroicon-o-megaphone')
            ->schema([
                Section::make(__('filament-short-url::default.marketing_pixels_title'))
                    ->description(__('filament-short-url::default.marketing_pixels_desc'))
                    ->schema([
                        Select::make('pixels')
                            ->label(__('filament-short-url::default.pixels_navigation_label') ?? 'Retargeting Pixels')
                            ->multiple()
                            ->relationship('pixels', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-short-url::default.marketing_webhooks_title'))
                    ->description(__('filament-short-url::default.marketing_webhooks_desc'))
                    ->schema([
                        Placeholder::make('webhook_info')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<div class="callout my-4 px-5 py-4 overflow-hidden rounded-2xl flex gap-3 border border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-white/10" data-callout-type="info">'.
                                '<div class="mt-0.5 w-4" data-component-part="callout-icon">'.
                                '<svg viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="flex-none size-5 text-neutral-800 dark:text-neutral-300" aria-label="Info">'.
                                '<path d="M8 0C3.58125 0 0 3.58125 0 8C0 12.4187 3.58125 16 8 16C12.4187 16 16 12.4187 16 8C16 3.58125 12.4187 0 8 0ZM8 14.5C4.41563 14.5 1.5 11.5841 1.5 8C1.5 4.41594 4.41563 1.5 8 1.5C11.5844 1.5 14.5 4.41594 14.5 8C14.5 11.5841 11.5844 14.5 8 14.5ZM9.25 10.5H8.75V7.75C8.75 7.3375 8.41563 7 8 7H7C6.5875 7 6.25 7.3375 6.25 7.75C6.25 8.1625 6.5875 8.5 7 8.5H7.25V10.5H6.75C6.3375 10.5 6 10.8375 6 11.25C6 11.6625 6.3375 12 6.75 12H9.25C9.66406 12 10 11.25C10 10.8359 9.66563 10.5 9.25 10.5ZM8 6C8.55219 6 9 5.55219 9 5C9 4.44781 8.55219 4 8 4C7.44781 4 7 4.44687 7 5C7 5.55313 7.44687 6 8 6Z"></path>'.
                                '</svg>'.
                                '</div>'.
                                '<div class="text-sm prose dark:prose-invert min-w-0 w-full text-neutral-800 dark:text-neutral-300" data-component-part="callout-content">'.
                                '<span data-as="p">'.
                                (__('filament-short-url::default.webhook_helper_alert') ?? 'Once configured, each visit to this short link triggers a real-time HTTP POST request to this URL. The payload contains detailed click metadata in JSON format (including URL key, IP address, country, browser, operating system, referrer, and UTM parameters).').
                                '</span>'.
                                '</div>'.
                                '</div>'
                            ))
                            ->columnSpanFull(),

                        TextInput::make('webhook_url')
                            ->label(__('filament-short-url::default.webhook_url'))
                            ->placeholder('https://api.yourcrm.com/webhooks/clicks')
                            ->url()
                            ->maxLength(2048)
                            ->nullable()
                            ->columnSpanFull(),
                        ViewField::make('webhook_payload_example')
                            ->view('filament-short-url::webhook-payload-example')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function appLinkingTab(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_app_linking') ?? 'App Linking')
            ->icon('heroicon-o-device-phone-mobile')
            ->schema([
                Section::make(__('filament-short-url::default.form_section_app_linking') ?? 'App Linking / Deep Links')
                    ->schema([
                        Toggle::make('auto_open_app_mobile')
                            ->label(__('filament-short-url::default.auto_open_app_mobile') ?? 'Auto open app on mobile')
                            ->helperText(__('filament-short-url::default.auto_open_app_mobile_helper') ?? 'Enable this if you want your link to automatically open as an app when accessed on mobile.')
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
