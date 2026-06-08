<?php

/**
 * @author     Bartek Janczak <barek122@gmail.com>
 * @copyright  2026 Bartek Janczak
 * @license    Custom Source-Available License (see LICENSE file)
 */

namespace Bjanczak\FilamentShortUrl;

use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlCustomDomainResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlFolderResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlPixelResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlResource\Pages\ShortUrlSettingsPage;
use Bjanczak\FilamentShortUrl\Filament\Resources\ShortUrlTagResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class FilamentShortUrlPlugin implements Plugin
{
    public const PACKAGE_NAME = 'janczakb/filament-short-url';

    public const PACKAGE_URL = 'https://github.com/janczakb/filament-short-url';

    public const AUTHOR_HANDLE = 'bjanczak';

    public const AUTHOR_URL = 'https://github.com/janczakb';

    public const POWERED_BY_LABEL = 'Powered by';

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
                ShortUrlCustomDomainResource::class,
                ShortUrlFolderResource::class,
                ShortUrlTagResource::class,
            ])
            ->pages([
                ShortUrlSettingsPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_END,
            fn (): string => self::renderFooter(),
        );
    }

    public static function renderFooter(): string
    {
        if (! self::shouldShowPluginFooter()) {
            return '';
        }

        return View::make('filament-short-url::panel.footer')->render();
    }

    public static function version(): string
    {
        static $version = null;

        if ($version !== null) {
            return $version;
        }

        $composerPath = dirname(__DIR__).'/composer.json';

        if (! is_readable($composerPath)) {
            return $version = 'dev';
        }

        $decoded = json_decode((string) file_get_contents($composerPath), true);

        return $version = is_array($decoded) && isset($decoded['version'])
            ? (string) $decoded['version']
            : 'dev';
    }

    public static function shouldShowPluginFooter(?Request $request = null): bool
    {
        if (! config('filament-short-url.enabled', true)) {
            return false;
        }

        $request ??= request();

        if ($request === null) {
            return false;
        }

        foreach (self::pluginPathPrefixes() as $prefix) {
            if (self::pathMatchesPluginPrefix(trim($request->path(), '/'), $prefix)) {
                return true;
            }
        }

        $routeName = $request->route()?->getName();

        if (! is_string($routeName)) {
            return false;
        }

        return str_starts_with($routeName, 'filament.')
            && str_contains($routeName, 'short-url');
    }

    /**
     * @return list<string>
     */
    protected static function pluginPathPrefixes(): array
    {
        return [
            'short-urls',
            'short-url-pixels',
            'short-url-custom-domains',
            'short-url-folders',
            'short-url-tags',
            'short-url-settings',
        ];
    }

    protected static function pathMatchesPluginPrefix(string $path, string $prefix): bool
    {
        return (bool) preg_match('#(?:^|/)'.preg_quote($prefix, '#').'(?:/|$)#', $path);
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
