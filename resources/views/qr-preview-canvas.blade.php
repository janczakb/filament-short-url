<div
    x-data="shortUrlQrDesignerPreview({
        url: @js($shortUrl)
    })"
    class="qr-preview-sticky"
>
    <div class="qr-preview-card">
        <div style="width:100%;display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-shrink:0">
            <span style="font-size:12px;font-weight:700;color:#a1a1aa;text-transform:uppercase;letter-spacing:0.05em">{{ __('filament-short-url::default.qr_label_live_preview') }}</span>
            <div style="display:flex;gap:6px">
                <button type="button" x-on:click="download('png')" class="qr-dl-btn" style="padding:4px 10px;font-size:11px;border-radius:6px">
                    {{ __('filament-short-url::default.qr_label_png') }}
                </button>
                <button type="button" x-on:click="download('svg')" class="qr-dl-btn" style="padding:4px 10px;font-size:11px;border-radius:6px">
                    {{ __('filament-short-url::default.qr_label_svg') }}
                </button>
            </div>
        </div>

        <div style="flex:1;display:flex;align-items:center;justify-content:center;width:100%">
            <div :class="bgTransparent ? 'qr-checker' : ''"
                 :style="{
                    width: '100%',
                    maxWidth: '280px',
                    aspectRatio: '1/1',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    borderRadius: '12px',
                    border: '1px solid #e5e7eb',
                    padding: '24px',
                    position: 'relative',
                    background: bgTransparent ? '' : bgColor,
                 }">
                {{-- Spinner --}}
                <div x-show="!qrInstance"
                     style="width:32px;height:32px;border:3px solid #e5e7eb;
                            border-top-color:#6366f1;border-radius:50%;
                            animation:qr-spin 0.8s linear infinite">
                </div>
                {{-- QR canvas --}}
                <div wire:ignore x-ref="qrCanvas" class="qr-preview-wrapper" style="line-height:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center"></div>
            </div>
        </div>

        <p class="mt-3 text-center font-mono" style="font-size:11px;color:#71717a;width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:14px;flex-shrink:0">
            <span x-text="url"></span>
        </p>
    </div>
</div>
