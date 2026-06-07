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
use Bjanczak\FilamentShortUrl\Models\ShortUrlTag;
use Bjanczak\FilamentShortUrl\Rules\SafeUrl;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Filament\Actions\Action;
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
use Illuminate\Support\HtmlString;
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
                        ->grouped()
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
                        ->required(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                        ->visible(fn (Get $get): bool => $get('destination_type') === 'single' || ! $get('destination_type'))
                        ->url()
                        ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.destination_url_helper'))
                        ->hint(function (Get $get) {
                            $isScraping = $get('is_scraping') ? 'true' : 'false';
                            $label = e(__('filament-short-url::default.fetching_metadata'));

                            return new HtmlString(
                                '<span'
                                .' x-data="{ scraping: '.$isScraping.' }"'
                                .' x-on:fsu-scraping-start.window="scraping = true"'
                                .' x-on:fsu-scraping-end.window="scraping = false"'
                                .' x-show="scraping"'
                                .' x-cloak'
                                .' class="flex items-center gap-x-1.5 text-xs text-primary-600 dark:text-primary-400 font-semibold"'
                                .'>'
                                .'<svg class="animate-spin h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">'
                                .'<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>'
                                .'<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>'
                                .'</svg>'
                                .$label
                                .'</span>'
                            );
                        })
                        ->placeholder('https://example.com/site-url')
                        ->maxLength(2048)
                        ->rules([
                            app(SafeUrl::class),
                        ])
                        ->live(debounce: 500)
                        ->afterStateUpdatedJs(<<<'JS'
                            window.fsuInitScrape($get, $el.querySelector('input'));
                            if ($state) {
                                window.fsuScrape($state, $get, $set, $el.querySelector('input'));
                            }
                        JS)
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
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
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
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.url_key_helper'))
                            ->alphaDash()
                            ->maxLength(32)
                            ->default(fn (ShortUrlService $service) => $service->generateKey())
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
                        ->native(false)
                        ->default(fn () => config('filament-short-url.redirect_status_code', 302))
                        ->required()->columnSpanFull(),
                ])->contained(false)->columns(2),

                Section::make(__('filament-short-url::default.form_section_options'))->schema([
                    Toggle::make('is_enabled')
                        ->label(__('filament-short-url::default.status'))
                        ->default(true)
                        ->inline(),

                    Toggle::make('forward_query_params')
                        ->label(__('filament-short-url::default.forward_query_params'))
                        ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.forward_query_params_helper'))
                        ->default(false)
                        ->inline(),
                ])->contained(false)->columns(2),

                Section::make()
                    ->schema([
                        Select::make('tags')
                            ->label(__('filament-short-url::default.tags_navigation_label'))
                            ->multiple()
                            ->maxItems(5)
                            ->relationship('tags', 'name')
                            ->allowHtml()
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getOptionHtml())
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
                                    ->allowHtml()
                                    ->options(ShortUrlTag::getColorOptions())
                                    ->default('gray')
                                    ->required()
                                    ->native(false),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->contained(false)
                    ->columns(1),

                Section::make()
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('filament-short-url::default.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->contained(false),
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
