<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlPixelResource\Pages\ListShortUrlPixels;
use Bjanczak\FilamentShortUrl\Models\ShortUrlPixel;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ShortUrlPixelResource extends Resource
{
    protected static ?string $model = ShortUrlPixel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?int $navigationSort = 51;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('filament-short-url::default.pixels_navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-short-url::default.pixel_resource_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-short-url::default.pixels_navigation_label');
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
            return ShortUrlResource::getNavigationSort() + 1;
        } catch (\Throwable) {
            return static::$navigationSort;
        }
    }

    /**
     * @return array<int, TextInput|Select|Toggle>
     */
    public static function formComponents(): array
    {
        return [
            TextInput::make('name')
                ->label(__('filament-short-url::default.pixel_name'))
                ->required()
                ->maxLength(150)
                ->placeholder('e.g. Meta Ads - Yacht Promo'),

            Select::make('type')
                ->label(__('filament-short-url::default.pixel_type'))
                ->options(self::typeOptions())
                ->required()
                ->native(false),

            TextInput::make('pixel_id')
                ->label(__('filament-short-url::default.pixel_id_label'))
                ->required()
                ->maxLength(100)
                ->placeholder('e.g. 1234567890 or G-XXXXXXXXXX'),

            Toggle::make('is_active')
                ->label(__('filament-short-url::default.pixel_status_active'))
                ->default(true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'meta' => 'Meta / Facebook Pixel',
            'google' => 'Google Tag (GA4 / GTM)',
            'linkedin' => 'LinkedIn Insight Tag',
            'tiktok' => 'TikTok Pixel',
            'pinterest' => 'Pinterest Tag',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(static::formComponents())
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-short-url::default.pixel_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label(__('filament-short-url::default.pixel_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'meta' => 'Meta',
                        'google' => 'Google',
                        'linkedin' => 'LinkedIn',
                        'tiktok' => 'TikTok',
                        'pinterest' => 'Pinterest',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'meta' => 'info',
                        'google' => 'warning',
                        'linkedin' => 'primary',
                        'tiktok' => 'gray',
                        'pinterest' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('pixel_id')
                    ->label(__('filament-short-url::default.pixel_id_label'))
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->color('gray'),

                ToggleColumn::make('is_active')
                    ->label(__('filament-short-url::default.pixel_status_active'))
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(__('filament-short-url::default.action_edit'))
                    ->modalWidth('md'),
                DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip(__('filament-short-url::default.action_delete')),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyState(view('filament-short-url::table.empty-state-pixels'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortUrlPixels::route('/'),
        ];
    }
}
