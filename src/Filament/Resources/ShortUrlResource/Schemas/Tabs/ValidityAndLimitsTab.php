<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\TabCardHeader;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

class ValidityAndLimitsTab
{
    /**
     * Build the validity and limits form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_validity'))
            ->icon('heroicon-o-clock')
            ->schema([
                Section::make(__('filament-short-url::default.expiration_dates_section_title'))
                    ->description(__('filament-short-url::default.expiration_dates_section_desc'))
                    ->icon('heroicon-o-calendar-days')
                    ->contained(false)
                    ->schema([
                        Section::make()
                            ->contained(false)
                            ->extraAttributes(['class' => 'validity-tab-card'])
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 12])
                                    ->extraAttributes(['class' => 'validity-tab-card-toolbar-grid'])
                                    ->schema([
                                        Placeholder::make('schedule_card_header')
                                            ->hiddenLabel()
                                            ->content(TabCardHeader::make(
                                                'heroicon-o-calendar-days',
                                                'validity-tab-card-icon--schedule',
                                                'validity_schedule_card_title',
                                                'validity_schedule_card_subtitle',
                                            ))
                                            ->columnSpan(['default' => 12, 'md' => 9]),

                                        Toggle::make('use_date_validity')
                                            ->label(__('filament-short-url::default.use_date_validity'))
                                            ->hiddenLabel()
                                            ->dehydrated(false)
                                            ->live()
                                            ->inline(false)
                                            ->extraFieldWrapperAttributes([
                                                'class' => 'validity-tab-card-toolbar-action',
                                            ])
                                            ->extraAttributes([
                                                'aria-label' => __('filament-short-url::default.use_date_validity'),
                                            ])
                                            ->columnSpan(['default' => 12, 'md' => 3])
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
                                            }),
                                    ]),

                                Placeholder::make('schedule_empty_state')
                                    ->hiddenLabel()
                                    ->visible(fn (Get $get): bool => ! $get('use_date_validity'))
                                    ->content(new HtmlString(
                                        '<div class="validity-tab-empty">'.
                                        '<div class="validity-tab-empty-icon">'.
                                        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>'.
                                        '</div>'.
                                        '<p class="validity-tab-empty-title">'.e(__('filament-short-url::default.validity_schedule_empty_title')).'</p>'.
                                        '<p class="validity-tab-empty-desc">'.e(__('filament-short-url::default.validity_schedule_empty_desc')).'</p>'.
                                        '</div>'
                                    )),

                                Group::make()
                                    ->visible(fn (Get $get): bool => (bool) $get('use_date_validity'))
                                    ->extraAttributes(['class' => 'validity-schedule-panel'])
                                    ->schema([
                                        Placeholder::make('schedule_timeline_hint')
                                            ->hiddenLabel()
                                            ->content(new HtmlString(
                                                '<div class="validity-timeline-hint">'.
                                                '<span class="validity-timeline-dot validity-timeline-dot--start"></span>'.
                                                '<span class="validity-timeline-line"></span>'.
                                                '<span class="validity-timeline-dot validity-timeline-dot--end"></span>'.
                                                '<span class="validity-timeline-label">'.e(__('filament-short-url::default.validity_schedule_timeline_label')).'</span>'.
                                                '</div>'
                                            ))
                                            ->columnSpanFull(),

                                        Grid::make([
                                            'default' => 1,
                                            'md' => 2,
                                        ])
                                            ->schema([
                                                DateTimePicker::make('activated_at')
                                                    ->label(__('filament-short-url::default.activated_at'))
                                                    ->helperText(__('filament-short-url::default.validity_activated_at_helper'))
                                                    ->prefixIcon('heroicon-m-play-circle')
                                                    ->nullable()
                                                    ->native(false)
                                                    ->withoutSeconds()
                                                    ->live(onBlur: true)
                                                    ->required(fn (Get $get): bool => (bool) $get('use_date_validity'))
                                                    ->minDate(now()->startOfDay())
                                                    ->maxDate(fn (Get $get) => $get('expires_at')),

                                                DateTimePicker::make('expires_at')
                                                    ->label(__('filament-short-url::default.expires_at'))
                                                    ->helperText(__('filament-short-url::default.validity_expires_at_helper'))
                                                    ->prefixIcon('heroicon-m-stop-circle')
                                                    ->nullable()
                                                    ->native(false)
                                                    ->withoutSeconds()
                                                    ->live(onBlur: true)
                                                    ->minDate(fn (Get $get) => $get('activated_at') ?: now()->startOfDay()),
                                            ]),

                                        TextInput::make('expiration_redirect_url')
                                            ->label(__('filament-short-url::default.expiration_redirect_url'))
                                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.expiration_redirect_url_helper'))
                                            ->placeholder('https://example.com/campaign-ended')
                                            ->url()
                                            ->maxLength(2048)
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),

                Section::make(__('filament-short-url::default.visit_limits_section_title'))
                    ->description(__('filament-short-url::default.visit_limits_section_desc'))
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->contained(false)
                    ->schema([
                        Section::make()
                            ->contained(false)
                            ->extraAttributes(['class' => 'validity-tab-card'])
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'lg' => 12,
                                ])
                                    ->schema([
                                        Group::make()
                                            ->columnSpan(['default' => 1, 'lg' => 7])
                                            ->extraAttributes(['class' => 'validity-limit-block'])
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 12])
                                                    ->extraAttributes(['class' => 'validity-tab-card-toolbar-grid'])
                                                    ->schema([
                                                        Placeholder::make('single_use_header')
                                                            ->hiddenLabel()
                                                            ->content(TabCardHeader::make(
                                                                'heroicon-o-lock-closed',
                                                                'validity-tab-card-icon--limit',
                                                                'single_use',
                                                                'single_use_helper',
                                                            ))
                                                            ->columnSpan(['default' => 12, 'md' => 9]),

                                                        Toggle::make('single_use')
                                                            ->label(__('filament-short-url::default.single_use'))
                                                            ->hiddenLabel()
                                                            ->default(false)
                                                            ->live()
                                                            ->inline(false)
                                                            ->extraFieldWrapperAttributes([
                                                                'class' => 'validity-tab-card-toolbar-action',
                                                            ])
                                                            ->extraAttributes([
                                                                'aria-label' => __('filament-short-url::default.single_use'),
                                                            ])
                                                            ->columnSpan(['default' => 12, 'md' => 3]),
                                                    ]),
                                            ]),

                                        Group::make()
                                            ->columnSpan(['default' => 1, 'lg' => 5])
                                            ->extraAttributes([
                                                'class' => 'validity-limit-block validity-limit-block--counter validity-max-visits-card',
                                            ])
                                            ->schema([
                                                Placeholder::make('max_visits_header')
                                                    ->hiddenLabel()
                                                    ->content(new HtmlString(
                                                        '<p class="validity-tab-card-title">'.e(__('filament-short-url::default.max_visits')).'</p>'.
                                                        '<p class="validity-tab-card-subtitle">'.e(__('filament-short-url::default.max_visits_helper')).'</p>'
                                                    )),

                                                NumberStepper::make('max_visits')
                                                    ->hiddenLabel()
                                                    ->minValue(1)
                                                    ->nullable()
                                                    ->nullLabel(__('filament-short-url::default.max_visits_no_limit'))
                                                    ->suffix(__('filament-short-url::default.max_visits_suffix'))
                                                    ->variant('outline')
                                                    ->size('md')
                                                    ->disabled(fn (Get $get): bool => (bool) $get('single_use'))
                                                    ->extraFieldWrapperAttributes(['class' => 'validity-stepper-wrap']),

                                                Placeholder::make('max_visits_lock_overlay')
                                                    ->hiddenLabel()
                                                    ->visible(fn (Get $get): bool => (bool) $get('single_use'))
                                                    ->content(new HtmlString(
                                                        '<div class="validity-max-visits-lock" role="presentation">'.
                                                        '<span class="sr-only">'.e(__('filament-short-url::default.validity_max_visits_locked')).'</span>'.
                                                        '<div class="validity-max-visits-lock-icon">'.
                                                        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-7"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" /></svg>'.
                                                        '</div>'.
                                                        '</div>'
                                                    )),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
