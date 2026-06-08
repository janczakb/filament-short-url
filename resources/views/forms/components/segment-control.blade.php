@php
    use Illuminate\Support\Js;

    $statePath = $getStatePath();
    $options = $getNormalizedOptions();
    $optionKeys = array_keys($options);
    $size = $getSize();
    $variant = $getVariant();
    $hasSeparators = $hasSeparators();
    $isFullWidth = $isFullWidth();
    $isIconOnly = $isIconOnly();
    $expandSelectedLabel = $shouldExpandSelectedLabel();
    $isDisabled = $isDisabled();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            optionKeys: {{ Js::from(array_values($optionKeys)) }},
            disabledOptions: {{ Js::from(collect($options)->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])->all()) }},
            separators: @js($hasSeparators),
            disabled: @js($isDisabled),
            indicatorStyle: '',
            resizeObserver: null,
            normalize(value) {
                return value === null || value === undefined ? null : String(value);
            },
            isSelected(value) {
                return this.normalize(this.state) === this.normalize(value);
            },
            isOptionDisabled(value) {
                return this.disabledOptions[this.normalize(value)] ?? false;
            },
            canSelect(value) {
                return ! this.disabled && ! this.isOptionDisabled(value);
            },
            select(value) {
                if (! this.canSelect(value)) {
                    return;
                }

                this.state = value;
                this.$nextTick(() => this.updateIndicator());
            },
            selectedIndex() {
                const current = this.normalize(this.state);

                return this.optionKeys.findIndex((key) => this.normalize(key) === current);
            },
            showSeparator(separatorIndex) {
                if (! this.separators) {
                    return false;
                }

                const selectedIndex = this.selectedIndex();

                if (selectedIndex === -1) {
                    return true;
                }

                return separatorIndex !== selectedIndex - 1 && separatorIndex !== selectedIndex;
            },
            updateIndicator() {
                const track = this.$refs.track;
                if (! track) {
                    return;
                }

                const selected = track.querySelector('[data-segment-selected=true]');
                if (! selected) {
                    this.indicatorStyle = 'opacity: 0;';

                    return;
                }

                this.indicatorStyle =
                    'width: ' + selected.offsetWidth + 'px;' +
                    'height: ' + selected.offsetHeight + 'px;' +
                    'transform: translate3d(' + selected.offsetLeft + 'px, ' + selected.offsetTop + 'px, 0);' +
                    'opacity: 1;';
            },
            init() {
                this.$watch('state', () => this.$nextTick(() => this.updateIndicator()));
                this.$nextTick(() => this.updateIndicator());

                if (typeof ResizeObserver === 'undefined') {
                    return;
                }

                this.resizeObserver = new ResizeObserver(() => this.updateIndicator());
                this.resizeObserver.observe(this.$refs.track);
            },
        }"
        x-init="init()"
        @class([
            'fsu-segment-control',
            'w-full' => $isFullWidth,
            'opacity-60 pointer-events-none' => $isDisabled,
        ])
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
    >
        <div
            x-ref="track"
            @class([
                'fsu-segment-track',
                'fsu-segment-track--'.$size,
                'fsu-segment-track--ghost' => $variant === 'ghost',
            ])
        >
            <div
                x-ref="indicator"
                aria-hidden="true"
                @class([
                    'fsu-segment-indicator',
                    'fsu-segment-indicator--ghost' => $variant === 'ghost',
                ])
                :style="indicatorStyle"
            ></div>

            @foreach ($options as $value => $option)
                @if (! $loop->first && $hasSeparators)
                    <span
                        x-show="showSeparator({{ $loop->index - 1 }})"
                        x-cloak
                        class="fsu-segment-separator"
                        aria-hidden="true"
                    ></span>
                @endif

                <button
                    type="button"
                    role="radio"
                    @class([
                        'fsu-segment-item',
                        'fsu-segment-item--'.$size,
                    ])
                    data-segment-value="{{ $value }}"
                    x-bind:data-segment-selected="isSelected(@js($value)) ? 'true' : 'false'"
                    x-bind:aria-checked="isSelected(@js($value)) ? 'true' : 'false'"
                    x-bind:disabled="disabled || isOptionDisabled(@js($value))"
                    x-on:click="select(@js($value))"
                    @if (filled($option['tooltip'] ?? null))
                        x-tooltip="{ content: @js($option['tooltip']), theme: $store.theme }"
                    @endif
                >
                    @if ($option['icon'])
                        <x-filament::icon :icon="$option['icon']" />
                    @endif

                    @if ($isIconOnly)
                        <span class="sr-only">{{ $option['label'] }}</span>
                    @elseif ($expandSelectedLabel)
                        <span
                            x-show="isSelected(@js($value))"
                            x-cloak
                        >{{ $option['label'] }}</span>
                    @else
                        <span>{{ $option['label'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
