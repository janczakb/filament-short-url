<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Tabs;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\PasswordOpenGraphGuard;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\Support\TabCardHeader;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Component;

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
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card password-tab-card'])
                    ->schema([
                        Placeholder::make('password_card_header')
                            ->hiddenLabel()
                            ->visible(fn (Get $get): bool => ! $get('password_active_flag') || $get('is_entering_password'))
                            ->content(TabCardHeader::make(
                                'heroicon-o-lock-closed',
                                'validity-tab-card-icon--password',
                                'password_card_title',
                                'password_card_subtitle',
                            )),

                        Placeholder::make('password_empty_state')
                            ->hiddenLabel()
                            ->visible(fn (Get $get): bool => ! $get('password_active_flag') && ! $get('is_entering_password'))
                            ->content(new HtmlString(
                                '<div class="validity-tab-empty">'.
                                '<div class="validity-tab-empty-icon">'.
                                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>'.
                                '</div>'.
                                '<p class="validity-tab-empty-title">'.e(__('filament-short-url::default.password_empty_state_title')).'</p>'.
                                '<p class="validity-tab-empty-desc">'.e(__('filament-short-url::default.password_empty_state_desc')).'</p>'.
                                '</div>'
                            )),

                        Actions::make([
                            Action::make('setup_password')
                                ->label(__('filament-short-url::default.set_password'))
                                ->icon(Heroicon::Plus)
                                ->outlined()
                                ->extraAttributes(['class' => 'password-tab-setup-btn'])
                                ->action(fn (Set $set) => $set('is_entering_password', true)),
                        ])
                            ->alignment(Alignment::Center)
                            ->extraAttributes(['class' => 'password-tab-setup-action'])
                            ->visible(fn (Get $get): bool => ! $get('password_active_flag') && ! $get('is_entering_password')),

                        Grid::make(['default' => 1, 'md' => 12])
                            ->visible(fn (Get $get): bool => (bool) $get('password_active_flag') && ! $get('is_entering_password'))
                            ->extraAttributes(['class' => 'validity-tab-card-toolbar-grid password-tab-active-toolbar-grid'])
                            ->schema([
                                Placeholder::make('password_active_status')
                                    ->hiddenLabel()
                                    ->content(TabCardHeader::make(
                                        'heroicon-o-shield-check',
                                        'validity-tab-card-icon--password-active',
                                        'password_status_active',
                                        'password_status_active_desc',
                                    ))
                                    ->columnSpan(['default' => 12, 'md' => 9]),

                                Actions::make([
                                    Action::make('change_password')
                                        ->label(__('filament-short-url::default.password_change_short'))
                                        ->icon(Heroicon::PencilSquare)
                                        ->color('gray')
                                        ->outlined()
                                        ->size('sm')
                                        ->action(function (Set $set, Get $get, Component $livewire) {
                                            $set('password_active_flag', false);
                                            $set('is_entering_password', true);
                                            $set('new_password_input', null);
                                            $set('new_password_confirmation_input', null);
                                            self::syncPasswordPreview($livewire, self::isPasswordProtected($get));
                                        }),

                                    Action::make('remove_password')
                                        ->label(__('filament-short-url::default.password_remove_short'))
                                        ->icon(Heroicon::Trash)
                                        ->color('danger')
                                        ->outlined()
                                        ->size('sm')
                                        ->requiresConfirmation()
                                        ->action(function (Set $set, ?ShortUrl $record, Component $livewire) {
                                            $set('password', null);
                                            $set('new_password_input', null);
                                            $set('new_password_confirmation_input', null);
                                            $set('password_active_flag', false);
                                            $set('is_entering_password', false);

                                            if ($record) {
                                                $record->password = null;
                                            }

                                            self::syncPasswordPreview($livewire, false);
                                        }),
                                ])
                                    ->alignment(Alignment::End)
                                    ->extraAttributes(['class' => 'password-tab-active-actions password-tab-toolbar-actions'])
                                    ->columnSpan(['default' => 12, 'md' => 3]),
                            ]),

                        Group::make()
                            ->visible(fn (Get $get): bool => ! $get('password_active_flag') && $get('is_entering_password'))
                            ->extraAttributes(['class' => 'password-tab-form-panel'])
                            ->schema([
                                Placeholder::make('password_form_heading')
                                    ->hiddenLabel()
                                    ->content(new HtmlString(
                                        '<p class="password-tab-section-title">'.e(__('filament-short-url::default.password_settings_section')).'</p>'
                                    ))
                                    ->columnSpanFull(),

                                Grid::make(['default' => 1, 'md' => 2])
                                    ->schema([
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
                                    ]),

                                Actions::make([
                                    Action::make('cancel_password')
                                        ->label(__('filament-short-url::default.cancel'))
                                        ->color('gray')
                                        ->outlined()
                                        ->action(function (Get $get, Set $set, ?ShortUrl $record, Component $livewire) {
                                            $wasProtected = (bool) ($record && ! empty($record->password));
                                            $set('password_active_flag', $wasProtected);
                                            $set('is_entering_password', false);
                                            $set('new_password_input', null);
                                            $set('new_password_confirmation_input', null);
                                            self::syncPasswordPreview($livewire, $wasProtected || filled($get('password')));
                                        }),

                                    Action::make('confirm_password')
                                        ->label(__('filament-short-url::default.save_password'))
                                        ->color('primary')
                                        ->action(function (Get $get, Set $set, Component $livewire) {
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
                                            $set('new_password_input', null);
                                            $set('new_password_confirmation_input', null);
                                            PasswordOpenGraphGuard::clearFormState($set, $livewire);
                                        }),
                                ])
                                    ->alignment(Alignment::End)
                                    ->extraAttributes(['class' => 'password-tab-form-actions']),
                            ]),
                    ]),

                Section::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'validity-tab-card password-warning-card'])
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 12])
                            ->extraAttributes(['class' => 'validity-tab-card-toolbar-grid'])
                            ->schema([
                                Placeholder::make('warning_page_card_header')
                                    ->hiddenLabel()
                                    ->content(TabCardHeader::make(
                                        'heroicon-o-exclamation-triangle',
                                        'validity-tab-card-icon--warning',
                                        'warning_page_card_title',
                                        'warning_page_card_subtitle',
                                    ))
                                    ->columnSpan(['default' => 12, 'md' => 9]),

                                Toggle::make('show_warning_page')
                                    ->label(__('filament-short-url::default.show_warning_page'))
                                    ->hiddenLabel()
                                    ->default(false)
                                    ->live()
                                    ->inline(false)
                                    ->extraFieldWrapperAttributes([
                                        'class' => 'validity-tab-card-toolbar-action tracking-card-toolbar-action',
                                    ])
                                    ->extraAttributes([
                                        'aria-label' => __('filament-short-url::default.show_warning_page'),
                                    ])
                                    ->columnSpan(['default' => 12, 'md' => 3]),
                            ]),
                    ]),
            ]);
    }

    private static function isPasswordProtected(Get $get): bool
    {
        return PasswordOpenGraphGuard::isFormPasswordProtected($get);
    }

    private static function syncPasswordPreview(Component $livewire, bool $protected): void
    {
        $protectedJs = $protected ? 'true' : 'false';

        $livewire->js('window.dispatchEvent(new CustomEvent("fsu-password-protection-changed", { detail: { protected: '.$protectedJs.' } }))');
    }
}
