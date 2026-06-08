<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;

class ViewShortUrlStats extends Page implements HasForms
{
    use AuthorizesRequests;
    use InteractsWithForms;

    protected static string $resource = ShortUrlResource::class;

    protected string $view = 'filament-short-url::stats';

    protected static ?string $title = 'Statistics';

    public ShortUrl $record;

    public int $totalVisits = 0;

    public ?array $filterData = [];

    public array $activeFilters = [];

    public string $activeTab = 'statistics';

    #[On('set-stats-filter')]
    public function setStatsFilter(string $key, $value): void
    {
        if (count($this->activeFilters) >= 5 && ! isset($this->activeFilters[$key])) {
            Notification::make()
                ->title(__('filament-short-url::default.stats_filter_limit_exceeded'))
                ->warning()
                ->send();

            return;
        }

        $this->activeFilters[$key] = $value;
        $this->totalVisits = $this->record->getCachedStats(
            $this->filterData['date_from'] ?? null,
            $this->filterData['date_to'] ?? null,
            $this->activeFilters
        )['totalVisits'] ?? 0;
    }

    #[On('clear-stats-filter')]
    public function clearStatsFilter(string $key): void
    {
        unset($this->activeFilters[$key]);
        $this->totalVisits = $this->record->getCachedStats(
            $this->filterData['date_from'] ?? null,
            $this->filterData['date_to'] ?? null,
            $this->activeFilters
        )['totalVisits'] ?? 0;
    }

    #[On('clear-all-stats-filters')]
    public function clearAllStatsFilters(): void
    {
        $this->activeFilters = [];
        $this->totalVisits = $this->record->getCachedStats(
            $this->filterData['date_from'] ?? null,
            $this->filterData['date_to'] ?? null,
            $this->activeFilters
        )['totalVisits'] ?? 0;
    }

    public function mount(ShortUrl $record): void
    {
        $this->authorize('view', $record);

        $this->record = $record;

        $this->form->fill([
            'preset' => '30_days',
            'date_from' => now()->subDays(29)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);

        if (empty($this->filterData)) {
            $this->filterData = [
                'preset' => '30_days',
                'date_from' => now()->subDays(29)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ];
        }

        $this->totalVisits = $record->getCachedStats(
            $this->filterData['date_from'] ?? null,
            $this->filterData['date_to'] ?? null,
            $this->activeFilters
        )['totalVisits'] ?? 0;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('preset')
                    ->hiddenLabel()
                    ->options([
                        '24_hours' => __('filament-short-url::default.stats_preset_24_hours'),
                        '7_days' => __('filament-short-url::default.stats_preset_7_days'),
                        '30_days' => __('filament-short-url::default.stats_preset_30_days'),
                        '90_days' => __('filament-short-url::default.stats_preset_90_days'),
                        'custom' => __('filament-short-url::default.stats_preset_custom'),
                    ])
                    ->live()
                    ->columnSpan(fn ($get) => $get('preset') === 'custom' ? 1 : 'full')
                    ->afterStateUpdated(function ($state, $set) {
                        $to = now()->format('Y-m-d');
                        $from = match ($state) {
                            '24_hours' => now()->subDay()->format('Y-m-d'),
                            '7_days' => now()->subDays(6)->format('Y-m-d'),
                            '30_days' => now()->subDays(29)->format('Y-m-d'),
                            '90_days' => now()->subDays(89)->format('Y-m-d'),
                            'custom' => now()->subDays(29)->format('Y-m-d'),
                            default => null,
                        };
                        if ($from) {
                            $set('date_from', $from);
                            $set('date_to', $to);
                        }
                    }),

                DatePicker::make('date_from')
                    ->hiddenLabel()
                    ->placeholder(__('filament-short-url::default.stats_filter_visited_from'))
                    ->native(false)
                    ->live()
                    ->columnSpan(1)
                    ->visible(fn ($get) => $get('preset') === 'custom')
                    ->required(),

                DatePicker::make('date_to')
                    ->hiddenLabel()
                    ->placeholder(__('filament-short-url::default.stats_filter_visited_until'))
                    ->native(false)
                    ->live()
                    ->columnSpan(1)
                    ->visible(fn ($get) => $get('preset') === 'custom')
                    ->required(),
            ])
            ->columns(3)
            ->statePath('filterData');
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('filament-short-url::default.stats_btn_back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ShortUrlResource::getUrl()),

            Action::make('refresh')
                ->label(__('filament-short-url::default.stats_btn_refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->record->clearStatsCache(
                        $this->filterData['date_from'] ?? null,
                        $this->filterData['date_to'] ?? null,
                        $this->activeFilters
                    );

                    $this->dispatch('$refresh');

                    Notification::make()
                        ->title(__('filament-short-url::default.stats_refresh_success'))
                        ->success()
                        ->send();
                }),

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
        return __('filament-short-url::default.stats_title').' — '.$this->record->url_key;
    }
}
