<?php

namespace Bjanczak\FilamentShortUrl\Assets;

use Filament\Support\Assets\Css;
use Throwable;

class ShortUrlCss extends Css
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
