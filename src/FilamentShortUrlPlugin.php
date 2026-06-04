<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlPixelResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\ShortUrlSettingsPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentShortUrlPlugin implements Plugin
{
    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected ?string $navigationLabel = null;

    protected ?string $navigationIcon = null;

    protected ?string $routePrefix = null;

    protected ?\Closure $authorizeSettingsUsing = null;

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
                ShortUrlPixelResource::class,
            ])
            ->pages([
                ShortUrlSettingsPage::class,
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
     *
     * @example FilamentShortUrlPlugin::make()->navigationSort(50)
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

    /**
     * Override the navigation label (menu item name).
     *
     * @example FilamentShortUrlPlugin::make()->navigationLabel('Short Links')
     */
    public function navigationLabel(string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel;
    }

    /**
     * Override the navigation icon.
     *
     * @example FilamentShortUrlPlugin::make()->navigationIcon('heroicon-o-link')
     */
    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): ?string
    {
        return $this->navigationIcon;
    }

    /**
     * Set a custom callback to authorize access to the Short URL settings page.
     *
     * @example FilamentShortUrlPlugin::make()->authorizeSettingsUsing(fn () => auth()->user()->hasRole('admin'))
     */
    public function authorizeSettingsUsing(\Closure $callback): static
    {
        $this->authorizeSettingsUsing = $callback;

        return $this;
    }

    public function getAuthorizeSettingsUsing(): ?\Closure
    {
        return $this->authorizeSettingsUsing;
    }
}
