<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;

class ViewShortUrlStats extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ShortUrlResource::class;

    protected string $view = 'filament-short-url::stats';

    protected static ?string $title = 'Statistics';

    public ShortUrl $record;

    public int $totalVisits = 0;

    public function mount(ShortUrl $record): void
    {
        $this->record = $record;
        $this->totalVisits = $record->getCachedStats()['totalVisits'] ?? 0;
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
