<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlCustomDomainResource\Pages\ListShortUrlCustomDomains;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;

class ShortUrlCustomDomainResource extends Resource
{
    protected static ?string $model = ShortUrlCustomDomain::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 52;

    protected static ?string $recordTitleAttribute = 'domain';

    public static function getNavigationLabel(): string
    {
        return __('filament-short-url::default.domains_navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-short-url::default.domain_resource_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-short-url::default.domains_navigation_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        try {
            return ShortUrlResource::getNavigationGroup();
        } catch (\Throwable) {
            return __('filament-short-url::default.navigation_group');
        }
    }

    public static function getNavigationSort(): ?int
    {
        try {
            return ShortUrlResource::getNavigationSort() + 2;
        } catch (\Throwable) {
            return static::$navigationSort;
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain')
                    ->label(__('filament-short-url::default.domain_label'))
                    ->required()
                    ->maxLength(191)
                    ->placeholder('e.g. links.acme.com')
                    ->rules([
                        'regex:/^[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(\.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+$/',
                    ])
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn ($state) => strtolower(trim($state))),

                Toggle::make('is_active')
                    ->label(__('filament-short-url::default.domain_status_active'))
                    ->default(true),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    ViewColumn::make('domain')
                        ->label(__('filament-short-url::default.domain_label'))
                        ->view('filament-short-url::table.domain-column')
                        ->searchable()
                        ->grow(),

                    ToggleColumn::make('is_active')
                        ->label(__('filament-short-url::default.domain_status_active')),

                    TextColumn::make('is_verified')
                        ->label(__('filament-short-url::default.domain_verification_status'))
                        ->badge()
                        ->color(fn ($state) => $state ? 'success' : 'danger')
                        ->formatStateUsing(fn ($state) => $state ? __('filament-short-url::default.domain_status_valid') : __('filament-short-url::default.domain_status_invalid')),
                ]),
            ])
            ->modifyQueryUsing(fn ($query) => $query
                ->withCount('shortUrls')
                ->withSum('shortUrls as total_clicks', 'total_visits')
                ->when(
                    config('filament-short-url.user.model') && auth()->check(),
                    fn ($q) => $q->where('user_id', auth()->id())
                )
            )
            ->filters([])
            ->actions([
                Action::make('dns_settings')
                    ->label('')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('gray')
                    ->iconButton()
                    ->badge(fn (ShortUrlCustomDomain $record): ?string => $record->is_verified ? null : '!')
                    ->badgeColor('danger')
                    ->modalHeading(fn (ShortUrlCustomDomain $record) => __('filament-short-url::default.dns_setup_title', ['domain' => $record->domain]))
                    ->modalWidth('2xl')
                    ->modalContent(fn (ShortUrlCustomDomain $record) => view('filament-short-url::table.dns-guide-panel', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->closeModalByClickingAway(false)
                    ->extraAttributes([
                        'class' => 'action-trigger-btn group flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border text-sm bg-bg-default text-content-emphasis hover:bg-bg-muted focus-visible:border-border-emphasis sm:inline-flex h-8 px-1.5 outline-none transition-all duration-200 border-transparent sm:group-hover/card:border-neutral-200',
                        'style' => 'width: 32px !important; height: 32px !important; padding: 0px !important; border-radius: 8px !important;',
                    ]),

                ActionGroup::make([
                    Action::make('verify')
                        ->label(__('filament-short-url::default.action_verify_dns'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function (ShortUrlCustomDomain $record) {
                            $verified = $record->verifyDns();
                            if ($verified) {
                                Notification::make()
                                    ->title(__('filament-short-url::default.dns_verify_success'))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title(__('filament-short-url::default.dns_verify_fail'))
                                    ->danger()
                                    ->send();
                            }
                        }),

                    ActionGroup::make([
                        DeleteAction::make()
                            ->label(__('filament-short-url::default.action_delete_domain'))
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->before(function (ShortUrlCustomDomain $record, DeleteAction $action): void {
                                if (! $record->shortUrls()->exists()) {
                                    return;
                                }

                                Notification::make()
                                    ->title('Cannot delete domain with assigned links.')
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            })
                            ->requiresConfirmation(fn (ShortUrlCustomDomain $record): bool => $record->shortUrls()->exists())
                            ->modalHeading(__('filament-short-url::default.action_delete_domain'))
                            ->modalDescription(__('filament-short-url::default.delete_domain_confirmation_desc'))
                            ->modalSubmitActionLabel(__('filament-short-url::default.action_delete_domain'))
                            ->closeModalByClickingAway(false),
                    ])->dropdown(false),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes([
                        'class' => 'action-trigger-btn group flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border text-sm bg-bg-default text-content-emphasis hover:bg-bg-muted focus-visible:border-border-emphasis data-[state=open]:ring-4 data-[state=open]:ring-border-subtle sm:inline-flex h-8 px-1.5 outline-none transition-all duration-200 border-transparent data-[state=open]:border-neutral-500 sm:group-hover/card:data-[state=closed]:border-neutral-200',
                        'style' => 'width: 32px !important; height: 32px !important; padding: 0px !important; border-radius: 8px !important;',
                    ])
                    ->label(''),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyState(view('filament-short-url::table.empty-state-domains'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortUrlCustomDomains::route('/'),
        ];
    }
}
