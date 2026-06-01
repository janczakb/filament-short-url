<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ViewShortUrlLogs extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $resource = ShortUrlResource::class;

    protected string $view = 'filament-short-url::logs';

    protected static ?string $title = 'Visit Logs';

    public ShortUrl $record;

    public function mount(ShortUrl $record): void
    {
        $this->record = $record;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ShortUrlVisit::query()->where('short_url_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('visited_at')
                    ->label(__('filament-short-url::default.stats_col_time'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament-short-url::default.stats_col_ip'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('country')
                    ->label(__('filament-short-url::default.stats_col_country'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->country_code ? "{$record->country_code} - {$state}" : ($state ?? '—'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('device_type')
                    ->label(__('filament-short-url::default.stats_col_device'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('browser')
                    ->label(__('filament-short-url::default.stats_col_browser'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->browser_version ? "{$state} ({$record->browser_version})" : ($state ?? '—'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('operating_system')
                    ->label(__('filament-short-url::default.stats_col_os'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->operating_system_version ? "{$state} ({$record->operating_system_version})" : ($state ?? '—'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('referer_url')
                    ->label(__('filament-short-url::default.track_referer'))
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('visited_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                Tables\Filters\SelectFilter::make('device_type')
                    ->label(__('filament-short-url::default.stats_col_device'))
                    ->options([
                        'desktop' => 'Desktop',
                        'mobile' => 'Mobile',
                        'tablet' => 'Tablet',
                        'robot' => 'Robot / Bot',
                    ]),

                Tables\Filters\SelectFilter::make('country_code')
                    ->label(__('filament-short-url::default.stats_col_country'))
                    ->options(function () {
                        return ShortUrlVisit::query()
                            ->whereNotNull('country')
                            ->whereNotNull('country_code')
                            ->where('short_url_id', $this->record->id)
                            ->distinct()
                            ->orderBy('country')
                            ->pluck('country', 'country_code')
                            ->toArray();
                    }),

                Tables\Filters\Filter::make('visited_at')
                    ->form([
                        DatePicker::make('visited_from')
                            ->label(__('filament-short-url::default.stats_filter_visited_from')),
                        DatePicker::make('visited_until')
                            ->label(__('filament-short-url::default.stats_filter_visited_until')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['visited_from'], fn ($q, $date) => $q->whereDate('visited_at', '>=', $date))
                            ->when($data['visited_until'], fn ($q, $date) => $q->whereDate('visited_at', '<=', $date));
                    }),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label(__('filament-short-url::default.stats_action_export'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (HasTable $livewire) {
                        return response()->streamDownload(function () use ($livewire) {
                            $handle = fopen('php://output', 'w');
                            // Add UTF-8 BOM for Microsoft Excel
                            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                            fputcsv($handle, [
                                __('filament-short-url::default.stats_csv_time'),
                                __('filament-short-url::default.stats_csv_ip'),
                                __('filament-short-url::default.stats_csv_country'),
                                __('filament-short-url::default.stats_csv_device'),
                                __('filament-short-url::default.stats_csv_browser'),
                                __('filament-short-url::default.stats_csv_os'),
                                __('filament-short-url::default.stats_csv_referer'),
                            ]);

                            $livewire->getFilteredTableQuery()
                                ->orderBy('visited_at', 'desc')
                                ->chunk(200, function ($visits) use ($handle) {
                                    foreach ($visits as $visit) {
                                        fputcsv($handle, [
                                            $visit->visited_at->toDateTimeString(),
                                            $visit->ip_address ?? '—',
                                            $visit->country ? "{$visit->country_code} - {$visit->country}" : '—',
                                            ucfirst($visit->device_type ?? '—'),
                                            $visit->browser ? "{$visit->browser} ({$visit->browser_version})" : '—',
                                            $visit->operating_system ? "{$visit->operating_system} ({$visit->operating_system_version})" : '—',
                                            $visit->referer_url ?? '—',
                                        ]);
                                    }
                                });

                            fclose($handle);
                        }, "visits-logs-{$this->record->url_key}-".now()->format('Y-m-d').'.csv');
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('filament-short-url::default.stats_btn_back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ShortUrlResource::getUrl()),

            Action::make('copy_url')
                ->label(__('filament-short-url::default.stats_btn_copy'))
                ->icon('heroicon-o-clipboard')
                ->color('gray')
                ->extraAttributes([
                    'x-on:click' => 'navigator.clipboard.writeText("'.$this->record->getShortUrl().'")',
                ]),
        ];
    }

    public function getTitle(): string
    {
        return __('filament-short-url::default.stats_table_title').' — '.$this->record->url_key;
    }
}
