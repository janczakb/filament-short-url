<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class GeneralTab
{
    /**
     * Build the general settings form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_general'))
            ->key('general')
            ->icon('heroicon-o-link')
            ->schema([
                Section::make(__('filament-short-url::default.settings_section_routing'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')
                            ->label(__('filament-short-url::default.settings_site_name'))
                            ->helperText(__('filament-short-url::default.settings_site_name_helper'))
                            ->nullable()
                            ->maxLength(100)
                            ->columnSpanFull(),

                        TextInput::make('route_prefix')
                            ->label(__('filament-short-url::default.settings_route_prefix'))
                            ->helperText(__('filament-short-url::default.settings_route_prefix_helper'))
                            ->prefix(new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 6px; margin-top: -3px; width: 15px; height: 15px;" class="text-emerald-600 dark:text-emerald-500"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v7a2 2 0 00 2 2h10a2 2 0 00 2-2v-7a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 00 10 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" /></svg>https://'.request()->getHost().'/'))
                            ->nullable()
                            ->alphaDash()
                            ->maxLength(20),

                        Select::make('redirect_status_code')
                            ->label(__('filament-short-url::default.redirect_code'))
                            ->helperText(__('filament-short-url::default.settings_redirect_code_helper'))
                            ->options([
                                302 => __('filament-short-url::default.redirect_code_302'),
                                301 => __('filament-short-url::default.redirect_code_301'),
                            ])
                            ->required(),

                        Toggle::make('lock_url_key')
                            ->label(__('filament-short-url::default.settings_lock_url_key'))
                            ->helperText(__('filament-short-url::default.settings_lock_url_key_helper'))
                            ->inline(false),

                        Toggle::make('disable_default_domain')
                            ->label(__('filament-short-url::default.settings_disable_default_domain'))
                            ->helperText(__('filament-short-url::default.settings_disable_default_domain_helper'))
                            ->inline(false),

                        TextInput::make('key_length')
                            ->label(__('filament-short-url::default.settings_key_length'))
                            ->helperText(__('filament-short-url::default.settings_key_length_helper'))
                            ->rules(['required', 'integer', 'between:3,20'])
                            ->extraInputAttributes([
                                'maxlength' => 2,
                                'oninput' => "this.value = this.value.replace(/[^0-9]/g, ''); if(this.value !== '') { let val = parseInt(this.value); if(val > 20) this.value = 20; }",
                                'onblur' => "if(this.value !== '') { let val = parseInt(this.value); if(val < 3) this.value = 3; if(val > 20) this.value = 20; }",
                            ])
                            ->required(),

                        TextInput::make('cache_ttl')
                            ->label(__('filament-short-url::default.settings_cache_ttl'))
                            ->helperText(__('filament-short-url::default.settings_cache_ttl_helper'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->suffix('s')
                            ->required(),

                        Toggle::make('trust_cdn_headers')
                            ->label(__('filament-short-url::default.settings_trust_cdn_headers'))
                            ->helperText(__('filament-short-url::default.settings_trust_cdn_headers_helper'))
                            ->columnSpanFull()
                            ->inline(false)
                            ->live(),

                        Placeholder::make('trust_cdn_headers_info')
                            ->content(function () {
                                $html = __('filament-short-url::default.settings_trust_cdn_headers_info_callout');

                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get): bool => (bool) $get('trust_cdn_headers'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-short-url::default.settings_section_queue'))
                    ->columns(2)
                    ->schema([
                        Select::make('queue_connection')
                            ->label(__('filament-short-url::default.settings_queue_connection'))
                            ->helperText(__('filament-short-url::default.settings_queue_connection_helper'))
                            ->options(function (): array {
                                $connections = array_keys(config('queue.connections', []));

                                return array_combine($connections, $connections) ?: [
                                    'sync' => 'sync',
                                    'database' => 'database',
                                    'redis' => 'redis',
                                ];
                            })
                            ->required()
                            ->live(),

                        TextInput::make('queue_name')
                            ->label(__('filament-short-url::default.settings_queue_name'))
                            ->helperText(__('filament-short-url::default.settings_queue_name_helper'))
                            ->default('default')
                            ->required(fn (Get $get): bool => $get('queue_connection') !== 'sync')
                            ->visible(fn (Get $get): bool => $get('queue_connection') !== 'sync'),

                        Placeholder::make('queue_worker_info')
                            ->content(function (Get $get) {
                                $queueName = $get('queue_name') ?: 'default';
                                $html = __('filament-short-url::default.settings_queue_worker_info', ['queue' => $queueName]);

                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get): bool => $get('queue_connection') !== 'sync')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-short-url::default.settings_section_buffering'))
                    ->schema([
                        Toggle::make('counter_buffering_enabled')
                            ->label(__('filament-short-url::default.settings_buffering_enabled'))
                            ->helperText(__('filament-short-url::default.settings_buffering_helper'))
                            ->inline(false)
                            ->live(),

                        Placeholder::make('counter_buffering_info')
                            ->content(function () {
                                $html = __('filament-short-url::default.settings_buffering_worker_info');

                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get): bool => (bool) $get('counter_buffering_enabled'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
