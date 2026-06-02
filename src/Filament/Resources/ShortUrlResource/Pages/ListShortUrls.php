<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Widgets\ShortUrlGlobalOverview;
use Bjanczak\FilamentShortUrl\Services\ShortUrlService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListShortUrls extends ManageRecords
{
    protected static string $resource = ShortUrlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')
                ->label(__('filament-short-url::default.settings_nav_label'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->size('sm')
                ->url(static::getResource()::getUrl('settings'))
                ->visible(fn () => ShortUrlSettingsPage::canAccess()),

            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->modalWidth('4xl')
                ->mutateFormDataUsing(function (array $data): array {
                    // Auto-generate key if not provided
                    if (empty($data['url_key'])) {
                        $data['url_key'] = app(ShortUrlService::class)->generateKey();
                    }

                    return $data;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ShortUrlGlobalOverview::class,
        ];
    }
}
