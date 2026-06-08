<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Fields\QrDesignerSidebarField;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\LinkTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\MarketingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\PasswordTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\SeoAndCloakingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\TargetingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\TrackingTab;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs\ValidityAndLimitsTab;
use Bjanczak\FilamentShortUrl\Models\ShortUrlFolder;
use Bjanczak\FilamentShortUrl\Services\OgFormImageResolver;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ShortUrlForm
{
    /**
     * Configure the short URL resource form schema.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make([
                'default' => 1,
                'md' => 18,
                'lg' => 18,
            ])
                ->columnSpanFull()
                ->schema([
                    Tabs::make()
                        ->contained(false)
                        ->tabs([
                            LinkTab::make(),
                            TargetingTab::make(),
                            PasswordTab::make(),
                            ValidityAndLimitsTab::make(),
                            TrackingTab::make(),
                            MarketingTab::make(),
                            SeoAndCloakingTab::make(),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'md' => 12,
                            'lg' => 12,
                        ]),

                    Section::make()
                        ->contained(false)
                        ->extraAttributes([
                            'class' => 'create-link-sidebar sticky top-0 border-0 bg-transparent',
                        ])
                        ->schema([
                            Select::make('folder_id')
                                ->extraFieldWrapperAttributes([
                                    'class' => 'create-link-sidebar-select-wrapper',
                                ])
                                ->label(new HtmlString('<span class="font-semibold">'.__('filament-short-url::default.folder_resource_title').'</span>'))
                                ->relationship('folder', 'name')
                                ->allowHtml()
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->getOptionHtml())
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
                                        ->allowHtml()
                                        ->options(ShortUrlFolder::getColorOptions())
                                        ->default('gray')
                                        ->required()
                                        ->native(false),
                                ]),

                            Hidden::make('qr_options')
                                ->id('qr-options-json-input')
                                ->dehydrateStateUsing(fn (mixed $state): array => is_array($state) ? $state : (json_decode($state ?? '{}', true) ?: []))
                                ->afterStateHydrated(function ($component, mixed $state): void {
                                    $component->state(is_array($state) ? json_encode($state) : ($state ?? '{}'));
                                }),

                            Hidden::make('qr_logo')
                                ->id('qr-logo-path-input')
                                ->nullable(),

                            Hidden::make('is_scraping')
                                ->default(false)
                                ->live(),

                            QrDesignerSidebarField::make()
                                ->label(__('filament-short-url::default.action_qr')),

                            ViewField::make('sidebar_social_preview')
                                ->view('filament-short-url::sidebar.social-preview')
                                ->viewData(function (ViewField $component, ?Get $get = null) {
                                    $formState = [];

                                    if (method_exists($component, 'getContainer')) {
                                        $formState = $component->getContainer()->getState();
                                    }

                                    if ($get) {
                                        $formState = array_merge($formState, [
                                            'og_title' => $get('og_title'),
                                            'og_description' => $get('og_description'),
                                            'og_image' => $get('og_image'),
                                            'og_image_scraped' => $get('og_image_scraped'),
                                            'is_scraping' => $get('is_scraping'),
                                        ]);
                                    }

                                    if (method_exists($component, 'getLivewire')) {
                                        $livewire = $component->getLivewire();

                                        if (isset($livewire->data) && is_array($livewire->data)) {
                                            $formState = array_merge($livewire->data, $formState);
                                        }
                                    }

                                    $resolver = app(OgFormImageResolver::class);

                                    return [
                                        'isPasswordProtected' => (bool) ($formState['password_active_flag'] ?? false)
                                            || filled($formState['password'] ?? null),
                                        'ogTitle' => filled($formState['og_title'] ?? null) ? $formState['og_title'] : null,
                                        'ogDescription' => filled($formState['og_description'] ?? null) ? $formState['og_description'] : null,
                                        'ogImageUrl' => $resolver->resolvePreviewUrl(
                                            $formState['og_image'] ?? null,
                                            $formState['og_image_scraped'] ?? null,
                                        ),
                                        'isScraping' => (bool) ($formState['is_scraping'] ?? false),
                                    ];
                                })
                                ->dehydrated(false)
                                ->columnSpanFull(),
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'md' => 6,
                            'lg' => 6,
                        ]),
                ]),
        ]);
    }
}
