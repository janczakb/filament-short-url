<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
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
                    ->dateTime('M j, g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shortUrl.url_key')
                    ->label(__('filament-short-url::default.stats_col_link'))
                    ->icon('heroicon-o-link')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->shortUrl) {
                            return '—';
                        }

                        return str_replace(['http://', 'https://'], '', $record->shortUrl->getShortUrl());
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('referer_host')
                    ->label(__('filament-short-url::default.stats_col_referrer'))
                    ->icon('heroicon-o-link')
                    ->formatStateUsing(fn ($state) => empty($state) ? __('filament-short-url::default.stats_referer_direct') : $state)
                    ->color(fn ($state) => empty($state) ? 'gray' : null)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('country')
                    ->label(__('filament-short-url::default.stats_col_country'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if (! $state) {
                            return '—';
                        }
                        if ($record->country_code) {
                            try {
                                $flag = implode('', array_map(fn ($char) => mb_chr(ord($char) + 127397), str_split(strtoupper($record->country_code))));

                                return "{$flag} {$state}";
                            } catch (\Throwable) {
                                return $state;
                            }
                        }

                        return $state;
                    })
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('device_type')
                    ->label(__('filament-short-url::default.stats_col_device'))
                    ->icon(fn ($state) => match (strtolower($state)) {
                        'desktop' => 'heroicon-m-computer-desktop',
                        'mobile' => 'heroicon-m-device-phone-mobile',
                        'tablet' => 'heroicon-m-device-tablet',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn ($state) => match (strtolower($state)) {
                        'desktop' => __('filament-short-url::default.stats_device_desktop'),
                        'mobile' => __('filament-short-url::default.stats_device_mobile'),
                        'tablet' => __('filament-short-url::default.stats_device_tablet'),
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Hidden/toggleable columns for advanced details
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament-short-url::default.stats_col_ip'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('browser')
                    ->label(__('filament-short-url::default.stats_col_browser'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->browser_version ? "{$state} ({$record->browser_version})" : ($state ?? '—'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('operating_system')
                    ->label(__('filament-short-url::default.stats_col_os'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->operating_system_version ? "{$state} ({$record->operating_system_version})" : ($state ?? '—'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('browser_language')
                    ->label(__('filament-short-url::default.stats_col_language'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('utm_source')
                    ->label(__('filament-short-url::default.stats_col_utm_source'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('utm_medium')
                    ->label(__('filament-short-url::default.stats_col_utm_medium'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('utm_campaign')
                    ->label(__('filament-short-url::default.stats_col_utm_campaign'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('utm_term')
                    ->label(__('filament-short-url::default.stats_col_utm_term'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('utm_content')
                    ->label(__('filament-short-url::default.stats_col_utm_content'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('selected_variant')
                    ->label(__('filament-short-url::default.stats_col_variant'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_qr_scan')
                    ->label(__('filament-short-url::default.stats_col_qr_scan'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_bot')
                    ->label(__('filament-short-url::default.stats_col_bot'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_proxy')
                    ->label(__('filament-short-url::default.stats_col_proxy'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('visited_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->filters([
                Tables\Filters\SelectFilter::make('device_type')
                    ->label(__('filament-short-url::default.stats_col_device'))
                    ->options([
                        'desktop' => __('filament-short-url::default.stats_device_desktop'),
                        'mobile' => __('filament-short-url::default.stats_device_mobile'),
                        'tablet' => __('filament-short-url::default.stats_device_tablet'),
                        'robot' => __('filament-short-url::default.stats_device_bot'),
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
                            ->when($data['visited_from'], fn ($q, $date) => $q->where('visited_at', '>=', $date.' 00:00:00'))
                            ->when($data['visited_until'], fn ($q, $date) => $q->where('visited_at', '<=', $date.' 23:59:59'));
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('details')
                        ->label(__('filament-short-url::default.stats_action_view_details'))
                        ->icon('heroicon-o-information-circle')
                        ->modalHeading(__('filament-short-url::default.stats_modal_visit_details'))
                        ->modalWidth('md')
                        ->form([
                            Grid::make(2)
                                ->schema([
                                    Placeholder::make('ip_address')
                                        ->label(__('filament-short-url::default.stats_csv_ip'))
                                        ->content(fn ($record) => $record->ip_address ?? '—'),
                                    Placeholder::make('country')
                                        ->label(__('filament-short-url::default.stats_detail_location'))
                                        ->content(fn ($record) => $record->country
                                            ? ($record->country_code ? implode('', array_map(fn ($char) => mb_chr(ord($char) + 127397), str_split(strtoupper($record->country_code)))).' '.$record->country : $record->country)
                                            : '—'),
                                    Placeholder::make('device')
                                        ->label(__('filament-short-url::default.stats_col_device'))
                                        ->content(fn ($record) => $record->device_type ? match (strtolower($record->device_type)) {
                                            'desktop' => __('filament-short-url::default.stats_device_desktop'),
                                            'mobile' => __('filament-short-url::default.stats_device_mobile'),
                                            'tablet' => __('filament-short-url::default.stats_device_tablet'),
                                            default => ucfirst($record->device_type)
                                        } : '—'),
                                    Placeholder::make('browser')
                                        ->label(__('filament-short-url::default.stats_col_browser'))
                                        ->content(fn ($record) => $record->browser ? "{$record->browser} ({$record->browser_version})" : '—'),
                                    Placeholder::make('os')
                                        ->label(__('filament-short-url::default.stats_col_os'))
                                        ->content(fn ($record) => $record->operating_system ? "{$record->operating_system} ({$record->operating_system_version})" : '—'),
                                    Placeholder::make('referer')
                                        ->label(__('filament-short-url::default.stats_col_referrer'))
                                        ->content(fn ($record) => $record->referer_url ?? '—')
                                        ->columnSpanFull(),
                                    Placeholder::make('utm_source')
                                        ->label(__('filament-short-url::default.stats_col_utm_source'))
                                        ->content(fn ($record) => $record->utm_source ?? '—'),
                                    Placeholder::make('utm_medium')
                                        ->label(__('filament-short-url::default.stats_col_utm_medium'))
                                        ->content(fn ($record) => $record->utm_medium ?? '—'),
                                    Placeholder::make('utm_campaign')
                                        ->label(__('filament-short-url::default.stats_col_utm_campaign'))
                                        ->content(fn ($record) => $record->utm_campaign ?? '—'),
                                    Placeholder::make('utm_term')
                                        ->label(__('filament-short-url::default.stats_col_utm_term'))
                                        ->content(fn ($record) => $record->utm_term ?? '—'),
                                    Placeholder::make('utm_content')
                                        ->label(__('filament-short-url::default.stats_col_utm_content'))
                                        ->content(fn ($record) => $record->utm_content ?? '—'),
                                    Placeholder::make('selected_variant')
                                        ->label(__('filament-short-url::default.stats_col_variant'))
                                        ->content(fn ($record) => $record->selected_variant ?? '—'),
                                    Placeholder::make('is_bot')
                                        ->label(__('filament-short-url::default.stats_detail_is_bot'))
                                        ->content(fn ($record) => $record->is_bot ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no')),
                                    Placeholder::make('is_proxy')
                                        ->label(__('filament-short-url::default.stats_detail_is_proxy'))
                                        ->content(fn ($record) => $record->is_proxy ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no')),
                                    Placeholder::make('is_qr_scan')
                                        ->label(__('filament-short-url::default.stats_detail_is_qr'))
                                        ->content(fn ($record) => $record->is_qr_scan ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no')),
                                    Placeholder::make('visited_at')
                                        ->label(__('filament-short-url::default.stats_detail_visited_at'))
                                        ->content(fn ($record) => $record->visited_at->toDateTimeString()),
                                ]),
                        ]),
                ]),
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
                                __('filament-short-url::default.stats_csv_utm_source'),
                                __('filament-short-url::default.stats_csv_utm_medium'),
                                __('filament-short-url::default.stats_csv_utm_campaign'),
                                __('filament-short-url::default.stats_csv_utm_term'),
                                __('filament-short-url::default.stats_csv_utm_content'),
                                __('filament-short-url::default.stats_csv_variant'),
                                __('filament-short-url::default.stats_csv_qr_scan'),
                                __('filament-short-url::default.stats_csv_bot'),
                                __('filament-short-url::default.stats_csv_proxy'),
                            ]);

                            $livewire->getFilteredTableQuery()
                                ->orderBy('visited_at', 'desc')
                                ->chunk(200, function ($visits) use ($handle) {
                                    foreach ($visits as $visit) {
                                        fputcsv($handle, [
                                            $visit->visited_at->toDateTimeString(),
                                            $visit->ip_address ?? '—',
                                            $visit->country ? "{$visit->country_code} - {$visit->country}" : '—',
                                            $visit->device_type ? match (strtolower($visit->device_type)) {
                                                'desktop' => __('filament-short-url::default.stats_device_desktop'),
                                                'mobile' => __('filament-short-url::default.stats_device_mobile'),
                                                'tablet' => __('filament-short-url::default.stats_device_tablet'),
                                                default => ucfirst($visit->device_type)
                                            } : '—',
                                            $visit->browser ? "{$visit->browser} ({$visit->browser_version})" : '—',
                                            $visit->operating_system ? "{$visit->operating_system} ({$visit->operating_system_version})" : '—',
                                            $visit->referer_url ?? '—',
                                            $visit->utm_source ?? '—',
                                            $visit->utm_medium ?? '—',
                                            $visit->utm_campaign ?? '—',
                                            $visit->utm_term ?? '—',
                                            $visit->utm_content ?? '—',
                                            $visit->selected_variant ?? '—',
                                            $visit->is_qr_scan ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no'),
                                            $visit->is_bot ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no'),
                                            $visit->is_proxy ? __('filament-short-url::default.stats_yes') : __('filament-short-url::default.stats_no'),
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
