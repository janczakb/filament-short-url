<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class DeveloperTab
{
    /**
     * Build the developer API and webhooks settings form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_developer'))
            ->key('developer')
            ->icon('heroicon-o-cpu-chip')
            ->schema([
                Section::make(__('filament-short-url::default.settings_section_rest_api'))
                    ->schema([
                        Toggle::make('api_enabled')
                            ->label(__('filament-short-url::default.settings_api_enabled'))
                            ->helperText(__('filament-short-url::default.settings_api_enabled_helper'))
                            ->default(false)
                            ->inline(false)
                            ->columnSpanFull()
                            ->live(),
                    ]),

                Section::make(__('filament-short-url::default.settings_section_api_keys'))
                    ->description(__('filament-short-url::default.settings_api_keys_description'))
                    ->visible(fn (Get $get): bool => (bool) $get('api_enabled'))
                    ->schema([
                        Repeater::make('api_keys')
                            ->label(__('filament-short-url::default.settings_api_keys'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-short-url::default.api_key_name'))
                                    ->required(),
                                TextInput::make('key')
                                    ->label(__('filament-short-url::default.api_key'))
                                    ->disabled()
                                    ->dehydrated()
                                    ->copyable(copyMessage: 'Copied!', copyMessageDuration: 1500)
                                    ->default(fn () => 'sh_key_'.bin2hex(random_bytes(16))),
                                Select::make('scope')
                                    ->label(__('filament-short-url::default.api_key_scope'))
                                    ->options([
                                        'links:read-write' => __('filament-short-url::default.api_key_scope_read_write'),
                                        'links:read-only' => __('filament-short-url::default.api_key_scope_read_only'),
                                    ])
                                    ->default('links:read-write')
                                    ->required(),
                                Select::make('rate_limit')
                                    ->label(__('filament-short-url::default.api_key_rate_limit'))
                                    ->options([
                                        '60' => __('filament-short-url::default.api_key_rate_limit_rpm', ['count' => 60]),
                                        '120' => __('filament-short-url::default.api_key_rate_limit_rpm', ['count' => 120]),
                                        '300' => __('filament-short-url::default.api_key_rate_limit_rpm', ['count' => 300]),
                                        '0' => __('filament-short-url::default.api_key_rate_limit_unlimited'),
                                    ])
                                    ->default('60')
                                    ->required(),
                                Toggle::make('is_active')
                                    ->label(__('filament-short-url::default.active'))
                                    ->default(true),
                                TextInput::make('owner_user_id')
                                    ->label(__('filament-short-url::default.api_key_owner_user_id'))
                                    ->numeric()
                                    ->nullable(),
                            ])
                            ->columns(6)
                            ->default([]),
                    ]),

                Section::make(__('filament-short-url::default.settings_section_global_webhook'))
                    ->schema([
                        Toggle::make('global_webhook_enabled')
                            ->label(__('filament-short-url::default.settings_global_webhook_enabled'))
                            ->helperText(__('filament-short-url::default.settings_global_webhook_enabled_helper'))
                            ->live()
                            ->inline(false)
                            ->afterStateUpdated(function (bool $state, $set) {
                                if (! $state) {
                                    $set('global_webhook_url', null);
                                    $set('webhook_events', ['visited']);
                                    $set('webhook_signing_secret', null);
                                }
                            }),

                        TextInput::make('global_webhook_url')
                            ->label(__('filament-short-url::default.settings_global_webhook_url'))
                            ->helperText(__('filament-short-url::default.settings_global_webhook_url_helper'))
                            ->url()
                            ->nullable()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('global_webhook_enabled')),

                        TextInput::make('webhook_signing_secret')
                            ->label(__('filament-short-url::default.webhook_signing_secret'))
                            ->helperText(__('filament-short-url::default.webhook_signing_secret_helper'))
                            ->password()
                            ->revealable()
                            ->placeholder('••••••••••••••••••••')
                            ->required(fn (Get $get): bool => (bool) $get('global_webhook_enabled'))
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('global_webhook_enabled')),

                        Select::make('webhook_events')
                            ->label(__('filament-short-url::default.settings_webhook_events'))
                            ->helperText(__('filament-short-url::default.settings_webhook_events_helper'))
                            ->multiple()
                            ->options([
                                'visited' => __('filament-short-url::default.webhook_event_visited'),
                                'created' => __('filament-short-url::default.webhook_event_created'),
                                'expired' => __('filament-short-url::default.webhook_event_expired'),
                                'limit_reached' => __('filament-short-url::default.webhook_event_limit_reached'),
                            ])
                            ->default(['visited'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => (bool) $get('global_webhook_enabled')),
                    ]),
            ]);
    }
}
