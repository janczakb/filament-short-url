<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SegmentControl extends Field
{
    protected string $view = 'filament-short-url::forms.components.segment-control';

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $options = [];

    /**
     * @var array<string | int, string> | Closure
     */
    protected array|Closure $icons = [];

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $disabledOptions = [];

    protected string|Closure $size = 'md';

    protected string|Closure $variant = 'default';

    protected bool|Closure $hasSeparators = true;

    protected bool|Closure $isFullWidth = false;

    protected bool|Closure $isIconOnly = false;

    protected bool|Closure $expandSelectedLabel = false;

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  array<string | int, string> | Closure  $icons
     */
    public function icons(array|Closure $icons): static
    {
        $this->icons = $icons;

        return $this;
    }

    /**
     * @param  array<string | int> | Closure  $keys
     */
    public function disabledOptions(array|Closure $keys): static
    {
        $this->disabledOptions = $keys;

        return $this;
    }

    public function size(string|Closure $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function separators(bool|Closure $condition = true): static
    {
        $this->hasSeparators = $condition;

        return $this;
    }

    public function fullWidth(bool|Closure $condition = true): static
    {
        $this->isFullWidth = $condition;

        return $this;
    }

    public function iconOnly(bool|Closure $condition = true): static
    {
        $this->isIconOnly = $condition;

        return $this;
    }

    public function expandSelectedLabel(bool|Closure $condition = true): static
    {
        $this->expandSelectedLabel = $condition;

        return $this;
    }

    /**
     * @return array<int, string|int>
     */
    public function getOptionKeys(): array
    {
        return array_keys($this->getNormalizedOptions());
    }

    /**
     * @return array<string|int, array{label: string, icon: ?string, disabled: bool, tooltip: ?string}>
     */
    public function getNormalizedOptions(): array
    {
        $icons = $this->getIcons();
        $disabledOptions = collect($this->getDisabledOptions())->map(fn ($key) => (string) $key);

        $normalized = [];

        foreach ($this->evaluate($this->options) as $value => $option) {
            $key = is_int($value) ? $value : (string) $value;

            if (is_string($option)) {
                $normalized[$key] = [
                    'label' => $option,
                    'icon' => $icons[$key] ?? $icons[$value] ?? null,
                    'disabled' => $disabledOptions->contains((string) $key),
                    'tooltip' => null,
                ];

                continue;
            }

            if (is_array($option)) {
                $normalized[$key] = [
                    'label' => (string) ($option['label'] ?? $key),
                    'icon' => $option['icon'] ?? $icons[$key] ?? $icons[$value] ?? null,
                    'disabled' => (bool) ($option['disabled'] ?? false) || $disabledOptions->contains((string) $key),
                    'tooltip' => filled($option['tooltip'] ?? null) ? (string) $option['tooltip'] : null,
                ];
            }
        }

        return $normalized;
    }

    /**
     * @return array<string|int, string>
     */
    public function getIcons(): array
    {
        return $this->evaluate($this->icons);
    }

    /**
     * @return array<string|int>
     */
    public function getDisabledOptions(): array
    {
        return Arr::wrap($this->evaluate($this->disabledOptions));
    }

    public function getSize(): string
    {
        return $this->evaluate($this->size);
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    public function hasSeparators(): bool
    {
        return (bool) $this->evaluate($this->hasSeparators);
    }

    public function isFullWidth(): bool
    {
        return (bool) $this->evaluate($this->isFullWidth);
    }

    public function isIconOnly(): bool
    {
        return (bool) $this->evaluate($this->isIconOnly);
    }

    public function shouldExpandSelectedLabel(): bool
    {
        return (bool) $this->evaluate($this->expandSelectedLabel);
    }

    public function isOptionDisabled(string|int $key): bool
    {
        return $this->getNormalizedOptions()[(string) $key]['disabled'] ?? true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (SegmentControl $component): string {
            return Rule::in($component->getOptionKeys());
        });
    }
}
