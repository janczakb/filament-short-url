<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\Settings\Tabs;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Http;

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
            ]);
    }
}
