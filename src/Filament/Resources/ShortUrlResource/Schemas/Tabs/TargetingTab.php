<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentShortUrl\Filament\Forms\Components\TrafficSplitter;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\TabCardHeader;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\WeightBalancer;
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
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
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
                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card'])
                    ->schema([
                        Placeholder::make('targeting_rules_card_header')
                            ->hiddenLabel()
                            ->content(TabCardHeader::make(
                                'heroicon-o-funnel',
                                'validity-tab-card-icon--targeting',
                                'targeting_rules_card_title',
                                'targeting_rules_card_subtitle',
                            )),

                        Placeholder::make('targeting_rules_empty_state')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'targeting-rules-empty-state'])
                            ->visible(fn (Get $get): bool => count($get('targeting_rules') ?? []) === 0)
                            ->content(new HtmlString(
                                '<div class="validity-tab-empty">'.
                                '<div class="validity-tab-empty-icon">'.
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" /></svg>'.
                                '</div>'.
                                '<p class="validity-tab-empty-title">'.e(__('filament-short-url::default.targeting_rules_empty_title')).'</p>'.
                                '<p class="validity-tab-empty-desc">'.e(__('filament-short-url::default.targeting_rules_empty_desc')).'</p>'.
                                '</div>'
                            )),

                        self::targetingRulesRepeater(),
                    ]),

                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card'])
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 12])
                            ->extraAttributes(['class' => 'validity-tab-card-toolbar-grid'])
                            ->schema([
                                Placeholder::make('app_linking_card_header')
                                    ->hiddenLabel()
                                    ->content(TabCardHeader::make(
                                        'heroicon-o-device-phone-mobile',
                                        'validity-tab-card-icon--app-linking',
                                        'app_linking_card_title',
                                        'app_linking_card_subtitle',
                                    ))
                                    ->columnSpan(['default' => 12, 'md' => 9]),

                                Toggle::make('auto_open_app_mobile')
                                    ->label(__('filament-short-url::default.auto_open_app_mobile'))
                                    ->hiddenLabel()
                                    ->default(false)
                                    ->live()
                                    ->inline(false)
                                    ->extraFieldWrapperAttributes([
                                        'class' => 'validity-tab-card-toolbar-action tracking-card-toolbar-action',
                                    ])
                                    ->extraAttributes([
                                        'aria-label' => __('filament-short-url::default.auto_open_app_mobile'),
                                    ])
                                    ->columnSpan(['default' => 12, 'md' => 3]),
                            ]),

                        Placeholder::make('app_linking_empty_state')
                            ->hiddenLabel()
                            ->visible(fn (Get $get): bool => ! $get('auto_open_app_mobile'))
                            ->content(new HtmlString(
                                '<div class="validity-tab-empty">'.
                                '<div class="validity-tab-empty-icon">'.
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>'.
                                '</div>'.
                                '<p class="validity-tab-empty-title">'.e(__('filament-short-url::default.app_linking_empty_title')).'</p>'.
                                '<p class="validity-tab-empty-desc">'.e(__('filament-short-url::default.app_linking_empty_desc')).'</p>'.
                                '</div>'
                            )),

                        Group::make()
                            ->visible(fn (Get $get): bool => (bool) $get('auto_open_app_mobile'))
                            ->extraAttributes(['class' => 'targeting-app-linking-panel'])
                            ->schema([
                                ViewField::make('app_linking_preview')
                                    ->view('filament-short-url::app-linking-preview')
                                    ->viewData(fn (Get $get) => [
                                        'destinationUrl' => $get('destination_url'),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    private static function targetingRulesRepeater(): Repeater
    {
        return Repeater::make('targeting_rules')
            ->hiddenLabel()
            ->extraAttributes(['class' => 'targeting-rules-repeater'])
            ->defaultItems(0)
            ->maxItems(10)
            ->reorderable(false)
            ->addActionLabel(__('filament-short-url::default.add_targeting_rule'))
            ->addAction(
                fn (Action $action) => $action
                    ->icon(Heroicon::Plus)
                    ->outlined()
                    ->after(fn (Repeater $component) => self::refreshTargetingRulesEmptyState($component))
            )
            ->addActionAlignment(Alignment::Start)
            ->grid(1)
            ->deleteAction(
                fn (Action $action) => $action
                    ->icon(Heroicon::Trash)
                    ->iconButton()
                    ->color('danger')
                    ->size(Size::Small)
                    ->extraAttributes(['class' => 'targeting-rule-delete-btn'])
                    ->after(fn (Repeater $component) => self::refreshTargetingRulesEmptyState($component))
            )
            ->afterStateHydrated(function (Repeater $component, $state) {
                if (! is_array($state)) {
                    $component->state([]);

                    return;
                }

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
                Group::make()
                    ->extraAttributes(['class' => 'targeting-rule-item'])
                    ->schema([
                        Placeholder::make('targeting_rule_conditions_heading')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<p class="targeting-rule-section-title">'.e(__('filament-short-url::default.targeting_rule_conditions_title')).'</p>'
                            ))
                            ->columnSpanFull(),

                        SegmentControl::make('match')
                            ->label(__('filament-short-url::default.match'))
                            ->options([
                                'or' => [
                                    'label' => 'OR',
                                    'tooltip' => __('filament-short-url::default.match_or'),
                                ],
                                'and' => [
                                    'label' => 'AND',
                                    'tooltip' => __('filament-short-url::default.match_and'),
                                ],
                            ])
                            ->icons([
                                'or' => 'heroicon-o-squares-2x2',
                                'and' => 'heroicon-o-squares-plus',
                            ])
                            ->default('or')
                            ->size('md')
                            ->separators()
                            ->required()
                            ->extraFieldWrapperAttributes(['class' => 'link-segment-wrap'])
                            ->columnSpanFull(),

                        self::filtersBuilder(),

                        Placeholder::make('targeting_rule_destination_heading')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<p class="targeting-rule-section-title targeting-rule-section-title--destination">'.e(__('filament-short-url::default.targeting_rule_destination_title')).'</p>'
                            ))
                            ->columnSpanFull(),

                        SegmentControl::make('destination_type')
                            ->label(__('filament-short-url::default.destination_type'))
                            ->options([
                                'single' => __('filament-short-url::default.destination_type_single'),
                                'split' => __('filament-short-url::default.destination_type_split'),
                            ])
                            ->icons([
                                'single' => 'heroicon-o-link',
                                'split' => 'heroicon-o-arrow-path-rounded-square',
                            ])
                            ->default('single')
                            ->live()
                            ->size('md')
                            ->separators()
                            ->required()
                            ->extraFieldWrapperAttributes(['class' => 'link-segment-wrap'])
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state === 'split' && empty($get('variants'))) {
                                    $set('variants', [
                                        (string) Str::uuid() => ['label' => 'Variant A', 'url' => '', 'weight' => 50],
                                        (string) Str::uuid() => ['label' => 'Variant B', 'url' => '', 'weight' => 50],
                                    ]);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('url')
                            ->label(__('filament-short-url::default.direct_to_url'))
                            ->url()
                            ->required(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                            ->visible(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                            ->placeholder('https://example.com/landing-page')
                            ->maxLength(2048)
                            ->rules([
                                app(SafeUrl::class),
                            ])
                            ->columnSpanFull(),

                        Group::make()
                            ->extraAttributes(['class' => 'targeting-tab-panel'])
                            ->visible(fn (Get $get): bool => $get('destination_type') === 'split')
                            ->columnSpanFull()
                            ->schema([
                                self::variantsRepeater(),

                                TrafficSplitter::make('traffic_split')
                                    ->label(__('filament-short-url::default.traffic_split'))
                                    ->target('variants')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->columnSpanFull()
            ->default([]);
    }

    private static function variantsRepeater(): Repeater
    {
        return Repeater::make('variants')
            ->hiddenLabel()
            ->compact()
            ->extraAttributes(['class' => 'ab-test-repeater'])
            ->table([
                TableColumn::make(__('filament-short-url::default.variant_label'))
                    ->width('36%'),
                TableColumn::make(__('filament-short-url::default.variant_url'))
                    ->width('64%'),
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
                    ->placeholder('https://example.com/landing-page')
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
                fn (Action $action) => $action
                    ->icon(Heroicon::Trash)
                    ->iconButton()
                    ->color('danger')
                    ->size(Size::Small)
                    ->visible(fn (Get $get): bool => count($get('variants') ?? []) > 2)
                    ->after(fn ($component) => WeightBalancer::balanceWeightsEqually($component))
            )
            ->addAction(
                fn (Action $action) => $action
                    ->icon(Heroicon::Plus)
                    ->outlined()
                    ->after(fn ($component) => WeightBalancer::balanceWeightsEqually($component))
            )
            ->addActionLabel(__('filament-short-url::default.add_url'))
            ->addActionAlignment(Alignment::Start)
            ->visible(fn (Get $get): bool => $get('destination_type') === 'split')
            ->columnSpanFull();
    }

    private static function refreshTargetingRulesEmptyState(Repeater $component): void
    {
        $component->getLivewire()->partiallyRenderSchemaComponent('targeting_rules_empty_state');
    }

    private static function filtersBuilder(): Builder
    {
        return Builder::make('filters')
            ->hiddenLabel()
            ->addActionLabel(__('filament-short-url::default.add_filter'))
            ->reorderable(false)
            ->collapsible()
            ->blockNumbers(false)
            ->addBetweenAction(fn ($action) => $action->hidden())
            ->minItems(1)
            ->extraAttributes(['class' => 'targeting-filters-builder'])
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
                            $fail(__('filament-short-url::default.filter_duplicate_error'));
                        }
                    };
                },
            ])
            ->columnSpanFull();
    }
}
