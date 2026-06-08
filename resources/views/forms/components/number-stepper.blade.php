@php
    $statePath = $getStatePath();
    $minValue = $getMinValue();
    $maxValue = $getMaxValue();
    $step = $getStep();
    $isInteger = $isInteger();
    $isNullable = $isNullable();
    $isDisabled = $isDisabled();
    $variant = $getVariant();
    $size = $getSize();
    $displaySuffix = $getDisplaySuffix();
    $nullLabel = $getNullLabel() ?? '—';

    $containerClasses = match ($size) {
        'sm' => 'h-8 gap-0.5 p-0.5',
        'lg' => 'h-12 gap-1.5 p-1.5',
        default => 'h-10 gap-1 p-1',
    };

    $buttonSizeClasses = match ($size) {
        'sm' => 'size-7',
        'lg' => 'size-10',
        default => 'size-8',
    };

    $valueClasses = match ($size) {
        'sm' => 'min-w-14 px-1 text-xs',
        'lg' => 'min-w-20 px-2 text-base',
        default => 'min-w-16 px-1.5 text-sm',
    };

    $iconClasses = match ($size) {
        'sm' => 'size-3',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $buttonVariantClasses = match ($variant) {
        'secondary' => 'bg-primary-50 text-primary-600 shadow-sm hover:bg-primary-100 disabled:opacity-40 dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20',
        'tertiary' => 'bg-gray-200/80 text-gray-700 shadow-sm hover:bg-gray-300/80 disabled:opacity-40 dark:bg-white/10 dark:text-gray-200 dark:hover:bg-white/15',
        'outline' => 'border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-40 dark:border-white/20 dark:bg-transparent dark:text-gray-200 dark:hover:bg-white/5',
        default => 'bg-primary-600 text-white shadow-sm hover:bg-primary-500 disabled:opacity-40 dark:bg-primary-500 dark:hover:bg-primary-400',
    };
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            min: @js($minValue),
            max: @js($maxValue),
            step: @js($step),
            integer: @js($isInteger),
            nullable: @js($isNullable),
            disabled: @js($isDisabled),
            nullLabel: @js($nullLabel),
            suffix: @js($displaySuffix),
            normalize(value) {
                if (value === null || value === '') {
                    return null;
                }

                const numeric = Number(value);

                if (Number.isNaN(numeric)) {
                    return null;
                }

                return this.integer ? Math.round(numeric) : numeric;
            },
            get numericState() {
                return this.normalize(this.state);
            },
            get hasValue() {
                return this.numericState !== null;
            },
            get displayValue() {
                if (! this.hasValue) {
                    return this.nullLabel;
                }

                return String(this.numericState);
            },
            get canDecrement() {
                if (this.disabled) {
                    return false;
                }

                if (! this.hasValue) {
                    return false;
                }

                if (this.min === null) {
                    return this.nullable || this.numericState > 0;
                }

                if (this.nullable && this.numericState <= this.min) {
                    return true;
                }

                return this.numericState > this.min;
            },
            get canIncrement() {
                if (this.disabled) {
                    return false;
                }

                if (! this.hasValue) {
                    return true;
                }

                if (this.max === null) {
                    return true;
                }

                return this.numericState < this.max;
            },
            decrement() {
                if (! this.canDecrement) {
                    return;
                }

                if (! this.hasValue) {
                    return;
                }

                if (this.nullable && this.min !== null && this.numericState <= this.min) {
                    this.state = null;

                    return;
                }

                let next = this.numericState - this.step;

                if (this.min !== null) {
                    next = Math.max(next, this.min);
                }

                this.state = this.integer ? Math.round(next) : next;
            },
            increment() {
                if (! this.canIncrement) {
                    return;
                }

                let next = this.hasValue
                    ? this.numericState + this.step
                    : (this.min ?? this.step);

                if (this.max !== null) {
                    next = Math.min(next, this.max);
                }

                this.state = this.integer ? Math.round(next) : next;
            },
        }"
        @class([
            'fsu-number-stepper inline-flex w-fit max-w-max shrink-0 justify-self-start items-center rounded-full bg-gray-100 dark:bg-white/10',
            $containerClasses,
            'opacity-60' => $isDisabled,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <button
            type="button"
            @class([
                'inline-flex shrink-0 items-center justify-center rounded-full transition duration-75 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:cursor-not-allowed',
                $buttonSizeClasses,
                $buttonVariantClasses,
            ])
            x-on:click="decrement()"
            x-bind:disabled="! canDecrement"
            x-bind:aria-disabled="! canDecrement"
            aria-label="{{ __('filament-short-url::default.number_stepper_decrease') }}"
        >
            <svg @class([$iconClasses]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M4 10a.75.75 0 0 1 .75-.75h10.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 10Z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            @class([
                'flex items-center justify-center gap-1 font-semibold tabular-nums text-gray-950 dark:text-white',
                $valueClasses,
            ])
        >
            <span x-text="displayValue"></span>
            <span
                x-show="hasValue && suffix"
                x-text="suffix"
                class="font-medium text-gray-500 dark:text-gray-400"
            ></span>
        </div>

        <button
            type="button"
            @class([
                'inline-flex shrink-0 items-center justify-center rounded-full transition duration-75 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:cursor-not-allowed',
                $buttonSizeClasses,
                $buttonVariantClasses,
            ])
            x-on:click="increment()"
            x-bind:disabled="! canIncrement"
            x-bind:aria-disabled="! canIncrement"
            aria-label="{{ __('filament-short-url::default.number_stepper_increase') }}"
        >
            <svg @class([$iconClasses]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
            </svg>
        </button>
    </div>
</x-dynamic-component>
