<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlTagResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ListShortUrlTags extends ManageRecords
{
    protected static string $resource = ShortUrlTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-short-url::default.empty_state_tag_action'))
                ->modalHeading(__('filament-short-url::default.empty_state_tag_action'))
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->modalWidth('md')
                ->modalAutofocus(false)
                ->createAnother(false),
        ];
    }
}
