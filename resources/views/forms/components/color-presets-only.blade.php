<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $targetName = str_replace('_presets', '', $field->getName());
        $targetStatePath = str_replace($field->getName(), $targetName, $getStatePath());
    @endphp

    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$targetStatePath}')") }},
            presets: ['#000000', '#dc2626', '#ea580c', '#ec4899', '#f59e0b', '#16a34a', '#2563eb', '#7c3aed'],
            get activePreset() {
                if (!this.state) return null;
                const normalizedState = this.state.toLowerCase();
                return this.presets.find(p => p.toLowerCase() === normalizedState) || null;
            }
        }"
        class="flex items-center gap-2"
    >
        <template x-for="color in presets" :key="color">
            <button type="button"
                x-on:click="state = color"
                :style="{ backgroundColor: color }"
                class="w-7 h-7 rounded-full border border-neutral-200 dark:border-neutral-800 shadow-sm flex items-center justify-center cursor-pointer transition-all hover:scale-105 relative focus:outline-none"
                :class="activePreset === color ? 'ring-2 ring-neutral-950 dark:ring-white ring-offset-2 dark:ring-offset-neutral-900' : ''"
                :title="color"
            >
                <!-- Checkmark icon visible when selected -->
                <template x-if="activePreset === color">
                    <svg class="w-3.5 h-3.5" :class="color.toLowerCase() === '#f59e0b' ? 'text-black' : 'text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
            </button>
        </template>
    </div>
</x-dynamic-component>
