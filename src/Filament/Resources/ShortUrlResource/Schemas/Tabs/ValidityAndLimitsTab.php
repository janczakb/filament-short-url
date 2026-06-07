<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

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
                    ->description('Zarządzaj dostępnością linku w czasie. Możesz zaplanować start kampanii lub jej automatyczne zakończenie.')
                    ->icon('heroicon-o-calendar-days')
                    ->aside()
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
                            }),

                        Group::make([
                            DateTimePicker::make('activated_at')
                                ->label(__('filament-short-url::default.activated_at'))
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
                                ->prefixIcon('heroicon-m-stop-circle')
                                ->nullable()
                                ->native(false)
                                ->withoutSeconds()
                                ->live(onBlur: true)
                                ->minDate(fn (Get $get) => $get('activated_at') ?: now()->startOfDay()),

                            TextInput::make('expiration_redirect_url')
                                ->label(__('filament-short-url::default.expiration_redirect_url'))
                                ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.expiration_redirect_url_helper'))
                                ->url()
                                ->maxLength(2048)
                                ->nullable()
                                ->columnSpanFull(),
                        ])
                            ->columns(2)
                            ->visible(fn (Get $get): bool => (bool) $get('use_date_validity')),
                    ]),

                Section::make(__('filament-short-url::default.visit_limits_section_title'))
                    ->description('Ogranicz maksymalną liczbę kliknięć. Idealne do biletów jednorazowych lub ofert limitowanych.')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->aside()
                    ->schema([
                        Toggle::make('single_use')
                            ->label(__('filament-short-url::default.single_use'))
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.single_use_helper'))
                            ->default(false)
                            ->live(),

                        TextInput::make('max_visits')
                            ->label(__('filament-short-url::default.max_visits'))
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.max_visits_helper'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->nullable()
                            ->hidden(fn (Get $get): bool => (bool) $get('single_use')),
                    ]),
            ]);
    }
}
