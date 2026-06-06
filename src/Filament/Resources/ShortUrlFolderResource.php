<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlFolderResource\Pages\ListShortUrlFolders;
use Bjanczak\FilamentShortUrl\Models\ShortUrlFolder;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ShortUrlFolderResource extends Resource
{
    protected static ?string $model = ShortUrlFolder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 53;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('filament-short-url::default.folders_navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-short-url::default.folder_resource_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-short-url::default.folders_navigation_label');
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
            return ShortUrlResource::getNavigationSort() + 3;
        } catch (\Throwable) {
            return static::$navigationSort;
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament-short-url::default.folder_name'))
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->label(__('filament-short-url::default.folder_slug'))
                    ->required()
                    ->maxLength(100)
                    ->unique('short_url_folders', 'slug', ignoreRecord: true),

                Select::make('color')
                    ->label(__('filament-short-url::default.folder_color'))
                    ->allowHtml()
                    ->options([
                        'gray' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #737373;"></span><span>' . __('filament-short-url::default.color_gray') . '</span></span>',
                        'red' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #ef4444;"></span><span>' . __('filament-short-url::default.color_red') . '</span></span>',
                        'blue' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #3b82f6;"></span><span>' . __('filament-short-url::default.color_blue') . '</span></span>',
                        'green' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #10b981;"></span><span>' . __('filament-short-url::default.color_green') . '</span></span>',
                        'yellow' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #f59e0b;"></span><span>' . __('filament-short-url::default.color_yellow') . '</span></span>',
                        'indigo' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #6366f1;"></span><span>' . __('filament-short-url::default.color_indigo') . '</span></span>',
                        'purple' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #a855f7;"></span><span>' . __('filament-short-url::default.color_purple') . '</span></span>',
                        'pink' => '<span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0 border border-black/10 dark:border-white/10" style="background-color: #ec4899;"></span><span>' . __('filament-short-url::default.color_pink') . '</span></span>',
                    ])
                    ->default('gray')
                    ->required()
                    ->native(false),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->label(__('filament-short-url::default.folder_name'))
                        ->view('filament-short-url::table.folder-card-column')
                        ->counts('shortUrls')
                        ->searchable()
                        ->sortable()
                        ->extraAttributes([
                            'class' => 'h-full',
                        ]),
                ])->extraAttributes([
                    'class' => 'h-full',
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
            ])
            ->recordUrl(
                fn (ShortUrlFolder $record): string => ShortUrlResource::getUrl('index', [
                    'filters' => ['folder' => ['values' => [$record->id]]],
                ])
            )
            ->recordClasses(fn (): string => 'folder-card relative flex flex-col justify-between rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 px-5 py-4 transition-all duration-200 h-[120px] sm:h-[132px] group/card hover:shadow-md hover:border-neutral-350 dark:hover:border-neutral-700')
            ->filters([])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->label(__('filament-short-url::default.action_edit_folder'))
                        ->modalWidth('md'),
                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->label(__('filament-short-url::default.action_delete_folder')),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->iconButton()
                ->extraAttributes([
                    'class' => 'action-trigger-btn group flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border text-sm bg-transparent hover:bg-bg-muted data-[state=open]:ring-4 data-[state=open]:ring-border-subtle sm:inline-flex h-8 w-8 outline-none transition-all duration-200 border-transparent data-[state=open]:border-neutral-500 sm:group-hover/card:data-[state=closed]:border-neutral-200',
                    'style' => 'width: 32px !important; height: 32px !important; padding: 0px !important; border-radius: 8px !important;',
                ]),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyState(view('filament-short-url::table.empty-state-folders'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortUrlFolders::route('/'),
        ];
    }
}
