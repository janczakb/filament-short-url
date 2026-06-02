<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class ShortUrlSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ShortUrlResource::class;

    protected string $view = 'filament-short-url::settings';

    public ?array $data = [];

    public function mount(): void
    {
        $mgr = app(ShortUrlSettingsManager::class);

        $this->form->fill([
            'route_prefix' => $mgr->get('route_prefix', 's'),
            'redirect_status_code' => $mgr->get('redirect_status_code', 302),
            'key_length' => $mgr->get('key_length', 6),
            'cache_ttl' => $mgr->get('cache_ttl', 3600),
            'geo_ip_enabled' => $mgr->get('geo_ip_enabled', true),
            'geo_ip_driver' => $mgr->get('geo_ip_driver', 'headers'),
            'geo_ip_cache_ttl' => $mgr->get('geo_ip_cache_ttl', 86400),
            'geo_ip_timeout' => $mgr->get('geo_ip_timeout', 3),
            'maxmind_database_path' => $mgr->get('maxmind_database_path', storage_path('geoip/GeoLite2-Country.mmdb')),
            'geo_ip_stats_cache_ttl' => $mgr->get('geo_ip_stats_cache_ttl', 300),
            'queue_connection' => $mgr->get('queue_connection', 'sync'),
            'queue_name' => $mgr->get('queue_name', 'default'),
            'ga4_api_secret' => $mgr->get('ga4_api_secret'),
            'ga4_firebase_app_id' => $mgr->get('ga4_firebase_app_id'),
            'counter_buffering_enabled' => $mgr->get('counter_buffering_enabled', false),
            'trust_cdn_headers' => $mgr->get('trust_cdn_headers', false),
            'pruning_enabled' => $mgr->get('pruning_enabled', true),
            'pruning_retention_days' => $mgr->get('pruning_retention_days', 90),
            'rate_limiting_enabled' => $mgr->get('rate_limiting_enabled', false),
            'rate_limiting_max_attempts' => $mgr->get('rate_limiting_max_attempts', 60),
            'rate_limiting_decay_seconds' => $mgr->get('rate_limiting_decay_seconds', 60),
            'tracking_enabled' => $mgr->get('tracking_enabled', true),
            'tracking_fields_ip_address' => $mgr->get('tracking_fields_ip_address', true),
            'tracking_fields_browser' => $mgr->get('tracking_fields_browser', true),
            'tracking_fields_browser_version' => $mgr->get('tracking_fields_browser_version', true),
            'tracking_fields_operating_system' => $mgr->get('tracking_fields_operating_system', true),
            'tracking_fields_operating_system_version' => $mgr->get('tracking_fields_operating_system_version', true),
            'tracking_fields_referer_url' => $mgr->get('tracking_fields_referer_url', true),
            'tracking_fields_device_type' => $mgr->get('tracking_fields_device_type', true),
            'qr_size' => $mgr->get('qr_size', 300),
            'qr_margin' => $mgr->get('qr_margin', 1),
            'qr_dot_style' => $mgr->get('qr_dot_style', 'square'),
            'qr_foreground_color' => $mgr->get('qr_foreground_color', '#000000'),
            'qr_background_color' => $mgr->get('qr_background_color', '#ffffff'),
            'qr_gradient_enabled' => $mgr->get('qr_gradient_enabled', false),
            'qr_gradient_from' => $mgr->get('qr_gradient_from', '#4f46e5'),
            'qr_gradient_to' => $mgr->get('qr_gradient_to', '#06b6d4'),
            'qr_gradient_type' => $mgr->get('qr_gradient_type', 'linear'),
            'global_webhook_url' => $mgr->get('global_webhook_url'),
            'webhook_events' => $mgr->get('webhook_events', ['visited']),
            'api_keys' => $mgr->get('api_keys', []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ShortUrlSettings')
                    ->persistTabInQueryString()
                    ->tabs([

                        // ── General ──────────────────────────────────────────
                        Tab::make(__('filament-short-url::default.settings_tab_general'))
                            ->icon('heroicon-o-link')
                            ->schema([
                                Section::make(__('filament-short-url::default.settings_section_routing'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('route_prefix')
                                            ->label(__('filament-short-url::default.settings_route_prefix'))
                                            ->helperText(__('filament-short-url::default.settings_route_prefix_helper'))
                                            ->required()
                                            ->alphaDash()
                                            ->maxLength(20),

                                        Select::make('redirect_status_code')
                                            ->label(__('filament-short-url::default.redirect_code'))
                                            ->options([
                                                302 => __('filament-short-url::default.redirect_code_302'),
                                                301 => __('filament-short-url::default.redirect_code_301'),
                                            ])
                                            ->required(),

                                        TextInput::make('key_length')
                                            ->label(__('filament-short-url::default.settings_key_length'))
                                            ->helperText(__('filament-short-url::default.settings_key_length_helper'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(4)
                                            ->maxValue(20)
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
                                            ->inline(false),
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
                                            ->required(),

                                        TextInput::make('queue_name')
                                            ->label(__('filament-short-url::default.settings_queue_name'))
                                            ->helperText(__('filament-short-url::default.settings_queue_name_helper'))
                                            ->required(),
                                    ]),

                                Section::make(__('filament-short-url::default.settings_section_buffering'))
                                    ->schema([
                                        Toggle::make('counter_buffering_enabled')
                                            ->label(__('filament-short-url::default.settings_buffering_enabled'))
                                            ->helperText(__('filament-short-url::default.settings_buffering_helper'))
                                            ->inline(false),
                                    ]),
                            ]),

                        // ── Geo-IP ───────────────────────────────────────────
                        Tab::make(__('filament-short-url::default.settings_tab_geoip'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make(__('filament-short-url::default.settings_section_geoip'))
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('geo_ip_enabled')
                                            ->label(__('filament-short-url::default.settings_geoip_enabled'))
                                            ->helperText(__('filament-short-url::default.settings_geoip_enabled_helper'))
                                            ->columnSpanFull()
                                            ->inline(false)
                                            ->live(),

                                        // ── Driver (only when geo-ip is on) ──
                                        Select::make('geo_ip_driver')
                                            ->label(__('filament-short-url::default.settings_geoip_driver'))
                                            ->helperText(__('filament-short-url::default.settings_geoip_driver_helper'))
                                            ->options([
                                                'headers' => __('filament-short-url::default.settings_geoip_driver_headers'),
                                                'maxmind' => __('filament-short-url::default.settings_geoip_driver_maxmind'),
                                                'ip-api' => __('filament-short-url::default.settings_geoip_driver_ipapi'),
                                            ])
                                            ->required()
                                            ->live()
                                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled')),

                                        // ── Cache TTL (only when geo-ip is on) ──
                                        TextInput::make('geo_ip_cache_ttl')
                                            ->label(__('filament-short-url::default.settings_geoip_cache_ttl'))
                                            ->helperText(__('filament-short-url::default.settings_geoip_cache_ttl_helper'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->suffix('s')
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled')),

                                        // ── Stats Cache TTL (only when geo-ip is on) ──
                                        TextInput::make('geo_ip_stats_cache_ttl')
                                            ->label(__('filament-short-url::default.settings_geoip_stats_cache_ttl'))
                                            ->helperText(__('filament-short-url::default.settings_geoip_stats_cache_ttl_helper'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->suffix('s')
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled')),

                                        // ── Timeout (only for ip-api driver) ──
                                        TextInput::make('geo_ip_timeout')
                                            ->label(__('filament-short-url::default.settings_geoip_timeout'))
                                            ->helperText(__('filament-short-url::default.settings_geoip_timeout_helper'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->maxValue(30)
                                            ->suffix('s')
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled') && $get('geo_ip_driver') === 'ip-api'),

                                        // ── MaxMind path (only for maxmind driver) ──
                                        TextInput::make('maxmind_database_path')
                                            ->label(__('filament-short-url::default.settings_maxmind_path'))
                                            ->helperText(__('filament-short-url::default.settings_maxmind_path_helper'))
                                            ->columnSpanFull()
                                            ->placeholder('/var/www/html/database/geoip/GeoLite2-Country.mmdb')
                                            ->suffixAction(
                                                Action::make('verifyMaxmindPath')
                                                    ->label(__('filament-short-url::default.settings_maxmind_verify'))
                                                    ->icon('heroicon-o-server')
                                                    ->action(function (?string $state): void {
                                                        $path = trim($state ?? '');

                                                        if (empty($path)) {
                                                            Notification::make()
                                                                ->title(__('filament-short-url::default.settings_maxmind_verify_empty'))
                                                                ->warning()
                                                                ->send();

                                                            return;
                                                        }

                                                        if (file_exists($path) && is_readable($path) && str_ends_with($path, '.mmdb')) {
                                                            $sizeKb = round(filesize($path) / 1024);
                                                            Notification::make()
                                                                ->title(__('filament-short-url::default.settings_maxmind_verify_ok'))
                                                                ->body("{$path} ({$sizeKb} KB)")
                                                                ->success()
                                                                ->send();
                                                        } else {
                                                            Notification::make()
                                                                ->title(__('filament-short-url::default.settings_maxmind_verify_fail'))
                                                                ->body($path)
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    })
                                            )
                                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled') && $get('geo_ip_driver') === 'maxmind'),
                                    ]),
                            ]),

                        // ── Google Analytics 4 ───────────────────────────────
                        Tab::make(__('filament-short-url::default.settings_tab_ga4'))
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make(__('filament-short-url::default.settings_section_ga4'))
                                    ->description(__('filament-short-url::default.settings_ga4_description'))
                                    ->schema([
                                        TextInput::make('ga4_api_secret')
                                            ->label(__('filament-short-url::default.settings_ga4_api_secret'))
                                            ->helperText(__('filament-short-url::default.settings_ga4_api_secret_helper'))
                                            ->password()
                                            ->revealable()
                                            ->placeholder('••••••••••••••••••••'),

                                        // Firebase App ID — always visible
                                        TextInput::make('ga4_firebase_app_id')
                                            ->label(__('filament-short-url::default.settings_ga4_firebase_app_id'))
                                            ->helperText(__('filament-short-url::default.settings_ga4_firebase_app_id_helper'))
                                            ->placeholder('1:1234567890:android:abcdef123456'),

                                        // GA4 connection verify button
                                        Actions::make([
                                            Action::make('verifyGa4ApiSecret')
                                                ->label(__('filament-short-url::default.settings_ga4_verify'))
                                                ->icon('heroicon-o-signal')
                                                ->color('gray')
                                                ->action(function (Get $get): void {
                                                    $secret = trim($get('ga4_api_secret') ?? '');

                                                    if (empty($secret)) {
                                                        Notification::make()
                                                            ->title(__('filament-short-url::default.settings_ga4_verify_empty'))
                                                            ->warning()
                                                            ->send();

                                                        return;
                                                    }

                                                    try {
                                                        // GA4 debug endpoint — a valid api_secret returns event-level
                                                        // validation messages; an invalid one returns auth errors.
                                                        $response = Http::timeout(5)
                                                            ->withHeaders(['Content-Type' => 'application/json'])
                                                            ->post(
                                                                'https://www.google-analytics.com/debug/mp/collect?measurement_id=G-XXXXXXXXXX&api_secret='.urlencode($secret),
                                                                [
                                                                    'client_id' => 'short-url-plugin-verify',
                                                                    'events' => [
                                                                        ['name' => 'page_view', 'params' => []],
                                                                    ],
                                                                ]
                                                            );

                                                        $body = $response->json();
                                                        $messages = $body['validationMessages'] ?? [];

                                                        // A valid secret returns event-level messages, not auth errors
                                                        $hasAuthError = collect($messages)->contains(fn ($m) => str_contains(
                                                            strtolower($m['description'] ?? ''),
                                                            'api_secret'
                                                        ));

                                                        if ($hasAuthError || $response->status() === 401) {
                                                            Notification::make()
                                                                ->title(__('filament-short-url::default.settings_ga4_verify_fail'))
                                                                ->danger()
                                                                ->send();
                                                        } else {
                                                            Notification::make()
                                                                ->title(__('filament-short-url::default.settings_ga4_verify_ok'))
                                                                ->success()
                                                                ->send();
                                                        }
                                                    } catch (\Throwable $e) {
                                                        Notification::make()
                                                            ->title(__('filament-short-url::default.settings_ga4_verify_error'))
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                }),
                                        ]),
                                    ]),
                            ]),

                        // ── Advanced & Security ──────────────────────────────
                        Tab::make(__('filament-short-url::default.settings_tab_advanced'))
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Section::make(__('filament-short-url::default.settings_section_aggregation'))
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('pruning_enabled')
                                            ->label(__('filament-short-url::default.settings_aggregation_enabled'))
                                            ->helperText(__('filament-short-url::default.settings_aggregation_enabled_helper'))
                                            ->default(true)
                                            ->live()
                                            ->inline(false),

                                        Select::make('pruning_retention_days')
                                            ->label(__('filament-short-url::default.settings_retention_days'))
                                            ->helperText(__('filament-short-url::default.settings_retention_days_helper'))
                                            ->options([
                                                30 => __('filament-short-url::default.retention_30_days'),
                                                60 => __('filament-short-url::default.retention_60_days'),
                                                90 => __('filament-short-url::default.retention_90_days'),
                                                180 => __('filament-short-url::default.retention_180_days'),
                                                365 => __('filament-short-url::default.retention_365_days'),
                                                730 => __('filament-short-url::default.retention_730_days'),
                                            ])
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('pruning_enabled')),
                                    ]),

                                Section::make(__('filament-short-url::default.settings_section_rate_limiting'))
                                    ->columns(3)
                                    ->schema([
                                        Toggle::make('rate_limiting_enabled')
                                            ->label(__('filament-short-url::default.settings_rate_limiting_enabled'))
                                            ->helperText(__('filament-short-url::default.settings_rate_limiting_enabled_helper'))
                                            ->default(false)
                                            ->live()
                                            ->inline(false),

                                        TextInput::make('rate_limiting_max_attempts')
                                            ->label(__('filament-short-url::default.settings_rate_limiting_max_attempts'))
                                            ->helperText(__('filament-short-url::default.settings_rate_limiting_max_attempts_helper'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('rate_limiting_enabled')),

                                        TextInput::make('rate_limiting_decay_seconds')
                                            ->label(__('filament-short-url::default.settings_rate_limiting_decay_seconds'))
                                            ->helperText(__('filament-short-url::default.settings_rate_limiting_decay_seconds_helper'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(1)
                                            ->suffix('s')
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('rate_limiting_enabled')),
                                    ]),
                            ]),

                        // ── Tracking Defaults ─────────────────────────────────
                        Tab::make(__('filament-short-url::default.settings_tab_tracking_defaults'))
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make(__('filament-short-url::default.settings_section_tracking_defaults'))
                                    ->description(__('filament-short-url::default.settings_section_tracking_defaults_helper'))
                                    ->schema([
                                        Toggle::make('tracking_enabled')
                                            ->label(__('filament-short-url::default.settings_track_visits_default'))
                                            ->live()
                                            ->inline(false)
                                            ->columnSpanFull(),

                                        Toggle::make('tracking_fields_ip_address')
                                            ->label(__('filament-short-url::default.settings_track_ip_default'))
                                            ->inline(false)
                                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                                        Toggle::make('tracking_fields_browser')
                                            ->label(__('filament-short-url::default.settings_track_browser_default'))
                                            ->inline(false)
                                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                                        Toggle::make('tracking_fields_browser_version')
                                            ->label(__('filament-short-url::default.settings_track_browser_version_default'))
                                            ->inline(false)
                                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                                        Toggle::make('tracking_fields_operating_system')
                                            ->label(__('filament-short-url::default.settings_track_os_default'))
                                            ->inline(false)
                                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                                        Toggle::make('tracking_fields_operating_system_version')
                                            ->label(__('filament-short-url::default.settings_track_os_version_default'))
                                            ->inline(false)
                                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                                        Toggle::make('tracking_fields_referer_url')
                                            ->label(__('filament-short-url::default.settings_track_referer_default'))
                                            ->inline(false)
                                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),

                                        Toggle::make('tracking_fields_device_type')
                                            ->label(__('filament-short-url::default.settings_track_device_type_default'))
                                            ->inline(false)
                                            ->disabled(fn (Get $get): bool => ! $get('tracking_enabled')),
                                    ])
                                    ->columns(4),
                            ]),

                        // ── QR Defaults ──────────────────────────────────────
                        Tab::make(__('filament-short-url::default.settings_tab_qr'))
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                Section::make(__('filament-short-url::default.settings_section_qr_defaults'))
                                    ->description(__('filament-short-url::default.settings_section_qr_defaults_helper'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('qr_size')
                                            ->label(__('filament-short-url::default.settings_qr_size'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(100)
                                            ->maxValue(2000)
                                            ->required(),

                                        Select::make('qr_margin')
                                            ->label(__('filament-short-url::default.settings_qr_margin'))
                                            ->options(array_combine(range(0, 10), range(0, 10)))
                                            ->required(),

                                        Select::make('qr_dot_style')
                                            ->label(__('filament-short-url::default.settings_qr_dot_style'))
                                            ->options([
                                                'square' => __('filament-short-url::default.qr_option_square'),
                                                'dots' => __('filament-short-url::default.qr_option_dots'),
                                                'rounded' => __('filament-short-url::default.qr_option_rounded'),
                                                'classy' => __('filament-short-url::default.qr_option_classy'),
                                                'classy-rounded' => __('filament-short-url::default.qr_option_classy_rounded'),
                                                'extra-rounded' => __('filament-short-url::default.qr_option_extra_rounded'),
                                            ])
                                            ->required(),

                                        ColorPicker::make('qr_foreground_color')
                                            ->label(__('filament-short-url::default.settings_qr_foreground_color'))
                                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                                            ->placeholder('#000000')
                                            ->required(),

                                        ColorPicker::make('qr_background_color')
                                            ->label(__('filament-short-url::default.settings_qr_background_color'))
                                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                                            ->placeholder('#ffffff')
                                            ->required(),

                                        Toggle::make('qr_gradient_enabled')
                                            ->label(__('filament-short-url::default.settings_qr_gradient_enabled'))
                                            ->live()
                                            ->inline(false),

                                        ColorPicker::make('qr_gradient_from')
                                            ->label(__('filament-short-url::default.settings_qr_gradient_from'))
                                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                                            ->placeholder('#4f46e5')
                                            ->required(fn (Get $get): bool => (bool) $get('qr_gradient_enabled'))
                                            ->visible(fn (Get $get): bool => (bool) $get('qr_gradient_enabled')),

                                        ColorPicker::make('qr_gradient_to')
                                            ->label(__('filament-short-url::default.settings_qr_gradient_to'))
                                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/')
                                            ->placeholder('#06b6d4')
                                            ->required(fn (Get $get): bool => (bool) $get('qr_gradient_enabled'))
                                            ->visible(fn (Get $get): bool => (bool) $get('qr_gradient_enabled')),

                                        Select::make('qr_gradient_type')
                                            ->label(__('filament-short-url::default.settings_qr_gradient_type'))
                                            ->options([
                                                'linear' => __('filament-short-url::default.qr_gradient_linear'),
                                                'radial' => __('filament-short-url::default.qr_gradient_radial'),
                                            ])
                                            ->required(fn (Get $get): bool => (bool) $get('qr_gradient_enabled'))
                                            ->visible(fn (Get $get): bool => (bool) $get('qr_gradient_enabled')),
                                    ]),
                            ]),

                        // ── Developer API & Webhooks ────────────────────────
                        Tab::make(__('filament-short-url::default.settings_tab_developer'))
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Section::make(__('filament-short-url::default.settings_section_global_webhook'))
                                    ->schema([
                                        TextInput::make('global_webhook_url')
                                            ->label(__('filament-short-url::default.settings_global_webhook_url'))
                                            ->helperText(__('filament-short-url::default.settings_global_webhook_url_helper'))
                                            ->url()
                                            ->nullable()
                                            ->columnSpanFull(),

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
                                            ->columnSpanFull(),
                                    ]),

                                Section::make(__('filament-short-url::default.settings_section_api_keys'))
                                    ->description(__('filament-short-url::default.settings_api_keys_description'))
                                    ->schema([
                                        \Filament\Forms\Components\Repeater::make('api_keys')
                                            ->label(__('filament-short-url::default.settings_api_keys'))
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('filament-short-url::default.api_key_name'))
                                                    ->required(),
                                                TextInput::make('key')
                                                    ->label(__('filament-short-url::default.api_key'))
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->default(fn () => 'sh_key_'.bin2hex(random_bytes(16))),
                                                Toggle::make('is_active')
                                                    ->label(__('filament-short-url::default.active'))
                                                    ->default(true),
                                            ])
                                            ->columns(3)
                                            ->default([]),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        app(ShortUrlSettingsManager::class)->set($data);

        Notification::make()
            ->title(__('filament-short-url::default.settings_saved'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('filament-short-url::default.stats_btn_back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->size('sm')
                ->url(static::getResource()::getUrl()),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-short-url::default.settings_nav_label');
    }
}
