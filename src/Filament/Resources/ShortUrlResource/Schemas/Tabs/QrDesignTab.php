<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs\Tab;

class QrDesignTab
{
    /**
     * Build the QR code design form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_qr_design'))
            ->icon('heroicon-o-qr-code')
            ->schema([
                Group::make([
                    TextInput::make('qr_options')
                        ->extraAttributes([
                            'style' => 'display: none !important;',
                        ])
                        ->extraInputAttributes([
                            'id' => 'qr-options-json-input',
                        ])
                        ->hiddenLabel()
                        ->dehydrateStateUsing(fn (mixed $state): array => is_array($state) ? $state : (json_decode($state ?? '{}', true) ?: []))
                        ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                            $component->state(is_array($state) ? json_encode($state) : ($state ?? '{}'));
                        }),

                    TextInput::make('qr_logo')
                        ->extraAttributes([
                            'style' => 'display: none !important;',
                        ])
                        ->extraInputAttributes([
                            'id' => 'qr-logo-path-input',
                        ])
                        ->hiddenLabel()
                        ->nullable(),
                ])->extraAttributes([
                    'style' => 'display: none !important;',
                ]),

                ViewField::make('qr_designer')
                    ->view('filament-short-url::qr-designer')
                    ->columnSpanFull()
                    ->dehydrated(false),
            ]);
    }
}
