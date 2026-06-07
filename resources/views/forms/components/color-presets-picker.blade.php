<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            presets: ['#000000', '#dc2626', '#ea580c', '#ec4899', '#f59e0b', '#16a34a', '#2563eb', '#7c3aed'],
            get activePreset() {
                if (!this.state) return null;
                const normalizedState = this.state.toLowerCase();
                return this.presets.find(p => p.toLowerCase() === normalizedState) || null;
            }
        }"
        class="flex flex-row items-center gap-4 mt-2"
    >
        <!-- Custom Color Input Box -->
        <div class="relative flex items-center border border-neutral-300 dark:border-neutral-700 rounded-lg overflow-hidden bg-white dark:bg-neutral-900 shadow-sm" style="width: 145px; height: 36px;">
            <!-- Color block (left) -->
            <div :style="{ backgroundColor: state || '#000000' }" class="w-10 h-full cursor-pointer relative flex-shrink-0 border-r border-neutral-300 dark:border-neutral-700">
                <input type="color" x-model="state" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer" />
            </div>
            <!-- Hex text input (right) -->
            <input type="text" x-model="state" placeholder="#000000" class="w-full h-full px-2 text-xs bg-transparent border-none outline-none focus:ring-0 text-neutral-800 dark:text-neutral-200 text-center font-mono" />
        </div>

        <!-- Presets Row -->
        <div class="flex items-center gap-2">
            <template x-for="color in presets" :key="color">
                <button type="button"
                    x-on:click="state = color"
                    :style="{ backgroundColor: color }"
                    class="w-6.5 h-6.5 rounded-full border border-neutral-200 dark:border-neutral-800 shadow-sm flex items-center justify-center cursor-pointer transition-all hover:scale-105 relative focus:outline-none"
                    :class="activePreset === color ? 'ring-2 ring-neutral-950 dark:ring-white ring-offset-2 dark:ring-offset-neutral-900' : ''"
                    :title="color"
                >
                    <!-- Checkmark icon visible when selected -->
                    <template x-if="activePreset === color">
                        <svg class="w-3 h-3" :class="color.toLowerCase() === '#f59e0b' ? 'text-black' : 'text-white'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </button>
            </template>
        </div>
    </div>
</x-dynamic-component>
