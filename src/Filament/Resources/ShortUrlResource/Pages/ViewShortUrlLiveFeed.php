<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrl;
use Bjanczak\FilamentShortUrl\Models\ShortUrlVisit;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class ViewShortUrlLiveFeed extends Page
{
    protected static string $resource = ShortUrlResource::class;

    protected string $view = 'filament-short-url::live';

    protected static ?string $title = 'Live Feed';

    public ShortUrl $record;

    public function mount(ShortUrl $record): void
    {
        $this->record = $record;
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
        ];
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
        return __('filament-short-url::default.stats_tab_live_feed').' — '.$this->record->url_key;
    }
}
