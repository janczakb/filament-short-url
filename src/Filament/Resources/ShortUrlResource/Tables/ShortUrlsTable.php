<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Tables;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ShortUrlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    // ROW 1: Favicon + Short URL with copy capability on click of text or icon (vertically centered!)
                    TextColumn::make('url_key')
                        ->label(__('filament-short-url::default.col_short_url'))
                        ->view('filament-short-url::table.url-key-column')
                        ->searchable(query: fn ($query, string $search) => $query->where('url_key', 'like', "%{$search}%")),

                    // ROW 2: Sub-arrow + Destination URL
                    TextColumn::make('destination_url')
                        ->label(__('filament-short-url::default.col_destination_url'))
                        ->view('filament-short-url::table.destination-column')
                        ->searchable(),

                    // ROW 3: Bottom Metadata (Total clicks / Unique clicks / Date added / Expiry / Redirect code)
                    TextColumn::make('metadata_badges')
                        ->view('filament-short-url::table.metadata-badges'),
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label(__('filament-short-url::default.col_status'))
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TernaryFilter::make('track_visits')
                    ->label(__('filament-short-url::default.track_visits'))
                    ->trueLabel('Tracking enabled')
                    ->falseLabel('Tracking disabled'),

                SelectFilter::make('single_use')
                    ->label(__('filament-short-url::default.single_use'))
                    ->options([
                        '0' => 'Multi-use',
                        '1' => 'Single-use',
                    ]),
            ])
            ->recordClasses(fn (): string => 'short-url-card group/card')
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->label(fn () => new HtmlString('<div class="flex items-center justify-between w-full min-w-[140px] text-left"><span>'.__('filament-short-url::default.action_edit').'</span><span class="text-[10px] bg-neutral-100 dark:bg-neutral-800 text-neutral-400 dark:text-neutral-500 rounded px-1.5 py-0.5 ml-auto font-mono">E</span></div>'))
                        ->modalAutofocus(false)
                        ->closeModalByClickingAway(false)
                        ->modalSubmitAction(false)
                        ->extraModalFooterActions(static fn (EditAction $action): array => [
                            Action::make('save_changes')
                                ->label(__('filament-actions::edit.single.modal.actions.save.label') ?: 'Save changes')
                                ->color('primary')
                                ->modal(static function () use ($action): bool {
                                    /** @var ShortUrl $record */
                                    $record = $action->getRecord();
                                    $data = $action->getRawData();
                                    $newKey = $data['url_key'] ?? null;

                                    return $newKey && $newKey !== $record->url_key;
                                })
                                ->requiresConfirmation(static function () use ($action): bool {
                                    /** @var ShortUrl $record */
                                    $record = $action->getRecord();
                                    $data = $action->getRawData();
                                    $newKey = $data['url_key'] ?? null;

                                    return $newKey && $newKey !== $record->url_key;
                                })
                                ->modalHeading(__('filament-short-url::default.url_key_change_confirmation_heading') ?: 'Replace link?')
                                ->modalDescription(__('filament-short-url::default.url_key_change_confirmation') ?: 'You have modified the short key of this link. Saving these changes will make the original short URL stop working.')
                                ->action(static function () use ($action) {
                                    /** @var ShortUrl $record */
                                    $record = $action->getRecord();
                                    $livewire = $action->getLivewire();
                                    $state = $livewire->getMountedTableActionForm()->getState();

                                    $action->process(function () use ($record, $state) {
                                        $record->update($state);
                                    });

                                    $action->success();
                                    $action->sendSuccessNotification();

                                    $livewire->mountedActions = [];
                                }),
                        ]),

                    Action::make('qrCode')
                        ->label(fn () => new HtmlString('<div class="flex items-center justify-between w-full min-w-[140px] text-left"><span>'.__('filament-short-url::default.action_qr').'</span><span class="text-[10px] bg-neutral-100 dark:bg-neutral-800 text-neutral-400 dark:text-neutral-500 rounded px-1.5 py-0.5 ml-auto font-mono">Q</span></div>'))
                        ->icon('heroicon-o-qr-code')
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->form(fn (ShortUrl $record): array => [
                            Forms\Components\Placeholder::make('qr_modal_content')
                                ->hiddenLabel()
                                ->content(view('filament-short-url::table.qr-code-modal', ['record' => $record])),
                        ]),

                    Action::make('share')
                        ->label(fn () => new HtmlString('<div class="flex items-center justify-between w-full min-w-[140px] text-left"><span>'.__('filament-short-url::default.action_share').'</span><span class="text-[10px] bg-neutral-100 dark:bg-neutral-800 text-neutral-400 dark:text-neutral-500 rounded px-1.5 py-0.5 ml-auto font-mono">I</span></div>'))
                        ->icon('heroicon-o-document-duplicate')
                        ->modalHeading(__('filament-short-url::default.share_title'))
                        ->modalWidth('md')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->form([
                            Forms\Components\Placeholder::make('platforms')
                                ->label(__('filament-short-url::default.share_description'))
                                ->content(fn (ShortUrl $record) => view('filament-short-url::table.share-platforms', ['record' => $record])),

                            Forms\Components\Placeholder::make('copy_field')
                                ->label('')
                                ->content(fn (ShortUrl $record) => view('filament-short-url::table.share-copy-field', ['record' => $record])),
                        ]),

                    Action::make('stats')
                        ->label(fn () => new HtmlString('<div class="flex items-center justify-between w-full min-w-[140px] text-left"><span>'.__('filament-short-url::default.action_stats').'</span><span class="text-[10px] bg-neutral-100 dark:bg-neutral-800 text-neutral-400 dark:text-neutral-500 rounded px-1.5 py-0.5 ml-auto font-mono">S</span></div>'))
                        ->icon('heroicon-o-chart-bar')
                        ->url(fn (ShortUrl $record): string => ShortUrlResource::getUrl('stats', ['record' => $record])),

                    ActionGroup::make([
                        DeleteAction::make()
                            ->label(fn () => new HtmlString('<div class="flex items-center justify-between w-full min-w-[140px] text-left text-red-600 dark:text-red-400 font-semibold"><span>'.__('filament-short-url::default.action_delete').'</span><span class="text-[10px] bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded px-1.5 py-0.5 ml-auto font-mono font-normal">X</span></div>'))
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->modalHeading(__('filament-short-url::default.action_delete'))
                            ->modalDescription(__('filament-short-url::default.delete_confirmation_desc'))
                            ->modalSubmitActionLabel(__('filament-short-url::default.action_delete'))
                            ->form(fn (ShortUrl $record): array => [
                                Forms\Components\Placeholder::make('link_preview')
                                    ->hiddenLabel()
                                    ->content(function () use ($record) {
                                        $shortUrl = $record->getShortUrl();
                                        $shortUrlDisplay = str($shortUrl)->after('://')->toString();

                                        $destHost = null;
                                        if ($record->destination_type === 'split') {
                                            $variants = $record->rotation_variants ?? [];
                                            if (! empty($variants)) {
                                                $firstVariant = reset($variants);
                                                $destHost = parse_url($firstVariant['url'] ?? '', PHP_URL_HOST);
                                            }
                                        }
                                        if (! $destHost) {
                                            $destHost = parse_url($record->destination_url ?? '', PHP_URL_HOST);
                                        }
                                        $destHostEncoded = e($destHost);

                                        $destUrl = $record->destination_url;
                                        if ($record->destination_type === 'split') {
                                            $destUrl = __('filament-short-url::default.destination_type_split');
                                        }
                                        $destUrlEncoded = e($destUrl);

                                        return new HtmlString("
                                            <div class=\"flex items-center gap-3 p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50/50 dark:bg-neutral-800/50 shadow-sm\">
                                                <div class=\"flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 flex-shrink-0 w-8 h-8\">
                                                    <img src=\"https://icons.duckduckgo.com/ip2/{$destHostEncoded}.ico\" 
                                                         class=\"w-full h-full object-contain\" 
                                                         onerror=\"this.src='https://heroicons.com/24/outline/link.svg'\" />
                                                </div>
                                                <div class=\"min-w-0 flex-1\">
                                                    <div class=\"text-sm font-semibold text-neutral-900 dark:text-neutral-100 truncate\">{$shortUrlDisplay}</div>
                                                    <div class=\"flex items-center gap-1.5 text-gray-500 dark:text-gray-400 text-xs mt-0.5\">
                                                        <svg class=\"w-3.5 h-3.5 flex-shrink-0 text-gray-400 dark:text-gray-500\" 
                                                             style=\"transform: scaleY(-1);\" 
                                                             xmlns=\"http://www.w3.org/2000/svg\" 
                                                             fill=\"none\" 
                                                             viewBox=\"0 0 24 24\" 
                                                             stroke-width=\"2.5\" 
                                                             stroke=\"currentColor\">
                                                            <path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 00-6 6v3\" />
                                                        </svg>
                                                        <span class=\"truncate max-w-[50ch] text-[#273144] dark:text-gray-300 text-[13px] leading-[16px] font-medium\">{$destUrlEncoded}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        ");
                                    }),
                                Forms\Components\TextInput::make('verification')
                                    ->label(fn () => new HtmlString(__('filament-short-url::default.delete_verification_label', [
                                        'short_url' => str($record->getShortUrl())->after('://')->toString(),
                                    ])))
                                    ->required()
                                    ->rules([
                                        function () use ($record) {
                                            return function (string $attribute, $value, \Closure $fail) use ($record) {
                                                $expected = str($record->getShortUrl())->after('://')->toString();
                                                if ($value !== $expected) {
                                                    $fail(__('filament-short-url::default.delete_verification_error'));
                                                }
                                            };
                                        },
                                    ]),
                            ]),
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
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label(__('filament-short-url::default.action_enable_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('disable')
                        ->label(__('filament-short-url::default.action_disable_selected'))
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_enabled' => false]))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'customDomain']))
            ->recordUrl(fn (ShortUrl $record): string => ShortUrlResource::getUrl('stats', ['record' => $record]))
            ->emptyState(view('filament-short-url::table.empty-state'));
    }
}
