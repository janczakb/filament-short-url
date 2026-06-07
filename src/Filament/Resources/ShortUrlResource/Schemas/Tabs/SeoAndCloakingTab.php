<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Services\OgImageImporter;
use Bjanczak\FilamentShortUrl\Services\OgImageProcessor;
use Bjanczak\FilamentShortUrl\Services\ShortUrlTempStorage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                Section::make(__('filament-short-url::default.seo_section_title'))
                    ->description(__('filament-short-url::default.seo_section_desc'))
                    ->schema([
                        Toggle::make('is_cloaked')
                            ->label(__('filament-short-url::default.is_cloaked'))
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.is_cloaked_helper'))
                            ->default(false)
                            ->live()
                            ->inline(),

                        Toggle::make('do_index')
                            ->label(__('filament-short-url::default.do_index'))
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.do_index_helper'))
                            ->default(false)
                            ->live()
                            ->inline(),
                    ])
                    ->contained(false)
                    ->columns(2),

                Section::make(__('filament-short-url::default.og_section_title'))
                    ->description(__('filament-short-url::default.og_section_desc'))
                    ->schema([
                        TextInput::make('og_title')
                            ->label(__('filament-short-url::default.og_title'))
                            ->placeholder('Custom Open Graph Title')
                            ->maxLength(255)
                            ->live(),

                        Textarea::make('og_description')
                            ->label(__('filament-short-url::default.og_description'))
                            ->placeholder('Custom Open Graph Description')
                            ->maxLength(500)
                            ->rows(3)
                            ->live()
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
                            ->columnSpanFull(),

                        Hidden::make('og_image_scraped')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get, OgImageImporter $importer, Component $livewire): void {
                                if (blank($state) || filled($get('og_image'))) {
                                    $set('is_scraping', false);
                                    $livewire->js('window.fsuDispatchScraping(false)');

                                    return;
                                }

                                try {
                                    $path = $importer->importFromUrl($state);

                                    if ($path !== null) {
                                        $set('og_image', $path);
                                    }
                                } finally {
                                    $set('is_scraping', false);
                                    $livewire->js('window.fsuDispatchScraping(false)');
                                }
                            }),
                    ])
                    ->contained(false),
            ]);
    }
}
