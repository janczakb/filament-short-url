<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\ListShortUrls;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\ViewShortUrlLogs;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\ViewShortUrlStats;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Schemas\ShortUrlForm;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Tables\ShortUrlsTable;
use Bjanczak\FilamentShortUrl\FilamentShortUrlPlugin;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ShortUrlResource extends Resource
{
    protected static ?string $model = ShortUrl::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'url_key';

    public static function getNavigationLabel(): string
    {
        try {
            $label = FilamentShortUrlPlugin::get()->getNavigationLabel();
            if ($label) {
                return $label;
            }
        } catch (\Throwable) {
            // Ignore
        }

        return __('filament-short-url::default.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('filament-short-url::default.resource_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-short-url::default.navigation_label');
    }

    // ─── Plugin-aware navigation overrides ───────────────────────────────────

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        try {
            $icon = FilamentShortUrlPlugin::get()->getNavigationIcon();
            if ($icon) {
                return $icon;
            }
        } catch (\Throwable) {
            // Ignore
        }

        return static::$navigationIcon;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        try {
            $plugin = FilamentShortUrlPlugin::get();
            $group = $plugin->getNavigationGroup();
            if ($group) {
                return $group;
            }
        } catch (\Throwable) {
            // Ignore
        }

        return __('filament-short-url::default.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        try {
            $plugin = FilamentShortUrlPlugin::get();

            return $plugin->getNavigationSort() ?? static::$navigationSort;
        } catch (\Throwable) {
            return static::$navigationSort;
        }
    }

    // ─── Resource definition ─────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return ShortUrlForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShortUrlsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortUrls::route('/'),
            'stats' => ViewShortUrlStats::route('/{record}/stats'),
            'stats.logs' => ViewShortUrlLogs::route('/{record}/stats/logs'),
        ];
    }
}
