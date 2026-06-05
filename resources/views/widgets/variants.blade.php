<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-arrow-path-rounded-square">
        <x-slot name="heading">
            A/B Testing Variants
        </x-slot>

        <div class="space-y-4 mt-2">
            @foreach ($visitsByVariant as $variant => $count)
                @php 
                    $pct = $totalVisits > 0 ? round(($count / $totalVisits) * 100, 1) : 0;
                    $expectedWeight = null;
                    if (is_array($rotationVariants)) {
                        foreach ($rotationVariants as $v) {
                            if (($v['label'] ?? '') === $variant) {
                                $expectedWeight = $v['weight'] ?? null;
                                break;
                            }
                        }
                    }
                @endphp
                <div class="group cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 -mx-2 px-2 py-1.5 rounded-lg transition-colors" x-on:click="$wire.dispatch('set-stats-filter', { key: 'selected_variant', value: '{{ addslashes($variant) }}' })">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $variant }}</span>
                        <span class="font-mono text-xs font-semibold text-gray-900 dark:text-white">
                            {{ number_format($count) }} 
                            <span class="text-gray-400 dark:text-gray-500">
                                ({{ $pct }}%{{ $expectedWeight !== null ? ' vs expected ' . $expectedWeight . '%' : '' }})
                            </span>
                        </span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div class="h-full rounded-full bg-indigo-500 transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
