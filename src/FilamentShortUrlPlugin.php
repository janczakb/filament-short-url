<?php

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentShortUrlPlugin implements Plugin
{
    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected ?string $routePrefix = null;

    // ─── Factory ─────────────────────────────────────────────────────────────

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    // ─── Plugin interface ────────────────────────────────────────────────────

    public function getId(): string
    {
        return 'filament-short-url';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                ShortUrlResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Configuration is consumed by ShortUrlResource via FilamentShortUrlPlugin::get()
    }

    // ─── Fluent configuration API ────────────────────────────────────────────

    /**
     * Set the navigation group for the Short URLs resource.
     *
     * @example FilamentShortUrlPlugin::make()->navigationGroup('Marketing')
     */
    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    /**
     * Set the navigation sort order.
     */
    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }
}
