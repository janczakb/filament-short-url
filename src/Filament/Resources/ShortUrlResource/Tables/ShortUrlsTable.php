<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Tables;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShortUrlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('url_key')
                    ->label(__('filament-short-url::default.col_short_url'))
                    ->getStateUsing(fn (ShortUrl $record): string => $record->getShortUrl())
                    ->copyable()
                    ->copyMessage(__('filament-short-url::default.qr_copied'))
                    ->fontFamily('mono')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(query: fn ($query, string $search) => $query->where('url_key', 'like', "%{$search}%")),

                TextColumn::make('destination_url')
                    ->label(__('filament-short-url::default.col_destination_url'))
                    ->limit(45)
                    ->tooltip(fn (ShortUrl $record): string => $record->destination_url)
                    ->url(fn (ShortUrl $record): string => $record->destination_url, shouldOpenInNewTab: true)
                    ->searchable(),

                TextColumn::make('total_visits')
                    ->label(__('filament-short-url::default.col_total_visits'))
                    ->numeric()
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('unique_visits')
                    ->label(__('filament-short-url::default.stats_card_unique'))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->sortable(),

                ToggleColumn::make('is_enabled')
                    ->label(__('filament-short-url::default.col_status'))
                    ->sortable(),

                IconColumn::make('track_visits')
                    ->label(__('filament-short-url::default.track_visits'))
                    ->boolean()
                    ->trueIcon('heroicon-o-signal')
                    ->falseIcon('heroicon-o-signal-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('expires_at')
                    ->label(__('filament-short-url::default.col_expires_at'))
                    ->dateTime('d M Y')
                    ->placeholder('Never')
                    ->color(fn (ShortUrl $record): string => $record->isExpired() ? 'danger' : 'gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('filament-short-url::default.col_created_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->recordActions([
                Action::make('stats')
                    ->label(__('filament-short-url::default.action_stats'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('gray')
                    ->size('sm')
                    ->url(fn (ShortUrl $record): string => ShortUrlResource::getUrl('stats', ['record' => $record])),

                EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->size('sm')
                    ->color('gray'),
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
            ->defaultSort('created_at', 'desc');
    }
}
