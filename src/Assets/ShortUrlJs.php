<?php

namespace Bjanczak\FilamentShortUrl\Assets;

use Filament\Support\Assets\Js;
use Throwable;

class ShortUrlJs extends Js
{
    public function getVersion(): string
    {
        try {
            $path = $this->getPublicPath();
            if (file_exists($path)) {
                return (string) filemtime($path);
            }
        } catch (Throwable $exception) {
        }

        return parent::getVersion();
    }
}
