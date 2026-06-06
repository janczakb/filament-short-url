<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlFolderResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlFolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListShortUrlFolders extends ManageRecords
{
    protected static string $resource = ShortUrlFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-short-url::default.empty_state_folder_action'))
                ->modalHeading(__('filament-short-url::default.empty_state_folder_action'))
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->modalWidth('md')
                ->modalAutofocus(false)
                ->createAnother(false),
        ];
    }
}
