<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\HtmlString;

class MarketingTab
{
    /**
     * Build the marketing details form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_marketing'))
            ->icon('heroicon-o-megaphone')
            ->schema([
                Section::make(__('filament-short-url::default.marketing_pixels_title'))
                    ->description(__('filament-short-url::default.marketing_pixels_desc'))
                    ->schema([
                        Select::make('pixels')
                            ->label(__('filament-short-url::default.pixels_navigation_label'))
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
                                '<path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm0 2.5a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-1.5 0v-4a.75.75 0 0 1 .75-.75Z"></path>'.
                                '</svg>'.
                                '</div>'.
                                '<div class="text-sm prose dark:prose-invert min-w-0 w-full text-neutral-800 dark:text-neutral-300" data-component-part="callout-content">'.
                                '<span data-as="p">'.
                                __('filament-short-url::default.webhook_helper_alert').
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
}
