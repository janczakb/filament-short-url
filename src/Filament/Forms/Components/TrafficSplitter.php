<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class TrafficSplitter extends Field
{
    protected string $view = 'filament-short-url::forms.components.traffic-splitter';

    protected string $target = 'rotation_variants';

    public function target(string $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function getTargetStatePath(): string
    {
        $containerPath = $this->getContainer()->getStatePath();

        return $containerPath ? $containerPath.'.'.$this->target : $this->target;
    }
}
