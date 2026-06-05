<x-filament-widgets::widget>
    <x-filament::section :icon="$icon">
        <x-slot name="heading">
            {{ $heading }}
        </x-slot>

        <div class="flex flex-col items-center justify-center py-12 text-center">
            <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-50 text-gray-400 dark:bg-gray-800/30 dark:text-gray-500">
                <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="h-5 w-5" />
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $message }}
            </p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
