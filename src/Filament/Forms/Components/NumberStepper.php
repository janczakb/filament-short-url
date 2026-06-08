<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Contracts\CanHaveNumericState;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\NumberStateCast;

class NumberStepper extends Field implements CanHaveNumericState
{
    protected string $view = 'filament-short-url::forms.components.number-stepper';

    /**
     * @var scalar | Closure | null
     */
    protected $minValue = null;

    /**
     * @var scalar | Closure | null
     */
    protected $maxValue = null;

    protected int|float|Closure $step = 1;

    protected bool|Closure $isInteger = true;

    protected bool|Closure $isNullable = false;

    protected string|Closure $variant = 'primary';

    protected string|Closure $size = 'md';

    protected string|Closure|null $displaySuffix = null;

    protected string|Closure|null $nullLabel = null;

    /**
     * @param  scalar | Closure | null  $value
     */
    public function minValue($value): static
    {
        $this->minValue = $value;

        $this->rule(static function (NumberStepper $component): string {
            $value = $component->getMinValue();

            return "min:{$value}";
        }, static fn (NumberStepper $component): bool => filled($component->getMinValue()) && ! $component->isNullable());

        return $this;
    }

    /**
     * @param  scalar | Closure | null  $value
     */
    public function maxValue($value): static
    {
        $this->maxValue = $value;

        $this->rule(static function (NumberStepper $component): string {
            $value = $component->getMaxValue();

            return "max:{$value}";
        }, static fn (NumberStepper $component): bool => filled($component->getMaxValue()));

        return $this;
    }

    public function step(int|float|Closure $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function integer(bool|Closure $condition = true): static
    {
        $this->isInteger = $condition;

        $this->rule('integer', $condition);

        return $this;
    }

    public function nullable(bool|Closure $condition = true): static
    {
        $this->isNullable = $condition;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function size(string|Closure $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function suffix(string|Closure|null $suffix): static
    {
        $this->displaySuffix = $suffix;

        return $this;
    }

    public function nullLabel(string|Closure|null $label): static
    {
        $this->nullLabel = $label;

        return $this;
    }

    /**
     * @return scalar | null
     */
    public function getMinValue(): mixed
    {
        return $this->evaluate($this->minValue);
    }

    /**
     * @return scalar | null
     */
    public function getMaxValue(): mixed
    {
        return $this->evaluate($this->maxValue);
    }

    public function getStep(): int|float
    {
        return $this->evaluate($this->step);
    }

    public function isInteger(): bool
    {
        return (bool) $this->evaluate($this->isInteger);
    }

    public function isNullable(): bool
    {
        return (bool) $this->evaluate($this->isNullable);
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    public function getSize(): string
    {
        return $this->evaluate($this->size);
    }

    public function getDisplaySuffix(): ?string
    {
        return $this->evaluate($this->displaySuffix);
    }

    public function getNullLabel(): ?string
    {
        return $this->evaluate($this->nullLabel);
    }

    public function isNumeric(): bool
    {
        return true;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(NumberStateCast::class, ['isNullable' => $this->isNullable()]),
        ];
    }
}
