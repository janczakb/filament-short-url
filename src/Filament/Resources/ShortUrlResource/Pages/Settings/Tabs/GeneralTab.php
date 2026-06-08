<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentShortUrl\Services\Queue\PluginQueueWorkerTester;
use Bjanczak\FilamentShortUrl\Services\Redis\PluginRedisConnectionTester;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class GeneralTab
{
    private static function queueConnectionRequiresWorker(string $connection): bool
    {
        return ! in_array($connection, ['sync', 'deferred', 'background'], true);
    }

    private static function queueModeDescription(string $connection): string
    {
        return match ($connection) {
            'redis' => __('filament-short-url::default.settings_queue_mode_desc_redis'),
            'database' => __('filament-short-url::default.settings_queue_mode_desc_database'),
            'sqs' => __('filament-short-url::default.settings_queue_mode_desc_sqs'),
            'beanstalkd' => __('filament-short-url::default.settings_queue_mode_desc_beanstalkd'),
            'deferred' => __('filament-short-url::default.settings_queue_mode_desc_deferred'),
            'background' => __('filament-short-url::default.settings_queue_mode_desc_background'),
            'failover' => __('filament-short-url::default.settings_queue_mode_desc_failover'),
            default => __('filament-short-url::default.settings_queue_mode_desc_default', ['connection' => e($connection)]),
        };
    }

    private static function queueCalloutHtml(string $content, string $variant = 'neutral'): string
    {
        $classes = $variant === 'emerald'
            ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30'
            : 'border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-white/10';

        return '<div class="callout my-4 px-5 py-4 overflow-hidden rounded-2xl flex gap-3 border '.$classes.'" data-callout-type="info">'
            .'<div class="text-sm text-neutral-800 dark:text-neutral-300">'.$content.'</div></div>';
    }

    private static function renderQueueConnectionCallout(Get $get): HtmlString
    {
        $connection = (string) ($get('queue_connection') ?: 'sync');

        if ($connection === 'sync') {
            return new HtmlString('');
        }

        $queueName = (string) ($get('queue_name') ?: 'default');
        $content = self::queueModeDescription($connection);

        if (self::queueConnectionRequiresWorker($connection)) {
            $content .= '<br><br>'.__('filament-short-url::default.settings_queue_worker_command', [
                'connection' => e($connection),
                'queue' => e($queueName),
            ]);
        }

        return new HtmlString(self::queueCalloutHtml($content, $connection === 'redis' ? 'emerald' : 'neutral'));
    }

    /**
     * @return array<string, mixed>
     */
    private static function queuePreviewSettings(Get $get): array
    {
        return [
            'queue_connection' => $get('queue_connection') ?: 'sync',
            'queue_name' => $get('queue_name') ?: 'default',
            'redis_host' => $get('redis_host'),
            'redis_port' => $get('redis_port'),
            'redis_password' => $get('redis_password'),
            'redis_database' => $get('redis_database'),
            'redis_key_prefix' => $get('redis_key_prefix'),
        ];
    }

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

                        SegmentControl::make('redirect_status_code')
                            ->label(__('filament-short-url::default.redirect_code'))
                            ->helperText(__('filament-short-url::default.settings_redirect_code_helper'))
                            ->options([
                                302 => __('filament-short-url::default.redirect_code_302'),
                                301 => __('filament-short-url::default.redirect_code_301'),
                            ])
                            ->size('sm')
                            ->separators(false)
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
                            ->live()
                            ->disabled(fn (Get $get): bool => $get('geo_ip_driver') === 'headers')
                            ->dehydrated(fn (Get $get): bool => $get('geo_ip_driver') !== 'headers'),

                        Placeholder::make('trust_cdn_headers_auto_enabled')
                            ->content(fn () => new HtmlString(__('filament-short-url::default.settings_trust_cdn_headers_auto_enabled')))
                            ->visible(fn (Get $get): bool => $get('geo_ip_driver') === 'headers')
                            ->columnSpanFull(),

                        Placeholder::make('trust_cdn_headers_info')
                            ->content(function () {
                                $html = __('filament-short-url::default.settings_trust_cdn_headers_info_callout');

                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get): bool => (bool) $get('trust_cdn_headers')
                                && $get('geo_ip_driver') !== 'headers')
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
                            ->visible(fn (Get $get): bool => $get('queue_connection') !== 'sync')
                            ->live(),

                        Placeholder::make('queue_mode_info')
                            ->hiddenLabel()
                            ->content(fn (Get $get): HtmlString => self::renderQueueConnectionCallout($get))
                            ->visible(fn (Get $get): bool => ($get('queue_connection') ?: 'sync') !== 'sync')
                            ->columnSpanFull(),

                        Section::make(__('filament-short-url::default.settings_section_redis'))
                            ->description(__('filament-short-url::default.settings_section_redis_description'))
                            ->columns(2)
                            ->visible(fn (Get $get): bool => $get('queue_connection') === 'redis')
                            ->schema([
                                TextInput::make('redis_host')
                                    ->label(__('filament-short-url::default.settings_redis_host'))
                                    ->helperText(__('filament-short-url::default.settings_redis_host_helper'))
                                    ->required(fn (Get $get): bool => $get('queue_connection') === 'redis')
                                    ->maxLength(255),

                                TextInput::make('redis_port')
                                    ->label(__('filament-short-url::default.settings_redis_port'))
                                    ->helperText(__('filament-short-url::default.settings_redis_port_helper'))
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->required(fn (Get $get): bool => $get('queue_connection') === 'redis'),

                                TextInput::make('redis_password')
                                    ->label(__('filament-short-url::default.settings_redis_password'))
                                    ->helperText(__('filament-short-url::default.settings_redis_password_helper'))
                                    ->password()
                                    ->revealable()
                                    ->placeholder('••••••••••••••••••••'),

                                TextInput::make('redis_database')
                                    ->label(__('filament-short-url::default.settings_redis_database'))
                                    ->helperText(__('filament-short-url::default.settings_redis_database_helper'))
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(15)
                                    ->required(fn (Get $get): bool => $get('queue_connection') === 'redis'),

                                TextInput::make('redis_key_prefix')
                                    ->label(__('filament-short-url::default.settings_redis_key_prefix'))
                                    ->helperText(__('filament-short-url::default.settings_redis_key_prefix_helper'))
                                    ->maxLength(100)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),

                        Actions::make([
                            Action::make('testRedisConnection')
                                ->label(__('filament-short-url::default.settings_redis_test'))
                                ->icon('heroicon-o-signal')
                                ->color('gray')
                                ->visible(fn (Get $get): bool => $get('queue_connection') === 'redis')
                                ->action(function (Get $get): void {
                                    $result = app(PluginRedisConnectionTester::class)->test(
                                        queueConnectionName: 'redis',
                                        queueName: $get('queue_name') ?: 'default',
                                        previewSettings: self::queuePreviewSettings($get),
                                    );

                                    $notification = Notification::make()
                                        ->title($result['ok']
                                            ? __('filament-short-url::default.settings_redis_test_ok')
                                            : __('filament-short-url::default.settings_redis_test_fail'))
                                        ->body($result['message']);

                                    if ($result['ok']) {
                                        $notification->success();
                                    } else {
                                        $notification->danger();
                                    }

                                    $notification->send();
                                }),

                            Action::make('testQueueWorker')
                                ->label(__('filament-short-url::default.settings_queue_worker_test'))
                                ->icon('heroicon-o-play')
                                ->color('gray')
                                ->visible(fn (Get $get): bool => ($get('queue_connection') ?: 'sync') !== 'sync')
                                ->action(function (Get $get): void {
                                    $result = app(PluginQueueWorkerTester::class)->test(self::queuePreviewSettings($get));

                                    $notification = Notification::make()
                                        ->title($result['ok']
                                            ? __('filament-short-url::default.settings_queue_worker_test_ok')
                                            : __('filament-short-url::default.settings_queue_worker_test_fail'))
                                        ->body($result['message']);

                                    if ($result['ok']) {
                                        $notification->success();
                                    } else {
                                        $notification->warning();
                                    }

                                    $notification->send();
                                }),
                        ])
                            ->visible(fn (Get $get): bool => ($get('queue_connection') ?: 'sync') !== 'sync')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-short-url::default.settings_section_buffering'))
                    ->schema([
                        Toggle::make('counter_buffering_enabled')
                            ->label(__('filament-short-url::default.settings_buffering_enabled'))
                            ->helperText(fn (Get $get): string => ($get('queue_connection') === 'redis')
                                ? __('filament-short-url::default.settings_buffering_redis_auto')
                                : __('filament-short-url::default.settings_buffering_helper'))
                            ->disabled(fn (Get $get): bool => $get('queue_connection') === 'redis')
                            ->dehydrated()
                            ->inline(false)
                            ->live(),

                        Placeholder::make('counter_buffering_info')
                            ->content(function () {
                                $html = __('filament-short-url::default.settings_buffering_worker_info');

                                return new HtmlString($html);
                            })
                            ->visible(fn (Get $get): bool => $get('queue_connection') === 'redis' || (bool) $get('counter_buffering_enabled'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
