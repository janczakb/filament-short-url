<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\HtmlString;

class PasswordTab
{
    /**
     * Build the password/security form tab.
     */
    public static function make(): Tab
    {
        return Tab::make(__('filament-short-url::default.tab_password'))
            ->icon('heroicon-o-lock-closed')
            ->schema([
                Hidden::make('password'),
                Hidden::make('password_active_flag')
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Hidden $component, $state, ?ShortUrl $record) {
                        $component->state($record && ! empty($record->password));
                    }),
                Hidden::make('is_entering_password')
                    ->dehydrated(false)
                    ->default(false),

                Section::make()
                    ->visible(fn (Get $get): bool => ! $get('password_active_flag') && ! $get('is_entering_password'))
                    ->extraAttributes([
                        'class' => 'rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10 transition duration-200 ring-0 shadow-none [&>div]:bg-transparent',
                    ])
                    ->schema([
                        Group::make([
                            Placeholder::make('empty_state_icon')
                                ->hiddenLabel()
                                ->content(new HtmlString('
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                                            <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Brak zabezpieczeń</h3>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">Dodaj hasło, aby ograniczyć dostęp do tego skróconego linku tylko dla wybranych osób.</p>
                                    </div>
                                ')),

                            Actions::make([
                                Action::make('setup_password')
                                    ->label(__('filament-short-url::default.set_password'))
                                    ->icon('heroicon-m-plus')
                                    ->color('primary')
                                    ->action(fn (Set $set) => $set('is_entering_password', true)),
                            ])->alignment(Alignment::Center),
                        ]),
                    ]),

                Section::make(__('filament-short-url::default.password_status_active'))
                    ->visible(fn (Get $get): bool => (bool) $get('password_active_flag'))
                    ->icon('heroicon-m-shield-check')
                    ->iconColor('success')
                    ->description('Link jest zabezpieczony. Dostęp wymaga podania prawidłowego hasła.')
                    ->extraAttributes([
                        'class' => 'bg-white dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl shadow-sm',
                    ])
                    ->headerActions([
                        Action::make('change_password')
                            ->label('Zmień')
                            ->icon('heroicon-m-pencil-square')
                            ->color('gray')
                            ->button()
                            ->outlined()
                            ->size('sm')
                            ->action(function (Set $set) {
                                $set('password_active_flag', false);
                                $set('is_entering_password', true);
                                $set('new_password_input', null);
                                $set('new_password_confirmation_input', null);
                            }),

                        Action::make('remove_password')
                            ->label('Usuń')
                            ->icon('heroicon-m-trash')
                            ->color('danger')
                            ->button()
                            ->outlined()
                            ->size('sm')
                            ->requiresConfirmation()
                            ->action(function (Set $set, ?ShortUrl $record) {
                                $set('password', null);
                                $set('new_password_input', null);
                                $set('new_password_confirmation_input', null);
                                $set('password_active_flag', false);
                                $set('is_entering_password', false);
                                if ($record) {
                                    $record->password = null;
                                }
                            }),
                    ])
                    ->schema([]),

                Section::make('Ustawienia hasła')
                    ->visible(fn (Get $get): bool => ! $get('password_active_flag') && $get('is_entering_password'))
                    ->schema([
                        Group::make([
                            TextInput::make('new_password_input')
                                ->label(__('filament-short-url::default.new_password'))
                                ->password()
                                ->revealable()
                                ->live()
                                ->maxLength(255)
                                ->dehydrated(false)
                                ->required(fn (Get $get): bool => ! $get('password_active_flag') && $get('is_entering_password')),

                            TextInput::make('new_password_confirmation_input')
                                ->label(__('filament-short-url::default.confirm_password'))
                                ->password()
                                ->revealable()
                                ->same('new_password_input')
                                ->maxLength(255)
                                ->dehydrated(false)
                                ->required(fn (Get $get): bool => ! empty($get('new_password_input'))),
                        ])->columns(2),

                        Actions::make([
                            Action::make('cancel_password')
                                ->label(__('filament-short-url::default.cancel'))
                                ->color('gray')
                                ->action(function (Get $get, Set $set, ?ShortUrl $record) {
                                    $set('password_active_flag', $record && ! empty($record->password));
                                    $set('is_entering_password', false);
                                    $set('new_password_input', null);
                                    $set('new_password_confirmation_input', null);
                                }),

                            Action::make('confirm_password')
                                ->label('Zapisz hasło')
                                ->color('primary')
                                ->action(function (Get $get, Set $set) {
                                    $password = $get('new_password_input');
                                    $confirm = $get('new_password_confirmation_input');

                                    if (empty($password)) {
                                        Notification::make()->title(__('filament-short-url::default.password_required_error'))->danger()->send();

                                        return;
                                    }
                                    if ($password !== $confirm) {
                                        Notification::make()->title(__('filament-short-url::default.password_mismatch_error'))->danger()->send();

                                        return;
                                    }

                                    $set('password', $password);
                                    $set('password_active_flag', true);
                                    $set('is_entering_password', false);
                                }),
                        ])->alignment(Alignment::End),
                    ]),

                Section::make()
                    ->schema([
                        Toggle::make('show_warning_page')
                            ->label(__('filament-short-url::default.show_warning_page'))
                            ->hintIcon('heroicon-o-information-circle', tooltip: __('filament-short-url::default.show_warning_page_helper'))
                            ->default(false)
                            ->inline(false),
                    ])
                    ->compact()
                    ->extraAttributes(['class' => 'bg-transparent border-none shadow-none mt-4']),
            ]);
    }
}
