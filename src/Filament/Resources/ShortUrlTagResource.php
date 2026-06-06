<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlTagResource\Pages\ListShortUrlTags;
use Bjanczak\FilamentShortUrl\Models\ShortUrlTag;
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

class ShortUrlTagResource extends Resource
{
    protected static ?string $model = ShortUrlTag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 54;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('filament-short-url::default.tags_navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-short-url::default.tag_resource_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-short-url::default.tags_navigation_label');
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
            return ShortUrlResource::getNavigationSort() + 4;
        } catch (\Throwable) {
            return static::$navigationSort;
        }
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament-short-url::default.tag_name'))
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->label(__('filament-short-url::default.tag_slug'))
                    ->required()
                    ->maxLength(100)
                    ->unique('short_url_tags', 'slug', ignoreRecord: true),

                Select::make('color')
                    ->label(__('filament-short-url::default.tag_color'))
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
                TextColumn::make('name')
                    ->label(__('filament-short-url::default.tag_name'))
                    ->view('filament-short-url::table.tag-row-column')
                    ->counts('shortUrls')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordUrl(
                fn (ShortUrlTag $record): string => ShortUrlResource::getUrl('index', [
                    'filters' => ['tags' => ['values' => [$record->id]]],
                ])
            )
            ->filters([])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->label(__('filament-short-url::default.action_edit_tag'))
                        ->modalWidth('md'),
                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->label(__('filament-short-url::default.action_delete_tag')),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->iconButton(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyState(view('filament-short-url::table.empty-state-tags'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortUrlTags::route('/'),
        ];
    }
}
