<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

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
            'maxmind_database_path' => $mgr->get('maxmind_database_path', database_path('geoip/GeoLite2-Country.mmdb')),
            'queue_connection' => $mgr->get('queue_connection', 'sync'),
            'ga4_api_secret' => $mgr->get('ga4_api_secret'),
            'ga4_firebase_app_id' => $mgr->get('ga4_firebase_app_id'),
            'counter_buffering_enabled' => $mgr->get('counter_buffering_enabled', false),
            'trust_cdn_headers' => $mgr->get('trust_cdn_headers', false),
            'pruning_enabled' => $mgr->get('pruning_enabled', true),
            'pruning_retention_days' => $mgr->get('pruning_retention_days', 90),
            'rate_limiting_enabled' => $mgr->get('rate_limiting_enabled', false),
            'rate_limiting_max_attempts' => $mgr->get('rate_limiting_max_attempts', 60),
            'rate_limiting_decay_seconds' => $mgr->get('rate_limiting_decay_seconds', 60),
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
                                            ->minValue(4)
                                            ->maxValue(20)
                                            ->required(),

                                        TextInput::make('cache_ttl')
                                            ->label(__('filament-short-url::default.settings_cache_ttl'))
                                            ->helperText(__('filament-short-url::default.settings_cache_ttl_helper'))
                                            ->numeric()
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
                                            ->minValue(0)
                                            ->suffix('s')
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('geo_ip_enabled')),

                                        // ── Timeout (only for ip-api driver) ──
                                        TextInput::make('geo_ip_timeout')
                                            ->label(__('filament-short-url::default.settings_geoip_timeout'))
                                            ->helperText(__('filament-short-url::default.settings_geoip_timeout_helper'))
                                            ->numeric()
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
                                            ->placeholder(database_path('geoip/GeoLite2-Country.mmdb'))
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
                                            ->live()
                                            ->placeholder('••••••••••••••••••••'),

                                        // Firebase App ID only when API Secret is set
                                        TextInput::make('ga4_firebase_app_id')
                                            ->label(__('filament-short-url::default.settings_ga4_firebase_app_id'))
                                            ->helperText(__('filament-short-url::default.settings_ga4_firebase_app_id_helper'))
                                            ->placeholder('1:1234567890:android:abcdef123456')
                                            ->visible(fn (Get $get): bool => filled($get('ga4_api_secret'))),
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

                                        TextInput::make('pruning_retention_days')
                                            ->label(__('filament-short-url::default.settings_retention_days'))
                                            ->helperText(__('filament-short-url::default.settings_retention_days_helper'))
                                            ->numeric()
                                            ->minValue(0)
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
                                            ->minValue(1)
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('rate_limiting_enabled')),

                                        TextInput::make('rate_limiting_decay_seconds')
                                            ->label(__('filament-short-url::default.settings_rate_limiting_decay_seconds'))
                                            ->helperText(__('filament-short-url::default.settings_rate_limiting_decay_seconds_helper'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->suffix('s')
                                            ->required()
                                            ->visible(fn (Get $get): bool => (bool) $get('rate_limiting_enabled')),
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
