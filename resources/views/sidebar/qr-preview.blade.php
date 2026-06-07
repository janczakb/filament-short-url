<div
    wire:ignore 
    class="sidebar-qr-container"
    @qr-design-updated.window="const _d = Array.isArray($event.detail) ? $event.detail[0] : $event.detail; applyQrDesign(_d?.options, _d?.logo)"
    x-data="shortUrlQrPreview({
        options: @js($opts),
        logo: @js($currentLogo),
        domains: @js($domains),
        defaultDomain: @js($defaultDomain),
        routePrefix: @js(config('filament-short-url.route_prefix')),
        defaultUrlKey: @js($record ? $record->url_key : ''),
        protocol: @js(app()->isProduction() ? 'https' : (parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https'))
    })"
>


    {{-- Label row matching "Folder" label style --}}
    <span class="fi-fo-field-wrp-label flex items-center gap-x-3 mb-1.5">
        <label class="fi-label text-sm font-semibold leading-6 text-gray-950 dark:text-white">
            {{ __('filament-short-url::default.action_qr') }}
        </label>
    </span>

    {{-- Dotted Box Wrapper --}}
    <div class="w-full h-[94px] border border-neutral-200 dark:border-neutral-800 rounded-xl relative overflow-hidden qr-preview-dots-bg flex items-center justify-center">
        <!-- QR Code Canvas in the middle (64x64px) -->
        <div x-ref="sidebarQrCanvas" class="w-[64px] h-[64px] flex items-center justify-center bg-white rounded-lg p-1 shadow-sm border border-neutral-100 dark:border-neutral-800/60 overflow-hidden"></div>

        {{-- Edit Button — positioned via inline style to guarantee correct offset --}}
        @if ($getAction('designQr'))
            {{ $getAction('designQr') }}
        @endif
    </div>
</div>