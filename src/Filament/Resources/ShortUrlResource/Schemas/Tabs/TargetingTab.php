<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\TrafficSplitter;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\WeightBalancer;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Rules\SafeUrl;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TargetingTab
{
    /**
     * Build the targeting and security form tab.
     */
    public static function make(): Tab
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
                                    '<span>'.__('filament-short-url::default.password_status_active').'</span>'.
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
                                                ->title(__('filament-short-url::default.password_required_error'))
                                                ->danger()
                                                ->send();

                                            return;
                                        }
                                        if ($password !== $confirm) {
                                            Notification::make()
                                                ->title(__('filament-short-url::default.password_mismatch_error'))
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
                            ->maxItems(10)
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed()
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

                                Select::make('destination_type')
                                    ->label(__('filament-short-url::default.destination_type'))
                                    ->options([
                                        'single' => __('filament-short-url::default.destination_type_single'),
                                        'split' => __('filament-short-url::default.destination_type_split'),
                                    ])
                                    ->default('single')
                                    ->live()
                                    ->required()
                                    ->columnSpan(3)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state === 'split' && empty($get('variants'))) {
                                            $set('variants', [
                                                (string) Str::uuid() => ['label' => 'Variant A', 'url' => '', 'weight' => 50],
                                                (string) Str::uuid() => ['label' => 'Variant B', 'url' => '', 'weight' => 50],
                                            ]);
                                        }
                                    }),

                                TextInput::make('url')
                                    ->label(__('filament-short-url::default.direct_to_url'))
                                    ->url()
                                    ->required(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                                    ->visible(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                                    ->maxLength(2048)
                                    ->rules([
                                        app(SafeUrl::class),
                                    ])
                                    ->columnSpan(6),

                                Repeater::make('variants')
                                    ->hiddenLabel()
                                    ->extraAttributes(['class' => 'ab-test-repeater'])
                                    ->table([
                                        TableColumn::make(__('filament-short-url::default.variant_label'))
                                            ->width('30%'),
                                        TableColumn::make(__('filament-short-url::default.variant_url'))
                                            ->width('70%'),
                                    ])
                                    ->schema([
                                        TextInput::make('label')
                                            ->hiddenLabel()
                                            ->placeholder('e.g. Variant A')
                                            ->required()
                                            ->maxLength(100),
                                        TextInput::make('url')
                                            ->hiddenLabel()
                                            ->url()
                                            ->required()
                                            ->maxLength(2048)
                                            ->rules([
                                                app(SafeUrl::class),
                                            ]),
                                        Hidden::make('weight'),
                                    ])
                                    ->defaultItems(2)
                                    ->minItems(2)
                                    ->maxItems(5)
                                    ->reorderable(false)
                                    ->live()
                                    ->rules([
                                        function () {
                                            return function (string $attribute, $value, \Closure $fail) {
                                                if (! is_array($value)) {
                                                    return;
                                                }
                                                $sum = array_sum(array_column($value, 'weight'));
                                                if ($sum !== 100) {
                                                    $fail(__('filament-short-url::default.weights_sum_error', ['sum' => $sum]));
                                                }
                                            };
                                        },
                                    ])
                                    ->deleteAction(
                                        fn ($action) => $action
                                            ->visible(fn (Get $get): bool => count($get('variants') ?? []) > 2)
                                            ->after(fn ($component) => WeightBalancer::balanceWeightsEqually($component))
                                    )
                                    ->addAction(
                                        fn ($action) => $action->after(fn ($component) => WeightBalancer::balanceWeightsEqually($component))
                                    )
                                    ->visible(fn (Get $get): bool => $get('destination_type') === 'split')
                                    ->columnSpanFull(),

                                TrafficSplitter::make('traffic_split')
                                    ->label(__('filament-short-url::default.traffic_split'))
                                    ->target('variants')
                                    ->visible(fn (Get $get): bool => $get('destination_type') === 'split')
                                    ->columnSpanFull(),

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
}
