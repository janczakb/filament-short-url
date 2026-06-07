<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\TrafficSplitter;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\WeightBalancer;
use Bjanczak\FilamentShortUrl\Rules\SafeUrl;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
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
            ->icon('heroicon-o-funnel')
            ->schema([

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
                    ])->contained(false),

                Section::make(__('filament-short-url::default.form_section_app_linking'))
                    ->schema([
                        Toggle::make('auto_open_app_mobile')
                            ->label(__('filament-short-url::default.auto_open_app_mobile'))
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.auto_open_app_mobile_helper'))
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
                    ])->contained(false),
            ]);
    }
}
