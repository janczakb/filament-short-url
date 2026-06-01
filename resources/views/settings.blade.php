<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" icon="heroicon-o-check">
                {{ __('filament-short-url::default.settings_save_btn') }}
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
