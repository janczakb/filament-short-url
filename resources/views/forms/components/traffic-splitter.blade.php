<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getTargetStatePath()}')") }},
            get items() {
                if (!this.state) return [];
                if (Array.isArray(this.state)) {
                    return this.state;
                }
                return Object.entries(this.state).map(([key, value]) => ({
                    key,
                    ...value
                }));
            },
            get totalSum() {
                return this.items.reduce((sum, item) => sum + parseInt(item.weight || 0), 0);
            },
            init() {
                this.$watch('state', () => {
                    this.checkAndNormalize();
                });
                this.checkAndNormalize();
            },
            checkAndNormalize() {
                const items = this.items;
                if (items.length < 2) return;
                const sum = items.reduce((s, i) => s + parseInt(i.weight || 0), 0);
                if (sum !== 100) {
                    const m = items.length;
                    const base = Math.floor(100 / m);
                    const remainder = 100 % m;
                    
                    // Re-balance equally using 1% resolution
                    items.forEach((item, index) => {
                        const newWeight = base + (index < remainder ? 1 : 0);
                        this.updateWeight(item, newWeight);
                    });
                }
            },
            updateWeight(item, newWeight) {
                if (Array.isArray(this.state)) {
                    const index = this.state.findIndex(i => i === item || (i.key && i.key === item.key));
                    if (index !== -1) {
                        this.state[index].weight = newWeight;
                    }
                } else if (this.state && typeof this.state === 'object') {
                    if (this.state[item.key]) {
                        this.state[item.key].weight = newWeight;
                    }
                }
            },
            startDrag(index, event) {
                event.preventDefault();
                const isTouch = event.type.startsWith('touch');
                const startX = isTouch ? event.touches[0].clientX : event.clientX;
                
                const rect = this.$refs.container.getBoundingClientRect();
                const containerWidth = rect.width;
                const containerLeft = rect.left;
                
                if (!isTouch) {
                    document.body.style.cursor = 'col-resize';
                }
                
                // Get cumulative weights up to index
                let sumBefore = 0;
                const items = this.items;
                for (let j = 0; j < index; j++) {
                    sumBefore += parseInt(items[j].weight || 0);
                }
                
                // Total sum of current and next segment
                const segmentSum = parseInt(items[index].weight || 0) + parseInt(items[index+1].weight || 0);
                
                const onMove = (e) => {
                    const currentX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
                    const offsetPercent = ((currentX - containerLeft) / containerWidth) * 100;
                    
                    const minWeight = 1;
                    const minPercent = sumBefore + minWeight;
                    const maxPercent = sumBefore + segmentSum - minWeight;
                    
                    let targetPercent = Math.round(offsetPercent);
                    targetPercent = Math.max(minPercent, Math.min(maxPercent, targetPercent));
                    
                    const newWeightCurrent = targetPercent - sumBefore;
                    const newWeightNext = segmentSum - newWeightCurrent;
                    
                    this.updateWeight(items[index], newWeightCurrent);
                    this.updateWeight(items[index+1], newWeightNext);
                };
                
                const onEnd = () => {
                    if (!isTouch) {
                        document.body.style.cursor = '';
                    }
                    if (isTouch) {
                        window.removeEventListener('touchmove', onMove);
                        window.removeEventListener('touchend', onEnd);
                    } else {
                        window.removeEventListener('mousemove', onMove);
                        window.removeEventListener('mouseup', onEnd);
                    }
                };
                
                if (isTouch) {
                    window.addEventListener('touchmove', onMove, { passive: true });
                    window.addEventListener('touchend', onEnd);
                } else {
                    window.addEventListener('mousemove', onMove);
                    window.addEventListener('mouseup', onEnd);
                }
            }
        }"
        class="traffic-splitter-component mt-2"
    >

        <div
            x-ref="container"
            class="relative h-10 w-full select-none"
            x-show="items.length >= 2"
        >
            <div class="absolute inset-0 flex h-full">
                <template x-for="(item, index) in items" :key="item.key || index">
                    <!-- Segment container -->
                    <div
                        class="@container pointer-events-none relative flex h-full"
                        :style="'width: ' + item.weight + '%;'"
                    >
                        <!-- Left Spacer (only if not first item) -->
                        <div class="w-1.5" x-show="index > 0"></div>

                        <!-- Segment Box -->
                        <div class="traffic-splitter-segment flex h-full grow items-center justify-center gap-2 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-xs select-none">
                            <span class="text-xs font-semibold text-neutral-900 dark:text-neutral-100" x-text="index + 1"></span>
                            <span class="font-medium text-neutral-600 dark:text-neutral-400" x-show="item.weight >= 12" x-text="item.weight + '%'"></span>
                        </div>
                        
                        <!-- Right Spacer (only if not last item) -->
                        <div class="w-1.5" x-show="index < items.length - 1"></div>
                        
                        <!-- Drag Handle (visible only if not the last item) -->
                        <div
                            x-show="index < items.length - 1"
                            class="group pointer-events-auto absolute -right-1.5 flex h-full w-3 cursor-col-resize items-center px-1 z-30"
                            @mousedown.stop="startDrag(index, $event)"
                            @touchstart.stop="startDrag(index, $event)"
                        >
                            <div class="h-2/3 w-1 rounded-full bg-neutral-300 dark:bg-neutral-600 group-hover:bg-neutral-400 dark:group-hover:bg-neutral-400 group-active:bg-neutral-500 transition-colors duration-150"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>
