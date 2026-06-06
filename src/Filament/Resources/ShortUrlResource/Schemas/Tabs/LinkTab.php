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
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Bjanczak\FilamentShortUrl\Rules\SafeUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class LinkTab
{
    /**
     * Build the link details form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_link'))
            ->icon('heroicon-o-link')
            ->schema([
                Section::make()->schema([
                    ToggleButtons::make('destination_type')
                        ->label(__('filament-short-url::default.destination_type'))
                        ->options([
                            'single' => __('filament-short-url::default.destination_type_single'),
                            'split' => __('filament-short-url::default.destination_type_split'),
                        ])
                        ->colors([
                            'single' => 'primary',
                            'split' => 'warning',
                        ])
                        ->icons([
                            'single' => 'heroicon-o-link',
                            'split' => 'heroicon-o-arrow-path-rounded-square',
                        ])
                        ->default('single')
                        ->live()
                        ->inline()
                        ->columnSpanFull()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            if ($state === 'split' && empty($get('rotation_variants'))) {
                                $set('rotation_variants', [
                                    (string) Str::uuid() => ['label' => 'Variant A', 'url' => '', 'weight' => 50],
                                    (string) Str::uuid() => ['label' => 'Variant B', 'url' => '', 'weight' => 50],
                                ]);
                            }
                        }),

                    TextInput::make('destination_url')
                        ->label(__('filament-short-url::default.destination_url'))
                        ->helperText(__('filament-short-url::default.destination_url_helper'))
                        ->required(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                        ->visible(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                        ->url()
                        ->maxLength(2048)
                        ->rules([
                            app(SafeUrl::class),
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

                    Repeater::make('rotation_variants')
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
                                ->visible(fn (Get $get): bool => count($get('rotation_variants') ?? []) > 2)
                                ->after(fn ($component) => WeightBalancer::balanceWeightsEqually($component))
                        )
                        ->addAction(
                            fn ($action) => $action->after(fn ($component) => WeightBalancer::balanceWeightsEqually($component))
                        )
                        ->addActionLabel(__('filament-short-url::default.add_url'))
                        ->visible(fn (Get $get): bool => $get('destination_type') === 'split')
                        ->columnSpanFull(),

                    TrafficSplitter::make('traffic_split')
                        ->label(__('filament-short-url::default.traffic_split'))
                        ->target('rotation_variants')
                        ->visible(fn (Get $get): bool => $get('destination_type') === 'split')
                        ->columnSpanFull(),

                    FusedGroup::make([
                        Select::make('custom_domain_id')
                            ->hiddenLabel()
                            ->options(function () {
                                $domains = ShortUrlCustomDomain::where('is_active', true)
                                    ->where('is_verified', true)
                                    ->pluck('domain', 'id');

                                if (! config('filament-short-url.disable_default_domain', false)) {
                                    $defaultDomain = request()->getHost() ?: parse_url(config('app.url'), PHP_URL_HOST);
                                    $domains = collect(['default' => $defaultDomain])->union($domains);
                                }

                                return $domains;
                            })
                            ->default(function () {
                                if (! config('filament-short-url.disable_default_domain', false)) {
                                    return 'default';
                                }

                                $firstDomain = ShortUrlCustomDomain::where('is_active', true)
                                    ->where('is_verified', true)
                                    ->first();

                                return $firstDomain ? $firstDomain->id : null;
                            })
                            ->afterStateHydrated(function (Select $component, $state) {
                                if ($state === null && ! config('filament-short-url.disable_default_domain', false)) {
                                    $component->state('default');
                                }
                            })
                            ->dehydrateStateUsing(fn ($state) => $state === 'default' ? null : $state)
                            ->disabled(function (?ShortUrl $record) {
                                if ($record && $record->exists && config('filament-short-url.lock_url_key', false)) {
                                    return true;
                                }

                                $domainsCount = ShortUrlCustomDomain::where('is_active', true)
                                    ->where('is_verified', true)
                                    ->count();

                                $defaultEnabled = ! config('filament-short-url.disable_default_domain', false);
                                $totalOptionsCount = $domainsCount + ($defaultEnabled ? 1 : 0);

                                return $totalOptionsCount <= 1;
                            })
                            ->dehydrated()
                            ->required(fn () => (bool) config('filament-short-url.disable_default_domain', false))
                            ->selectablePlaceholder(false)
                            ->nullable()
                            ->native(false),

                        TextInput::make('url_key')
                            ->hiddenLabel()
                            ->helperText(__('filament-short-url::default.url_key_helper'))
                            ->alphaDash()
                            ->maxLength(32)
                            ->unique('short_urls', 'url_key', ignoreRecord: true)
                            ->disabled(fn (?ShortUrl $record) => $record && $record->exists && config('filament-short-url.lock_url_key', false))
                            ->placeholder('auto-generated')
                            ->suffixAction(
                                Action::make('regenerate')
                                    ->icon('heroicon-o-arrow-path')
                                    ->tooltip('Generate new key')
                                    ->action(function (Set $set, ShortUrlService $service): void {
                                        $set('url_key', $service->generateKey());
                                    })
                                    ->visible(fn (?ShortUrl $record) => ! ($record && $record->exists && config('filament-short-url.lock_url_key', false)))
                            ),
                    ])
                        ->extraAttributes(['class' => 'custom-fused'])
                        ->label(__('filament-short-url::default.short_link_label'))
                        ->columns(2)
                        ->columnSpanFull(),

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

                Section::make(__('filament-short-url::default.folders_navigation_label').' & '.__('filament-short-url::default.tags_navigation_label'))
                    ->schema([
                        Select::make('folder_id')
                            ->label(__('filament-short-url::default.folder_resource_title'))
                            ->relationship('folder', 'name')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(__('filament-short-url::default.folder_name'))
                                    ->required()
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->label(__('filament-short-url::default.folder_slug'))
                                    ->required()
                                    ->maxLength(100)
                                    ->unique('short_url_folders', 'slug'),
                                Select::make('color')
                                    ->label(__('filament-short-url::default.folder_color'))
                                    ->options([
                                        'gray' => 'Gray',
                                        'red' => 'Red',
                                        'blue' => 'Blue',
                                        'green' => 'Green',
                                        'yellow' => 'Yellow',
                                        'indigo' => 'Indigo',
                                        'purple' => 'Purple',
                                        'pink' => 'Pink',
                                    ])
                                    ->default('gray')
                                    ->required()
                                    ->native(false),
                            ]),

                        Select::make('tags')
                            ->label(__('filament-short-url::default.tags_navigation_label'))
                            ->multiple()
                            ->maxItems(5)
                            ->relationship('tags', 'name')
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(__('filament-short-url::default.tag_name'))
                                    ->required()
                                    ->maxLength(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                                TextInput::make('slug')
                                    ->label(__('filament-short-url::default.tag_slug'))
                                    ->required()
                                    ->maxLength(100)
                                    ->unique('short_url_tags', 'slug'),
                                Select::make('color')
                                    ->label(__('filament-short-url::default.tag_color'))
                                    ->options([
                                        'gray' => 'Gray',
                                        'red' => 'Red',
                                        'blue' => 'Blue',
                                        'green' => 'Green',
                                        'yellow' => 'Yellow',
                                        'indigo' => 'Indigo',
                                        'purple' => 'Purple',
                                        'pink' => 'Pink',
                                    ])
                                    ->default('gray')
                                    ->required()
                                    ->native(false),
                            ]),

                        Toggle::make('is_archived')
                            ->label(__('filament-short-url::default.is_archived'))
                            ->helperText(__('filament-short-url::default.is_archived_helper'))
                            ->default(false)
                            ->inline(false)
                            ->hiddenOn('create')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('filament-short-url::default.form_section_notes'))->schema([
                    Textarea::make('notes')
                        ->label(__('filament-short-url::default.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    /**
     * Synchronize campaign parameters into the query parameters of the destination URL.
     */
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
}
