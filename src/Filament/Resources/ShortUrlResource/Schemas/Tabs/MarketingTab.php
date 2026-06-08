<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlPixelResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\TabCardHeader;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\WebhookPayloadExample;
use Bjanczak\FilamentShortUrl\Models\ShortUrlPixel;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
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
                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card marketing-pixels-card'])
                    ->schema([
                        Placeholder::make('marketing_pixels_card_header')
                            ->hiddenLabel()
                            ->content(TabCardHeader::make(
                                'heroicon-o-cursor-arrow-rays',
                                'validity-tab-card-icon--pixels',
                                'marketing_pixels_title',
                                'marketing_pixels_desc',
                                compact: true,
                            )),

                        Placeholder::make('marketing_pixels_empty_state')
                            ->hiddenLabel()
                            ->visible(fn (Get $get): bool => ! self::hasSelectedPixels($get))
                            ->content(new HtmlString(
                                '<div class="validity-tab-empty">'.
                                '<div class="validity-tab-empty-icon">'.
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59" /></svg>'.
                                '</div>'.
                                '<p class="validity-tab-empty-title">'.e(__('filament-short-url::default.marketing_pixels_empty_title')).'</p>'.
                                '<p class="validity-tab-empty-desc">'.e(__('filament-short-url::default.marketing_pixels_empty_desc')).'</p>'.
                                '</div>'
                            )),

                        Select::make('pixels')
                            ->label(__('filament-short-url::default.pixels_navigation_label'))
                            ->hiddenLabel()
                            ->multiple()
                            ->relationship('pixels', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))
                            ->preload()
                            ->searchable()
                            ->live()
                            ->placeholder(__('filament-short-url::default.marketing_pixels_select_placeholder'))
                            ->createOptionForm(ShortUrlPixelResource::formComponents())
                            ->createOptionUsing(function (array $data): int {
                                return ShortUrlPixel::query()->create([
                                    'name' => $data['name'],
                                    'type' => $data['type'],
                                    'pixel_id' => $data['pixel_id'],
                                    'is_active' => (bool) ($data['is_active'] ?? true),
                                ])->getKey();
                            })
                            ->createOptionAction(fn (Action $action): Action => $action
                                ->label(__('filament-short-url::default.empty_state_pixel_action'))
                                ->modalHeading(__('filament-short-url::default.empty_state_pixel_action'))
                                ->icon(Heroicon::Plus)
                                ->iconButton()
                                ->color('gray')
                                ->modalWidth('md')
                                ->modalAutofocus(false)
                                ->tooltip(__('filament-short-url::default.empty_state_pixel_action')))
                            ->extraFieldWrapperAttributes(['class' => 'marketing-pixels-field']),
                    ]),

                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card marketing-webhook-card'])
                    ->schema([
                        Placeholder::make('marketing_webhook_card_header')
                            ->hiddenLabel()
                            ->content(TabCardHeader::make(
                                'heroicon-o-bolt',
                                'validity-tab-card-icon--webhook',
                                'marketing_webhooks_title',
                                'marketing_webhooks_desc',
                                compact: true,
                            )),

                        Placeholder::make('marketing_webhook_empty_state')
                            ->hiddenLabel()
                            ->visible(fn (Get $get): bool => ! self::hasWebhookUrl($get))
                            ->content(new HtmlString(
                                '<div class="validity-tab-empty">'.
                                '<div class="validity-tab-empty-icon">'.
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.467.732-3.553" /></svg>'.
                                '</div>'.
                                '<p class="validity-tab-empty-title">'.e(__('filament-short-url::default.marketing_webhook_empty_title')).'</p>'.
                                '<p class="validity-tab-empty-desc">'.e(__('filament-short-url::default.marketing_webhook_empty_desc')).'</p>'.
                                '</div>'
                            )),

                        Group::make()
                            ->extraAttributes(['class' => 'marketing-webhook-panel'])
                            ->schema([
                                Placeholder::make('marketing_webhook_callout')
                                    ->hiddenLabel()
                                    ->visible(fn (Get $get): bool => self::hasWebhookUrl($get))
                                    ->content(new HtmlString(
                                        '<div class="marketing-tab-callout">'.
                                        '<div class="marketing-tab-callout-icon">'.
                                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" /></svg>'.
                                        '</div>'.
                                        '<p class="marketing-tab-callout-text">'.e(__('filament-short-url::default.webhook_helper_alert')).'</p>'.
                                        '</div>'
                                    ))
                                    ->columnSpanFull(),

                                TextInput::make('webhook_url')
                                    ->label(__('filament-short-url::default.webhook_url'))
                                    ->placeholder('https://api.yourcrm.com/webhooks/clicks')
                                    ->prefixIcon('heroicon-m-link')
                                    ->url()
                                    ->maxLength(2048)
                                    ->nullable()
                                    ->live(onBlur: true)
                                    ->extraFieldWrapperAttributes(['class' => 'marketing-webhook-field']),

                                Actions::make([
                                    Action::make('show_webhook_payload')
                                        ->label(__('filament-short-url::default.webhook_show_payload'))
                                        ->icon(Heroicon::CodeBracketSquare)
                                        ->color('gray')
                                        ->outlined()
                                        ->size('sm')
                                        ->modalHeading(__('filament-short-url::default.webhook_payload_modal_title'))
                                        ->modalDescription(__('filament-short-url::default.webhook_payload_modal_desc'))
                                        ->modalWidth('3xl')
                                        ->modalContent(fn () => view('filament-short-url::webhook-payload-example', [
                                            'rawJson' => WebhookPayloadExample::visitedEventSampleJson(),
                                        ]))
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel(__('filament-short-url::default.close_button')),
                                ])
                                    ->visible(fn (Get $get): bool => self::hasWebhookUrl($get))
                                    ->alignment(Alignment::Start)
                                    ->extraAttributes(['class' => 'marketing-webhook-payload-action']),
                            ]),
                    ]),
            ]);
    }

    private static function hasSelectedPixels(Get $get): bool
    {
        $pixels = $get('pixels') ?? [];

        return is_array($pixels) && count($pixels) > 0;
    }

    private static function hasWebhookUrl(Get $get): bool
    {
        return filled($get('webhook_url'));
    }
}
