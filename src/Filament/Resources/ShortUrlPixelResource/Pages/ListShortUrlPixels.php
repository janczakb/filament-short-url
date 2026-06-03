<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlPixelResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlPixelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListShortUrlPixels extends ManageRecords
{
    protected static string $resource = ShortUrlPixelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->modalWidth('md'),
        ];
    }
}
