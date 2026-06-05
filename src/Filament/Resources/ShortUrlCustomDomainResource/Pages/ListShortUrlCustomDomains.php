<?php

namespace Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlCustomDomainResource\Pages;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlCustomDomainResource;
use Bjanczak\FilamentShortUrl\Models\ShortUrlCustomDomain;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Livewire\Attributes\On;

class ListShortUrlCustomDomains extends ManageRecords
{
    protected static string $resource = ShortUrlCustomDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('filament-short-url::default.empty_state_domain_action'))
                ->modalHeading(__('filament-short-url::default.empty_state_domain_action'))
                ->icon('heroicon-o-plus')
                ->size('sm')
                ->color('primary')
                ->modalWidth('md')
                ->modalAutofocus(false)
                ->closeModalByClickingAway(false)
                ->after(function ($livewire, $record) {
                    $livewire->dispatch('mount-dns-setup-modal', recordId: $record->id);
                }),

            Action::make('dns_setup_modal')
                ->modalHeading(function (array $arguments) {
                    $record = ShortUrlCustomDomain::find($arguments['record'] ?? null);

                    return $record ? __('filament-short-url::default.dns_setup_title', ['domain' => $record->domain]) : '';
                })
                ->modalWidth('2xl')
                ->modalContent(function (array $arguments) {
                    $record = ShortUrlCustomDomain::find($arguments['record'] ?? null);

                    return $record ? view('filament-short-url::table.dns-guide-panel', ['record' => $record]) : null;
                })
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->closeModalByClickingAway(false)
                ->extraAttributes(['class' => 'hidden']),
        ];
    }

    #[On('mount-dns-setup-modal')]
    public function openDnsSetupModal(int $recordId): void
    {
        $this->mountAction('dns_setup_modal', ['record' => $recordId]);
    }
}
