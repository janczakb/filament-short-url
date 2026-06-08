@php
    use Bjanczak\FilamentShortUrl\FilamentShortUrlPlugin;

    $version = FilamentShortUrlPlugin::version();
@endphp

<footer class="filamentshortUrl-panel-footer" aria-label="Plugin footer">
    <div class="filamentshortUrl-panel-footer__card">
        <span class="filamentshortUrl-panel-footer__title">Filament Short URL</span>
        <span class="filamentshortUrl-panel-footer__version">v{{ $version }}</span>
        <span class="filamentshortUrl-panel-footer__sep" aria-hidden="true">·</span>
        <span class="filamentshortUrl-panel-footer__meta">
            {{ FilamentShortUrlPlugin::POWERED_BY_LABEL }}
            <a
                href="{{ FilamentShortUrlPlugin::AUTHOR_URL }}"
                target="_blank"
                rel="noopener noreferrer"
                class="filamentshortUrl-panel-footer__link"
            >
                {{ FilamentShortUrlPlugin::AUTHOR_HANDLE }}
            </a>
        </span>
        <span class="filamentshortUrl-panel-footer__sep" aria-hidden="true">·</span>
        <a
            href="{{ FilamentShortUrlPlugin::PACKAGE_URL }}"
            target="_blank"
            rel="noopener noreferrer"
            class="filamentshortUrl-panel-footer__link filamentshortUrl-panel-footer__package"
        >
            {{ FilamentShortUrlPlugin::PACKAGE_NAME }}
        </a>
    </div>
</footer>
