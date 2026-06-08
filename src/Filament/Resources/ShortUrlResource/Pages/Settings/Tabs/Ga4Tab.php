<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Bjanczak\FilamentShortUrl\Services\Ga4MeasurementProtocolService;
use Bjanczak\FilamentShortUrl\Services\ShortUrlSettingsManager;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class Ga4Tab
{
    /**
     * Build the GA4 settings form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.settings_tab_ga4'))
            ->key('ga4')
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

                        TextInput::make('ga4_firebase_app_id')
                            ->label(__('filament-short-url::default.settings_ga4_firebase_app_id'))
                            ->helperText(__('filament-short-url::default.settings_ga4_firebase_app_id_helper'))
                            ->placeholder('1:1234567890:android:abcdef123456'),

                        TextInput::make('ga4_verify_measurement_id')
                            ->label(__('filament-short-url::default.settings_ga4_verify_measurement_id'))
                            ->helperText(__('filament-short-url::default.settings_ga4_verify_measurement_id_helper'))
                            ->placeholder('G-XXXXXXXXXX')
                            ->rule('nullable|regex:/^G-[A-Z0-9]+$/i'),

                        Actions::make([
                            Action::make('verifyGa4ApiSecret')
                                ->label(__('filament-short-url::default.settings_ga4_verify'))
                                ->icon('heroicon-o-signal')
                                ->color('gray')
                                ->action(function (Get $get): void {
                                    $secret = trim($get('ga4_api_secret') ?? '');

                                    if ($secret === '' || str_contains($secret, '••••')) {
                                        $secret = (string) app(ShortUrlSettingsManager::class)->get('ga4_api_secret', '');
                                    }

                                    if ($secret === '') {
                                        Notification::make()
                                            ->title(__('filament-short-url::default.settings_ga4_verify_empty'))
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $measurementId = strtoupper(trim($get('ga4_verify_measurement_id') ?? ''));
                                    $firebaseAppId = trim($get('ga4_firebase_app_id') ?? '');

                                    if ($firebaseAppId === '' && $measurementId === '') {
                                        Notification::make()
                                            ->title(__('filament-short-url::default.settings_ga4_verify_measurement_required'))
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $result = app(Ga4MeasurementProtocolService::class)->validateCredentials(
                                        $measurementId,
                                        $secret,
                                        $firebaseAppId !== '' ? $firebaseAppId : null,
                                    );

                                    if ($result['valid']) {
                                        Notification::make()
                                            ->title(__('filament-short-url::default.settings_ga4_verify_ok'))
                                            ->success()
                                            ->send();

                                        return;
                                    }

                                    $firstMessage = collect($result['messages'])
                                        ->pluck('description')
                                        ->filter()
                                        ->first();

                                    Notification::make()
                                        ->title(__('filament-short-url::default.settings_ga4_verify_fail'))
                                        ->body(is_string($firstMessage) ? $firstMessage : null)
                                        ->danger()
                                        ->send();
                                }),
                        ]),
                    ]),
            ]);
    }
}
