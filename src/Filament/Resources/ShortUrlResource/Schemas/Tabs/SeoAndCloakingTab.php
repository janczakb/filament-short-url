<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\PasswordOpenGraphGuard;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\TabCardHeader;
use Bjanczak\FilamentShortUrl\Services\OgImageImporter;
use Bjanczak\FilamentShortUrl\Services\OgImageProcessor;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTempStorage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SeoAndCloakingTab
{
    /**
     * Build the SEO & Social details form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_seo_social'))
            ->icon('heroicon-o-globe-alt')
            ->schema([
                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card seo-settings-card'])
                    ->schema([
                        Placeholder::make('seo_settings_card_header')
                            ->hiddenLabel()
                            ->content(TabCardHeader::make(
                                'heroicon-o-magnifying-glass-circle',
                                'validity-tab-card-icon--seo',
                                'seo_section_title',
                                'seo_section_desc',
                                compact: true,
                            )),

                        Grid::make(['default' => 1, 'md' => 2])
                            ->extraAttributes(['class' => 'seo-settings-grid'])
                            ->schema([
                                self::toggleCard(
                                    'is_cloaked',
                                    'is_cloaked',
                                    'is_cloaked_helper',
                                    false,
                                ),
                                self::toggleCard(
                                    'do_index',
                                    'do_index',
                                    'do_index_helper',
                                    false,
                                ),
                            ]),
                    ]),

                Section::make()
                    ->contained(false)
                    ->visible(fn (Get $get): bool => ! PasswordOpenGraphGuard::isFormPasswordProtected($get))
                    ->extraAttributes(['class' => 'validity-tab-card seo-og-card'])
                    ->schema([
                        Placeholder::make('seo_og_card_header')
                            ->hiddenLabel()
                            ->content(TabCardHeader::make(
                                'heroicon-o-photo',
                                'validity-tab-card-icon--og',
                                'og_section_title',
                                'og_section_desc',
                                compact: true,
                            )),

                        Group::make()
                            ->extraAttributes(['class' => 'seo-og-panel'])
                            ->schema([
                                TextInput::make('og_title')
                                    ->label(__('filament-short-url::default.og_title'))
                                    ->placeholder(__('filament-short-url::default.og_title_placeholder'))
                                    ->maxLength(255)
                                    ->live()
                                    ->extraFieldWrapperAttributes(['class' => 'seo-og-field']),

                                Textarea::make('og_description')
                                    ->label(__('filament-short-url::default.og_description'))
                                    ->placeholder(__('filament-short-url::default.og_description_placeholder'))
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->live()
                                    ->extraFieldWrapperAttributes(['class' => 'seo-og-field'])
                                    ->columnSpanFull(),

                                FileUpload::make('og_image')
                                    ->label(__('filament-short-url::default.og_image'))
                                    ->image()
                                    ->disk('public')
                                    ->directory(fn (ShortUrlTempStorage $temp): string => $temp->bucketDirectory())
                                    ->visibility('public')
                                    ->maxSize(4096)
                                    ->rule(Rule::dimensions()->minWidth(600)->minHeight(313))
                                    ->automaticallyCropImagesToAspectRatio('16:9')
                                    ->automaticallyResizeImagesMode('cover')
                                    ->automaticallyResizeImagesToWidth('1200')
                                    ->automaticallyResizeImagesToHeight('630')
                                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, OgImageProcessor $processor): string {
                                        $storedPath = $processor->storeWebpFromPath($file->getRealPath(), ShortUrlTempStorage::ROOT);

                                        if ($storedPath === null) {
                                            return $file->storePubliclyAs(
                                                app(ShortUrlTempStorage::class)->bucketDirectory(),
                                                $file->getClientOriginalName(),
                                                'public',
                                            );
                                        }

                                        return $storedPath;
                                    })
                                    ->live()
                                    ->afterStateHydrated(function (mixed $state, Component $livewire): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $livewire->js('window.fsuLockScrape && window.fsuLockScrape()');
                                    })
                                    ->afterStateUpdated(function (mixed $state, Set $set, Component $livewire): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $set('og_image_scraped', null);
                                        $set('is_scraping', false);
                                        $livewire->js('window.fsuDispatchScraping(false); window.dispatchEvent(new CustomEvent("fsu-og-image-updated"));');
                                    })
                                    ->afterStateUpdatedJs(<<<'JS'
                                        if ($state) {
                                            window.fsuOnManualOgImage && window.fsuOnManualOgImage($get, $set);
                                        } else {
                                            window.fsuRetryScrapeAfterImageRemoved && window.fsuRetryScrapeAfterImageRemoved($get, $set);
                                        }
                                    JS)
                                    ->extraFieldWrapperAttributes(['class' => 'seo-og-field'])
                                    ->columnSpanFull(),

                                Hidden::make('og_image_scraped')
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Set $set, Get $get, OgImageImporter $importer, Component $livewire): void {
                                        if (PasswordOpenGraphGuard::isFormPasswordProtected($get)) {
                                            $set('og_image_scraped', null);

                                            return;
                                        }

                                        if (blank($state) || filled($get('og_image'))) {
                                            $set('is_scraping', false);
                                            $livewire->js('window.fsuDispatchScraping(false)');

                                            return;
                                        }

                                        try {
                                            $path = $importer->importFromUrl($state);

                                            if ($path !== null) {
                                                $set('og_image', $path);
                                                $livewire->js('window.fsuLockScrape && window.fsuLockScrape()');
                                            }
                                        } finally {
                                            $set('is_scraping', false);
                                            $livewire->js('window.fsuDispatchScraping(false); window.fsuLockScrape && window.fsuLockScrape()');
                                        }
                                    }),
                            ]),
                    ]),
            ]);
    }

    private static function toggleCard(
        string $name,
        string $labelKey,
        string $descKey,
        bool $default,
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
                    ->default($default)
                    ->live()
                    ->inline(false)
                    ->extraFieldWrapperAttributes(['class' => 'tracking-field-card-toggle'])
                    ->extraAttributes([
                        'aria-label' => __("filament-short-url::default.{$labelKey}"),
                    ]),
            ]);
    }
}
