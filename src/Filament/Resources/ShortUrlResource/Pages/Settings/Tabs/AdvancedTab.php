<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentShortUrl\Services\SafeBrowsingService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class AdvancedTab
{
    /**
     * Build the advanced & security settings form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_advanced'))
            ->key('advanced')
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
                        Placeholder::make('rate_limiting_route_info')
                            ->content(fn () => new HtmlString(__('filament-short-url::default.settings_rate_limiting_route_info')))
                            ->columnSpanFull(),

                        Toggle::make('rate_limiting_enabled')
                            ->label(__('filament-short-url::default.settings_rate_limiting_enabled'))
                            ->helperText(__('filament-short-url::default.settings_rate_limiting_enabled_helper'))
                            ->default(false)
                            ->live()
                            ->inline(false),

                        NumberStepper::make('rate_limiting_max_attempts')
                            ->label(__('filament-short-url::default.settings_rate_limiting_max_attempts'))
                            ->helperText(__('filament-short-url::default.settings_rate_limiting_max_attempts_helper'))
                            ->minValue(1)
                            ->maxValue(1000)
                            ->variant('secondary')
                            ->size('sm')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('rate_limiting_enabled')),

                        NumberStepper::make('rate_limiting_decay_seconds')
                            ->label(__('filament-short-url::default.settings_rate_limiting_decay_seconds'))
                            ->helperText(__('filament-short-url::default.settings_rate_limiting_decay_seconds_helper'))
                            ->minValue(1)
                            ->maxValue(3600)
                            ->suffix('s')
                            ->variant('secondary')
                            ->size('sm')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('rate_limiting_enabled')),
                    ]),

                Section::make(__('filament-short-url::default.settings_section_security_v2'))
                    ->description(__('filament-short-url::default.settings_section_security_v2_desc'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('vpn_detection_enabled')
                            ->label(__('filament-short-url::default.settings_vpn_detection_enabled'))
                            ->helperText(__('filament-short-url::default.settings_vpn_detection_enabled_helper'))
                            ->default(false)
                            ->inline(false)
                            ->live()
                            ->columnSpanFull(),

                        Select::make('vpn_detection_driver')
                            ->label(__('filament-short-url::default.settings_vpn_driver'))
                            ->helperText(__('filament-short-url::default.settings_vpn_driver_helper'))
                            ->options([
                                'ip-api' => __('filament-short-url::default.settings_vpn_driver_ipapi'),
                                'vpnapi' => __('filament-short-url::default.settings_vpn_driver_vpnapi'),
                            ])
                            ->default('ip-api')
                            ->live()
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('vpn_detection_enabled')),

                        TextInput::make('vpnapi_key')
                            ->label(__('filament-short-url::default.settings_vpnapi_key'))
                            ->helperText(__('filament-short-url::default.settings_vpnapi_key_helper'))
                            ->password()
                            ->revealable()
                            ->placeholder('••••••••••••••••••••')
                            ->visible(fn (Get $get): bool => (bool) $get('vpn_detection_enabled') && $get('vpn_detection_driver') === 'vpnapi'),

                        Select::make('vpn_block_action')
                            ->label(__('filament-short-url::default.settings_vpn_block_action'))
                            ->helperText(__('filament-short-url::default.settings_vpn_block_action_helper'))
                            ->options([
                                'flag_only' => __('filament-short-url::default.settings_vpn_block_flag_only'),
                                'block_with_403' => __('filament-short-url::default.settings_vpn_block_block_403'),
                            ])
                            ->default('flag_only')
                            ->required()
                            ->live()
                            ->visible(fn (Get $get): bool => (bool) $get('vpn_detection_enabled')),

                        Placeholder::make('vpn_block_social_warning')
                            ->content(fn () => new HtmlString(__('filament-short-url::default.settings_vpn_block_social_warning')))
                            ->visible(fn (Get $get): bool => (bool) $get('vpn_detection_enabled')
                                && $get('vpn_block_action') === 'block_with_403')
                            ->columnSpanFull(),

                        NumberStepper::make('vpn_detection_timeout')
                            ->label(__('filament-short-url::default.settings_vpn_detection_timeout'))
                            ->helperText(__('filament-short-url::default.settings_vpn_detection_timeout_helper'))
                            ->minValue(1)
                            ->maxValue(30)
                            ->suffix('s')
                            ->variant('tertiary')
                            ->size('sm')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('vpn_detection_enabled')),

                        NumberStepper::make('vpn_detection_cache_ttl')
                            ->label(__('filament-short-url::default.settings_vpn_detection_cache_ttl'))
                            ->helperText(__('filament-short-url::default.settings_vpn_detection_cache_ttl_helper'))
                            ->minValue(60)
                            ->maxValue(604800)
                            ->step(60)
                            ->suffix('s')
                            ->variant('tertiary')
                            ->size('sm')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('vpn_detection_enabled')),

                        Toggle::make('safe_browsing_enabled')
                            ->label(__('filament-short-url::default.settings_safe_browsing_enabled'))
                            ->helperText(__('filament-short-url::default.settings_safe_browsing_enabled_helper'))
                            ->default(false)
                            ->inline(false)
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('google_safe_browsing_api_key')
                            ->label(__('filament-short-url::default.settings_safe_browsing_api_key'))
                            ->helperText(__('filament-short-url::default.settings_safe_browsing_api_key_helper'))
                            ->password()
                            ->revealable()
                            ->placeholder('AIza••••••••••••••••••')
                            ->columnSpanFull()
                            ->suffixAction(
                                Action::make('testSafeBrowsing')
                                    ->label(__('filament-short-url::default.settings_safe_browsing_test'))
                                    ->icon('heroicon-o-signal')
                                    ->color('gray')
                                    ->action(function (Get $get, SafeBrowsingService $svc): void {
                                        $key = trim($get('google_safe_browsing_api_key') ?? '');
                                        if (empty($key)) {
                                            Notification::make()
                                                ->title(__('filament-short-url::default.settings_safe_browsing_test_empty'))
                                                ->warning()->send();

                                            return;
                                        }
                                        try {
                                            $safe = $svc->isSafeWithKey('https://google.com', $key);
                                            Notification::make()
                                                ->title($safe
                                                    ? __('filament-short-url::default.settings_safe_browsing_test_ok')
                                                    : __('filament-short-url::default.settings_safe_browsing_test_fail'))
                                                ->color($safe ? 'success' : 'danger')
                                                ->send();
                                        } catch (\Throwable $e) {
                                            Notification::make()
                                                ->title(__('filament-short-url::default.settings_safe_browsing_test_error'))
                                                ->body($e->getMessage())
                                                ->danger()->send();
                                        }
                                    })
                            )
                            ->visible(fn (Get $get): bool => (bool) $get('safe_browsing_enabled')),
                    ]),

                Section::make(__('filament-short-url::default.settings_section_analytics_security'))
                    ->description(__('filament-short-url::default.settings_section_analytics_security_desc'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('click_deduplication_enabled')
                            ->label(__('filament-short-url::default.settings_click_dedup_enabled'))
                            ->helperText(__('filament-short-url::default.settings_click_dedup_enabled_helper'))
                            ->default(false)
                            ->inline(false)
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('click_deduplication_hours')
                            ->label(__('filament-short-url::default.settings_click_dedup_hours'))
                            ->helperText(__('filament-short-url::default.settings_click_dedup_hours_helper'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(168)
                            ->default(1)
                            ->suffix('h')
                            ->required()
                            ->visible(fn (Get $get): bool => (bool) $get('click_deduplication_enabled')),

                        Toggle::make('bot_verify_google_bot_ip')
                            ->label(__('filament-short-url::default.settings_bot_verify_googlebot'))
                            ->helperText(__('filament-short-url::default.settings_bot_verify_googlebot_helper'))
                            ->default(false)
                            ->inline(false)
                            ->columnSpanFull(),

                        TextInput::make('bot_debug_secret')
                            ->label(__('filament-short-url::default.settings_bot_debug_secret'))
                            ->helperText(__('filament-short-url::default.settings_bot_debug_secret_helper'))
                            ->password()
                            ->revealable()
                            ->placeholder('••••••••••••')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
