@php
    /** @var \Bjanczak\FilamentShortUrl\Models\ShortUrl|null $record */
    $record = $record ?? (isset($getRecord) && is_callable($getRecord) ? $getRecord() : (isset($component) ? $component->getRecord() : null));
    $shortUrl = $record ? ($record->getShortUrl() . '?source=qr') : (config('app.url').'/s/preview?source=qr');
    $opts = $record ? $record->getQrOptions() : config('filament-short-url.qr_defaults', []);

    $qrOptionsStatePath = 'qr_options';
    $qrLogoStatePath = 'qr_logo';

    if (isset($component) && method_exists($component, 'getStatePath')) {
        $statePath = $component->getStatePath();
        \Illuminate\Support\Facades\Log::info('QR DESIGNER STATE PATH: ' . $statePath);
        if ($statePath) {
            $parts = explode('.', $statePath);
            if (end($parts) === 'qr_designer') {
                array_pop($parts);
                $qrOptionsStatePath = implode('.', array_merge($parts, ['qr_options']));
                $qrLogoStatePath = implode('.', array_merge($parts, ['qr_logo']));
            }
        }
    }
@endphp

<style>
/* Color picker */
.qr-color-picker input[type=color] {
    position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;
}
.qr-margin-top {
    margin-top: 12px !important;
}
.qr-space-y > div {
    margin-top: 12px !important;
}
.qr-space-y > div:first-child {
    margin-top: 0 !important;
}
@keyframes qr-spin { to { transform: rotate(360deg); } }

/* Premium Grid & Panels */
.qr-designer-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 2rem;
    width: 100%;
    align-items: stretch;
}
@media (max-width: 768px) {
    .qr-designer-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

/* Sticky Preview Wrapper */
.qr-preview-sticky {
    position: sticky;
    top: 2rem;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    height: 100%;
}

/* Accordion Styling */
.qr-accordion {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.qr-accordion-item {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease-in-out;
}
.dark .qr-accordion-item {
    background: #18181b;
    border-color: #27272a;
}
.qr-accordion-item.open {
    border-color: #6366f1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.05);
}
.dark .qr-accordion-item.open {
    border-color: #818cf8;
    box-shadow: 0 4px 12px rgba(129, 140, 248, 0.1);
}
.qr-accordion-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    background: transparent;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #4b5563;
    text-align: left;
    transition: color 0.15s;
}
.dark .qr-accordion-header {
    color: #9ca3af;
}
.qr-accordion-header:hover {
    color: #6366f1;
}
.dark .qr-accordion-header:hover {
    color: #818cf8;
}
.qr-accordion-icon {
    width: 18px;
    height: 18px;
    color: #9ca3af;
    transition: color 0.15s;
}
.qr-accordion-item.open .qr-accordion-icon {
    color: #6366f1;
}
.dark .qr-accordion-item.open .qr-accordion-icon {
    color: #818cf8;
}
.qr-accordion-chevron {
    width: 16px;
    height: 16px;
    color: #9ca3af;
    transition: transform 0.2s ease-in-out;
}
.qr-accordion-content {
    padding: 0 16px 16px 16px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
}
.dark .qr-accordion-content {
    border-top-color: #27272a;
    background: #1c1c1f;
}

/* Label styling */
.qr-label {
    font-size: 11px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 6px;
    display: block;
}

/* Dropdowns / Inputs */
.qr-select {
    width: 100%;
    padding: 8px 30px 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #111827;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px;
    cursor: pointer;
    transition: border-color .15s;
}
.qr-select:focus { outline:none; border-color:#6366f1; }
.dark .qr-select { background-color:#18181b; border-color:#27272a; color:#f9fafb; }

.qr-input-num {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #111827;
    transition: border-color .15s;
}
.qr-input-num:focus { outline:none; border-color:#6366f1; }
.dark .qr-input-num { background-color:#18181b; border-color:#27272a; color:#f9fafb; }

/* Premium Segment Control */
.qr-segment-control {
    display: flex;
    background: #f3f4f6;
    border-radius: 8px;
    padding: 3px;
    gap: 2px;
}
.dark .qr-segment-control {
    background: #27272a;
}
.qr-segment-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    background: transparent;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.dark .qr-segment-btn {
    color: #9ca3af;
}
.qr-segment-btn.active {
    background: #ffffff;
    color: #4f46e5;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.dark .qr-segment-btn.active {
    background: #3f3f46;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* iOS-Style Toggle Switch */
.qr-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    height: 22px;
    width: 40px;
    cursor: pointer;
    border-radius: 9999px;
    transition: background-color 0.2s ease;
    flex-shrink: 0;
}
.qr-toggle.on {
    background: #6366f1;
}
.qr-toggle.off {
    background: #d1d5db;
}
.dark .qr-toggle.off {
    background: #4b5563;
}
.qr-toggle-thumb {
    pointer-events: none;
    height: 18px;
    width: 18px;
    border-radius: 50%;
    background: #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease;
    position: absolute;
    left: 2px;
}
.qr-toggle.on .qr-toggle-thumb {
    transform: translateX(18px);
}

/* Drag-and-drop Upload Area */
.qr-upload-zone {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border: 2px dashed #d1d5db;
    border-radius: 10px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    text-align: center;
}
.dark .qr-upload-zone {
    border-color: #3f3f46;
    background: #18181b;
}
.qr-upload-zone:hover, .qr-upload-zone.dragover {
    border-color: #6366f1;
    background: #f5f3ff;
}
.dark .qr-upload-zone:hover, .dark .qr-upload-zone.dragover {
    border-color: #818cf8;
    background: #1e1b4b;
}
.qr-upload-icon {
    width: 28px;
    height: 28px;
    color: #9ca3af;
    margin-bottom: 8px;
    transition: color 0.15s;
}
.qr-upload-zone:hover .qr-upload-icon, .qr-upload-zone.dragover .qr-upload-icon {
    color: #6366f1;
}
.dark .qr-upload-zone:hover .qr-upload-icon, .dark .qr-upload-zone.dragover .qr-upload-icon {
    color: #818cf8;
}
.qr-upload-text {
    font-size: 11px;
    font-weight: 600;
    color: #4b5563;
}
.dark .qr-upload-text {
    color: #d1d5db;
}
.qr-upload-hint {
    font-size: 10px;
    color: #9ca3af;
    margin-top: 2px;
}

/* Accent Color Slider */
.qr-slider-container {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.qr-slider-track-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}
.qr-range-input {
    -webkit-appearance: none;
    appearance: none;
    flex: 1;
    height: 5px;
    border-radius: 9999px;
    background: #e5e7eb;
    outline: none;
    cursor: pointer;
}
.dark .qr-range-input {
    background: #4b5563;
}
.qr-range-input::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #6366f1;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
    transition: transform 0.1s ease;
}
.qr-range-input::-webkit-slider-thumb:hover {
    transform: scale(1.15);
}
.qr-range-input::-moz-range-thumb {
    width: 14px;
    height: 14px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    background: #6366f1;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
    transition: transform 0.1s ease;
}
.qr-range-input::-moz-range-thumb:hover {
    transform: scale(1.15);
}

/* Floating Badge */
.qr-badge {
    padding: 2px 6px;
    background: #eef2ff;
    color: #4f46e5;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
}
.dark .qr-badge {
    background: #1e1b4b;
    color: #a5b4fc;
}

/* Color swatch and hex wrapper */
.qr-color-field {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #ffffff;
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    padding: 4px 8px;
    width: 100%;
}
.dark .qr-color-field {
    background: #18181b;
    border-color: #27272a;
}
.qr-color-swatch {
    position: relative;
    width: 24px;
    height: 24px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    overflow: hidden;
    flex-shrink: 0;
    cursor: pointer;
}
.dark .qr-color-swatch {
    border-color: #3f3f46;
}
.qr-hex-input {
    border: none !important;
    background: transparent !important;
    font-family: monospace;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    padding: 2px 4px !important;
    width: 100%;
    letter-spacing: .03em;
}
.dark .qr-hex-input {
    color: #f9fafb;
}
.qr-hex-input:focus {
    outline: none !important;
    box-shadow: none !important;
}

/* Custom Grid Rows */
.qr-grid-cols-2 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.qr-space-y-4 > * + * {
    margin-top: 16px;
}

/* Preview Card styling */
.qr-preview-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    max-width: none;
    height: 100%;
    transition: all 0.2s ease-in-out;
}
.dark .qr-preview-card {
    background: #18181b;
    border-color: #27272a;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
}

/* Download buttons */
.qr-dl-btn {
    display:inline-flex;align-items:center;gap:5px;padding:6px 14px;
    border-radius:8px;font-size:12px;font-weight:600;
    border:1.5px solid #e5e7eb;background:#fff;color:#374151;
    cursor:pointer;transition:all .15s;
}
.qr-dl-btn:hover { background:#f9fafb;border-color:#d1d5db; }
.dark .qr-dl-btn { background:#374151;border-color:#4b5563;color:#d1d5db; }
.dark .qr-dl-btn:hover { background:#4b5563; }

/* Transparent checker */
.qr-checker {
    background-image:linear-gradient(45deg,#d1d5db 25%,transparent 25%),
        linear-gradient(-45deg,#d1d5db 25%,transparent 25%),
        linear-gradient(45deg,transparent 75%,#d1d5db 75%),
        linear-gradient(-45deg,transparent 75%,#d1d5db 75%);
    background-size:10px 10px;
    background-position:0 0,0 5px,5px -5px,-5px 0;
    background-color:#f3f4f6;
}
.qr-preview-wrapper canvas, .qr-preview-wrapper svg {
    max-width: 100% !important;
    height: 100% !important;
    display: block;
    aspect-ratio: 1/1;
}
</style>

{{-- Preload QR library as soon as the tab renders --}}
<script>
(function(){
    if (window.QRCodeStyling) return;
    var s = document.createElement('script');
    s.src = 'https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js';
    s.onerror = function(){
        var s2 = document.createElement('script');
        s2.src = 'https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js';
        document.head.appendChild(s2);
    };
    document.head.appendChild(s);
})();
</script>

<div
    x-data="{
        url:              @js($shortUrl),
        size:             {{ $opts['size'] ?? 300 }},
        margin:           {{ $opts['margin'] ?? 1 }},
        dotStyle:         @js($opts['dot_style'] ?? 'square'),
        colorMode:        @js($opts['color_mode'] ?? 'solid'),
        fgColor:          @js($opts['foreground_color'] ?? '#000000'),
        gradientFrom:     @js($opts['gradient_from'] ?? '#4f46e5'),
        gradientTo:       @js($opts['gradient_to'] ?? '#06b6d4'),
        gradientType:     @js($opts['gradient_type'] ?? 'linear'),
        bgTransparent:    {{ ($opts['bg_transparent'] ?? false) ? 'true' : 'false' }},
        bgColor:          @js($opts['background_color'] ?? '#ffffff'),
        eyeConfigEnabled: {{ ($opts['eye_config_enabled'] ?? false) ? 'true' : 'false' }},
        eyeSquareStyle:   @js($opts['eye_square_style'] ?? 'square'),
        eyeDotStyle:      @js($opts['eye_dot_style'] ?? 'square'),
        eyeColor:         @js($opts['eye_color'] ?? '#000000'),
        logo:             @js($opts['logo'] ?? ''),
        logoSize:         {{ $opts['logo_size'] ?? 0.3 }},
        logoMargin:       {{ $opts['logo_margin'] ?? 9 }},
        logoHideBackground: {{ ($opts['logo_hide_background'] ?? true) ? 'true' : 'false' }},
        logoShape:        @js($opts['logo_shape'] ?? 'square'),
        logoPath: @js($record ? $record->qr_logo : ''),
        qrInstance: null,
        processedLogo: '',
        activeSection: 'basic',
        dragOver: false,
        uploading: false,
        uploadProgress: 0,

        init() {
            this.$nextTick(() => {
                this.loadScript().then(() => {
                    this.updateProcessedLogo().then(() => {
                        this.render();
                        ['size','margin','dotStyle','colorMode','fgColor','gradientFrom',
                         'gradientTo','gradientType','bgTransparent','bgColor',
                         'eyeConfigEnabled','eyeSquareStyle','eyeDotStyle','eyeColor',
                         'logoSize'
                        ].forEach(k => this.$watch(k, () => { this.syncDom(); this.render(); }));

                        // Watchers for logo/background/shape/margin to update processed image dynamically
                        this.$watch('logo', () => { this.updateProcessedLogo().then(() => { this.syncDom(); this.render(); }); });
                        this.$watch('logoHideBackground', () => { this.updateProcessedLogo().then(() => { this.syncDom(); this.render(); }); });
                        this.$watch('logoShape', () => { this.updateProcessedLogo().then(() => { this.syncDom(); this.render(); }); });
                        this.$watch('logoMargin', () => { this.updateProcessedLogo().then(() => { this.syncDom(); this.render(); }); });

                        // Re-render when the QR tab becomes visible (Filament uses x-show on tab panels)
                        const canvas = this.$refs.qrCanvas;
                        if (canvas) {
                            const obs = new IntersectionObserver(entries => {
                                if (entries[0].isIntersecting) { this.render(); }
                            });
                            obs.observe(canvas);
                        }
                    });
                });
            });
        },

        loadScript() {
            if (window.QRCodeStyling) return Promise.resolve();
            return new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js';
                s.onload  = resolve;
                s.onerror = () => {
                    // Fallback CDN
                    const s2 = document.createElement('script');
                    s2.src = 'https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js';
                    s2.onload = resolve;
                    s2.onerror = reject;
                    document.head.appendChild(s2);
                };
                document.head.appendChild(s);
            });
        },

        updateProcessedLogo() {
            if (!this.logo) {
                this.processedLogo = '';
                return Promise.resolve();
            }

            const processImage = (img, hasCrossOrigin) => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 1200;
                    canvas.height = 1200;
                    const ctx = canvas.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    const isCircle = this.logoShape === 'circle';
                    const targetDim = 1200 - (this.logoMargin * 20); // Dynamic dimension based on slider

                    // Draw the image
                    ctx.save();
                    if (isCircle) {
                        // Clip drawing to the circle
                        ctx.beginPath();
                        ctx.arc(600, 600, targetDim / 2, 0, 2 * Math.PI);
                        ctx.clip();

                        // object-fit: cover scaling logic for circular logo
                        const scale = Math.max(targetDim / img.width, targetDim / img.height);
                        const w = img.width * scale;
                        const h = img.height * scale;
                        const x = (1200 - w) / 2;
                        const y = (1200 - h) / 2;

                        ctx.drawImage(img, x, y, w, h);
                    } else {
                        // object-fit: cover scaling logic for square logo
                        ctx.beginPath();
                        const offset = (1200 - targetDim) / 2;
                        const radius = 144 * (targetDim / 1200); // Scale the radius dynamically

                        if (typeof ctx.roundRect === 'function') {
                            ctx.roundRect(offset, offset, targetDim, targetDim, radius);
                        } else {
                            ctx.moveTo(offset + radius, offset);
                            ctx.lineTo(offset + targetDim - radius, offset);
                            ctx.quadraticCurveTo(offset + targetDim, offset, offset + targetDim, offset + radius);
                            ctx.lineTo(offset + targetDim, offset + targetDim - radius);
                            ctx.quadraticCurveTo(offset + targetDim, offset + targetDim, offset + targetDim - radius, offset + targetDim);
                            ctx.lineTo(offset + radius, offset + targetDim);
                            ctx.quadraticCurveTo(offset, offset + targetDim, offset, offset + targetDim - radius);
                            ctx.lineTo(offset, offset + radius);
                            ctx.quadraticCurveTo(offset, offset, offset + radius, offset);
                            ctx.closePath();
                        }
                        ctx.clip();

                        // object-fit: cover scaling
                        const scale = Math.max(targetDim / img.width, targetDim / img.height);
                        const w = img.width * scale;
                        const h = img.height * scale;
                        const x = (1200 - w) / 2;
                        const y = (1200 - h) / 2;

                        ctx.drawImage(img, x, y, w, h);
                    }
                    ctx.restore();

                    return canvas.toDataURL('image/png');
                } catch (e) {
                    if (hasCrossOrigin) {
                        throw e; // rethrow to trigger loadWithoutCORS
                    }
                    console.warn('Canvas processing failed (possibly CORS), falling back to raw logo:', e);
                    return this.logo;
                }
            };

            return new Promise((resolve) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';

                img.onload = () => {
                    try {
                        this.processedLogo = processImage(img, true);
                        resolve();
                    } catch (e) {
                        loadWithoutCORS();
                    }
                };

                const loadWithoutCORS = () => {
                    const imgRetry = new Image();
                    imgRetry.onload = () => {
                        this.processedLogo = processImage(imgRetry, false);
                        resolve();
                    };
                    imgRetry.onerror = () => {
                        console.error('Failed to load logo image entirely:', this.logo);
                        this.processedLogo = '';
                        resolve();
                    };
                    imgRetry.src = this.logo;
                };

                img.onerror = () => {
                    loadWithoutCORS();
                };

                img.src = this.logo;
            });
        },

        buildOptions() {
            const isGrad = this.colorMode === 'gradient';
            const dotsOptions = isGrad
                ? { type: this.dotStyle, gradient: { type: this.gradientType,
                    colorStops: [{ offset: 0, color: this.gradientFrom }, { offset: 1, color: this.gradientTo }] } }
                : { type: this.dotStyle, color: this.fgColor };

            const mainColor = isGrad ? this.gradientFrom : this.fgColor;
            const eyeSq = this.eyeConfigEnabled
                ? { type: this.eyeSquareStyle, color: this.eyeColor }
                : { type: this.dotStyle === 'dots' ? 'dot' : 'square', color: mainColor };
            const eyeDt = this.eyeConfigEnabled
                ? { type: this.eyeDotStyle, color: this.eyeColor }
                : { type: this.dotStyle === 'dots' ? 'dot' : 'square', color: mainColor };

            const options = {
                type: 'svg',
                width: +this.size || 300, height: +this.size || 300,
                data: this.url, margin: +this.margin || 1,
                dotsOptions,
                backgroundOptions: this.bgTransparent ? { color: 'rgba(0,0,0,0)' } : { color: this.bgColor },
                cornersSquareOptions: eyeSq,
                cornersDotOptions: eyeDt,
                qrOptions: { errorCorrectionLevel: this.logo ? 'H' : 'M' },
            };

            if (this.processedLogo) {
                options.image = this.processedLogo;
                options.imageOptions = {
                    crossOrigin: 'anonymous',
                    hideBackgroundDots: this.logoHideBackground,
                    imageSize: parseFloat(this.logoSize) || 0.3,
                    margin: 0,
                    logoShape: this.logoShape
                };
            }

            return options;
        },

        render() {
            try {
                const el = this.$refs.qrCanvas;
                if (!el || !window.QRCodeStyling) return;
                el.innerHTML = '';
                const opts = this.buildOptions();

                fetch('/admin/short-url/log-debug', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token())
                    },
                    body: JSON.stringify({
                        event: 'render_start',
                        logo: this.logo,
                        processedLogo_length: this.processedLogo ? this.processedLogo.length : 0,
                        options: opts
                    })
                });

                this.qrInstance = new window.QRCodeStyling(opts);
                this.qrInstance.append(el);

                const svg = el.querySelector('svg');
                if (svg) {
                    const w = svg.getAttribute('width') || this.size || 300;
                    const h = svg.getAttribute('height') || this.size || 300;
                    svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
                    svg.style.width = '100%';
                    svg.style.height = '100%';
                    svg.style.maxWidth = '100%';
                    svg.style.maxHeight = '100%';
                }
            } catch (err) {
                fetch('/admin/short-url/log-debug', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token())
                    },
                    body: JSON.stringify({
                        event: 'render_error',
                        error: err.message,
                        stack: err.stack
                    })
                });
            }
        },

        syncDom() {
            const optionsVal = {
                size: +this.size, margin: +this.margin, dot_style: this.dotStyle,
                color_mode: this.colorMode, foreground_color: this.fgColor,
                gradient_from: this.gradientFrom, gradient_to: this.gradientTo,
                gradient_type: this.gradientType, bg_transparent: this.bgTransparent,
                background_color: this.bgColor, eye_config_enabled: this.eyeConfigEnabled,
                eye_square_style: this.eyeSquareStyle, eye_dot_style: this.eyeDotStyle,
                eye_color: this.eyeColor,
                logo: this.logo, logo_size: parseFloat(this.logoSize),
                logo_margin: parseFloat(this.logoMargin), logo_hide_background: this.logoHideBackground,
                logo_shape: this.logoShape,
            };

            const optionsJson = JSON.stringify(optionsVal);

            const findFormInput = (nameSuffix, fallbackId) => {
                const parent = this.$el.closest('form') || this.$el.closest('.fi-modal-window') || this.$el.closest('.fi-fo-component-container');
                if (parent) {
                    const el = parent.querySelector('input[name*=' + nameSuffix + ']');
                    if (el) return el;
                }
                return document.getElementById(fallbackId);
            };

            const optionsEl = findFormInput('qr_options', 'qr-options-json-input');
            const logoEl = findFormInput('qr_logo', 'qr-logo-path-input');

            if (optionsEl) {
                optionsEl.value = optionsJson;
            }
            if (logoEl) {
                logoEl.value = this.logoPath;
            }

            const resolveStatePath = (el, fallback) => {
                if (!el) return fallback;
                for (let i = 0; i < el.attributes.length; i++) {
                    const attr = el.attributes[i];
                    if (attr.name.startsWith('wire:model')) {
                        return attr.value;
                    }
                }
                const name = el.getAttribute('name');
                if (name) {
                    return name.replace(/\[/g, '.').replace(/\]/g, '');
                }
                return fallback;
            };

            const optionsPath = resolveStatePath(optionsEl, @js($qrOptionsStatePath));
            const logoPath = resolveStatePath(logoEl, @js($qrLogoStatePath));

            if (optionsPath && optionsPath.includes('.')) {
                this.$wire.set(optionsPath, optionsJson);
            }
            if (logoPath && logoPath.includes('.')) {
                this.$wire.set(logoPath, this.logoPath);
            }

            fetch('/admin/short-url/log-debug', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @js(csrf_token())
                },
                body: JSON.stringify({
                    optionsEl_exists: !!optionsEl,
                    logoEl_exists: !!logoEl,
                    optionsEl_name: optionsEl ? optionsEl.getAttribute('name') : null,
                    logoEl_name: logoEl ? logoEl.getAttribute('name') : null,
                    optionsPath: optionsPath,
                    logoPath: logoPath,
                    logoPathValue: this.logoPath,
                })
            });
        },

        download(ext) { this.qrInstance?.download({ name: 'qr-code', extension: ext }); },
        setHex(field, val) { if (/^#[0-9A-Fa-f]{6}$/.test(val)) this[field] = val; },
        handleLogoUpload(file) {
            if (!file) return;

            this.uploading = true;
            this.uploadProgress = 0;

            const formData = new FormData();
            formData.append('logo', file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/admin/short-url/upload-logo');
            xhr.setRequestHeader('X-CSRF-TOKEN', @js(csrf_token()));

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                }
            };

            xhr.onload = () => {
                this.uploading = false;
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        this.logoPath = data.path;
                        this.logo = data.url;
                        this.logoMargin = 9;

                        this.updateProcessedLogo().then(() => {
                            this.syncDom();
                            this.render();
                        });
                    } catch (err) {
                        console.error('Parsing response failed:', err);
                        alert('{{ addslashes(__('filament-short-url::default.qr_logo_upload_parse_error')) }}');
                    }
                } else {
                    console.error('Logo upload failed:', xhr.status, xhr.responseText);
                    alert('{{ addslashes(__('filament-short-url::default.qr_logo_upload_error')) }}');
                }
            };

            xhr.onerror = () => {
                this.uploading = false;
                alert('{{ addslashes(__('filament-short-url::default.qr_logo_upload_connection_error')) }}');
            };

            xhr.send(formData);
        },
    }"
    class="w-full"
>
<div class="qr-designer-grid">
    {{-- ══ LEFT: settings panel (Accordions) ══ --}}
    <div class="qr-accordion">

        {{-- Section 1: Basic settings --}}
        <div class="qr-accordion-item" :class="activeSection === 'basic' ? 'open' : ''">
            <button type="button" class="qr-accordion-header" x-on:click="activeSection = (activeSection === 'basic' ? '' : 'basic')">
                <span style="display:flex;align-items:center;gap:8px">
                    <svg class="qr-accordion-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ __('filament-short-url::default.form_section_options') }}</span>
                </span>
                <svg class="qr-accordion-chevron" :style="activeSection === 'basic' ? 'transform: rotate(90deg)' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="qr-accordion-content" x-show="activeSection === 'basic'" x-collapse>
                <div class="qr-grid-cols-2" style="margin-top:8px">
                    <div>
                        <span class="qr-label">{{ __('filament-short-url::default.qr_label_size') }}</span>
                        <input type="number" x-model.number="size" min="100" max="1000" step="10" class="qr-input-num" />
                    </div>
                    <div>
                        <span class="qr-label">{{ __('filament-short-url::default.qr_label_margin') }}</span>
                        <select x-model.number="margin" class="qr-select">
                            @foreach (range(0, 10) as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Dots & Background --}}
        <div class="qr-accordion-item" :class="activeSection === 'design' ? 'open' : ''">
            <button type="button" class="qr-accordion-header" x-on:click="activeSection = (activeSection === 'design' ? '' : 'design')">
                <span style="display:flex;align-items:center;gap:8px">
                    <svg class="qr-accordion-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    <span>{{ __('filament-short-url::default.qr_label_dots_background') }}</span>
                </span>
                <svg class="qr-accordion-chevron" :style="activeSection === 'design' ? 'transform: rotate(90deg)' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="qr-accordion-content" x-show="activeSection === 'design'" x-collapse>
                <div class="qr-space-y-4" style="margin-top:8px">
                    {{-- Dot Style --}}
                    <div>
                        <span class="qr-label">{{ __('filament-short-url::default.qr_label_style') }}</span>
                        <select x-model="dotStyle" class="qr-select">
                            <option value="square">{{ __('filament-short-url::default.qr_option_square') }}</option>
                            <option value="dots">{{ __('filament-short-url::default.qr_option_dots') }}</option>
                            <option value="rounded">{{ __('filament-short-url::default.qr_option_rounded') }}</option>
                            <option value="classy">{{ __('filament-short-url::default.qr_option_classy') }}</option>
                            <option value="classy-rounded">{{ __('filament-short-url::default.qr_option_classy_rounded') }}</option>
                            <option value="extra-rounded">{{ __('filament-short-url::default.qr_option_extra_rounded') }}</option>
                        </select>
                    </div>

                    {{-- Foreground Color Mode --}}
                    <div>
                        <span class="qr-label">{{ __('filament-short-url::default.qr_label_foreground_color') }}</span>
                        <div class="qr-segment-control">
                            <button type="button" class="qr-segment-btn" :class="colorMode === 'solid' ? 'active' : ''" x-on:click="colorMode = 'solid'">
                                {{ __('filament-short-url::default.qr_label_single_color') }}
                            </button>
                            <button type="button" class="qr-segment-btn" :class="colorMode === 'gradient' ? 'active' : ''" x-on:click="colorMode = 'gradient'">
                                {{ __('filament-short-url::default.qr_label_gradient') }}
                            </button>
                        </div>
                    </div>

                    {{-- Solid Color --}}
                    <div x-show="colorMode === 'solid'" x-transition>
                        <span class="qr-label">{{ __('filament-short-url::default.qr_label_color') }}</span>
                        <div class="qr-color-field">
                            <div class="qr-color-swatch qr-color-picker" :style="'background:'+fgColor">
                                <input type="color" x-model="fgColor" />
                            </div>
                            <input type="text" class="qr-hex-input" :value="fgColor" x-on:change="setHex('fgColor', $event.target.value)" maxlength="7" />
                        </div>
                    </div>

                    {{-- Gradient --}}
                    <div x-show="colorMode === 'gradient'" x-transition class="qr-space-y-4">
                        <div class="qr-grid-cols-2">
                            <div>
                                <span class="qr-label">{{ __('filament-short-url::default.qr_label_from') }}</span>
                                <div class="qr-color-field">
                                    <div class="qr-color-swatch qr-color-picker" :style="'background:'+gradientFrom">
                                        <input type="color" x-model="gradientFrom" />
                                    </div>
                                    <input type="text" class="qr-hex-input" :value="gradientFrom" x-on:change="setHex('gradientFrom', $event.target.value)" maxlength="7" />
                                </div>
                            </div>
                            <div>
                                <span class="qr-label">{{ __('filament-short-url::default.qr_label_to') }}</span>
                                <div class="qr-color-field">
                                    <div class="qr-color-swatch qr-color-picker" :style="'background:'+gradientTo">
                                        <input type="color" x-model="gradientTo" />
                                    </div>
                                    <input type="text" class="qr-hex-input" :value="gradientTo" x-on:change="setHex('gradientTo', $event.target.value)" maxlength="7" />
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="qr-label">{{ __('filament-short-url::default.qr_label_gradient_type') }}</span>
                            <select x-model="gradientType" class="qr-select">
                                <option value="linear">{{ __('filament-short-url::default.qr_gradient_linear') }}</option>
                                <option value="radial">{{ __('filament-short-url::default.qr_gradient_radial') }}</option>
                            </select>
                        </div>
                    </div>

                    {{-- Background Transparency --}}
                    <div style="border-top:1px solid #e5e7eb;padding-top:12px;margin-top:12px">
                        <div class="flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between">
                            <span style="font-size:13px;font-weight:600;color:#374151" class="dark:text-gray-300">{{ __('filament-short-url::default.qr_label_transparent') }}</span>
                            <button type="button" :class="bgTransparent ? 'on' : 'off'" class="qr-toggle" x-on:click="bgTransparent = !bgTransparent">
                                <span class="qr-toggle-thumb"></span>
                            </button>
                        </div>

                        {{-- Background Color (if not transparent) --}}
                        <div x-show="!bgTransparent" x-transition style="margin-top:12px">
                            <span class="qr-label">{{ __('filament-short-url::default.qr_label_color') }}</span>
                            <div class="qr-color-field">
                                <div class="qr-color-swatch qr-color-picker" :style="'background:'+bgColor">
                                    <input type="color" x-model="bgColor" />
                                </div>
                                <input type="text" class="qr-hex-input" :value="bgColor" x-on:change="setHex('bgColor', $event.target.value)" maxlength="7" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Corner Eyes --}}
        <div class="qr-accordion-item" :class="activeSection === 'eyes' ? 'open' : ''">
            <button type="button" class="qr-accordion-header" x-on:click="activeSection = (activeSection === 'eyes' ? '' : 'eyes')">
                <span style="display:flex;align-items:center;gap:8px">
                    <svg class="qr-accordion-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>{{ __('filament-short-url::default.qr_label_eye_config') }}</span>
                </span>
                <svg class="qr-accordion-chevron" :style="activeSection === 'eyes' ? 'transform: rotate(90deg)' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="qr-accordion-content" x-show="activeSection === 'eyes'" x-collapse>
                <div class="qr-space-y-4" style="margin-top:8px">
                    <div class="flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:13px;font-weight:600;color:#374151" class="dark:text-gray-300">{{ __('filament-short-url::default.qr_label_custom_eye_config') }}</span>
                        <button type="button" :class="eyeConfigEnabled ? 'on' : 'off'" class="qr-toggle" x-on:click="eyeConfigEnabled = !eyeConfigEnabled">
                            <span class="qr-toggle-thumb"></span>
                        </button>
                    </div>

                    <div x-show="eyeConfigEnabled" x-transition class="qr-space-y-4">
                        <div>
                            <span class="qr-label">{{ __('filament-short-url::default.qr_label_eye_square_style') }}</span>
                            <select x-model="eyeSquareStyle" class="qr-select">
                                <option value="square">{{ __('filament-short-url::default.qr_option_square') }}</option>
                                <option value="dot">{{ __('filament-short-url::default.qr_option_dot') }}</option>
                                <option value="extra-rounded">{{ __('filament-short-url::default.qr_option_extra_rounded') }}</option>
                            </select>
                        </div>
                        <div>
                            <span class="qr-label">{{ __('filament-short-url::default.qr_label_eye_dot_style') }}</span>
                            <select x-model="eyeDotStyle" class="qr-select">
                                <option value="square">{{ __('filament-short-url::default.qr_option_square') }}</option>
                                <option value="dot">{{ __('filament-short-url::default.qr_option_dot') }}</option>
                            </select>
                        </div>
                        <div>
                            <span class="qr-label">{{ __('filament-short-url::default.qr_label_eye_color') }}</span>
                            <div class="qr-color-field">
                                <div class="qr-color-swatch qr-color-picker" :style="'background:'+eyeColor">
                                    <input type="color" x-model="eyeColor" />
                                </div>
                                <input type="text" class="qr-hex-input" :value="eyeColor" x-on:change="setHex('eyeColor', $event.target.value)" maxlength="7" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 4: Logo & Overlay --}}
        <div class="qr-accordion-item" :class="activeSection === 'logo' ? 'open' : ''">
            <button type="button" class="qr-accordion-header" x-on:click="activeSection = (activeSection === 'logo' ? '' : 'logo')">
                <span style="display:flex;align-items:center;gap:8px">
                    <svg class="qr-accordion-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ __('filament-short-url::default.qr_label_logo_overlay') }}</span>
                </span>
                <svg class="qr-accordion-chevron" :style="activeSection === 'logo' ? 'transform: rotate(90deg)' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div class="qr-accordion-content" x-show="activeSection === 'logo'" x-collapse>
                <div class="qr-space-y-4" style="margin-top:8px">
                    
                    {{-- Upload Area --}}
                    <div style="position:relative">
                        <div class="qr-upload-zone" 
                             :class="dragOver ? 'dragover' : ''"
                             style="position: relative; overflow: hidden;"
                             x-on:dragover.prevent="dragOver = true"
                             x-on:dragleave.prevent="dragOver = false"
                             x-on:drop.prevent="dragOver = false; handleLogoUpload($event.dataTransfer.files[0])"
                             x-on:click="if (!uploading) $refs.logoInput.click()">
                            
                            <!-- Inner content container, blurred/faded during upload -->
                            <div :style="uploading ? 'filter: blur(1px); opacity: 0.5; pointer-events: none;' : ''" style="width: 100%; transition: all 0.2s ease;">
                                <!-- 1. Empty state (shown if no logo) -->
                                <div x-show="!logo" style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%">
                                    <svg class="qr-upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                    </svg>
                                    <span class="qr-upload-text">{{ __('filament-short-url::default.qr_label_drag_drop_upload') }}</span>
                                    <span class="qr-upload-hint">{{ __('filament-short-url::default.qr_label_upload_supports') }}</span>
                                </div>

                                <!-- 2. Logo Active state (shown if logo exists) -->
                                <div x-show="logo" style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%">
                                    <div style="position:relative;width:100%;height:150px;margin-bottom:8px;border-radius:8px;border:1.5px solid #e5e7eb;background:#ffffff;padding:8px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,0.05)">
                                        <img :src="logo" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:4px" />
                                    </div>
                                    <span class="qr-upload-hint">{{ __('filament-short-url::default.qr_label_drag_drop_replace') }}</span>
                                </div>
                            </div>

                            <!-- 3. Corner loader/progress overlay -->
                            <div x-show="uploading" style="position: absolute; top: 12px; right: 12px; display: flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(4px); border: 1.5px solid #e5e7eb; border-radius: 9999px; padding: 4px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); font-size: 10px; font-weight: 700; color: #4f46e5; z-index: 10;" class="dark:bg-zinc-800 dark:border-zinc-700 dark:text-indigo-400">
                                <!-- Animated Spinner -->
                                <svg style="width: 14px; height: 14px; animation: qr-spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" style="opacity: 0.25"></circle>
                                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" style="opacity: 0.75"></path>
                                </svg>
                                <!-- Progress percentage -->
                                <span x-text="uploadProgress + '%'"></span>
                            </div>

                            <!-- 4. Progress bar line at the very bottom of the dropzone -->
                            <div x-show="uploading" style="position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: #e5e7eb;" class="dark:bg-zinc-800">
                                <div :style="'width: ' + uploadProgress + '%'" style="height: 100%; background: #6366f1; transition: width 0.1s ease-out;"></div>
                            </div>

                            <input type="file" x-ref="logoInput" accept="image/*" style="display:none" x-on:change="handleLogoUpload($event.target.files[0])" />
                        </div>
                    </div>

                    {{-- Logo Details & Sliders (rendered if logo exists) --}}
                    <div x-show="logo" x-transition class="qr-space-y-4">
                        <div style="display:flex;justify-content:flex-end">
                            <button type="button" x-on:click="logo = ''; logoPath = ''; if ($refs.logoInput) $refs.logoInput.value = '';" style="font-size:12px;font-weight:600;color:#ef4444;background:none;border:none;cursor:pointer;text-decoration:underline">
                                {{ __('filament-short-url::default.qr_label_remove_logo') }}
                            </button>
                        </div>

                        <div>
                            <span class="qr-label">{{ __('filament-short-url::default.qr_label_logo_shape') }}</span>
                            <div class="qr-segment-control">
                                <button type="button" class="qr-segment-btn" :class="logoShape === 'square' ? 'active' : ''" x-on:click="logoShape = 'square'">
                                    {{ __('filament-short-url::default.qr_option_square') }}
                                </button>
                                <button type="button" class="qr-segment-btn" :class="logoShape === 'circle' ? 'active' : ''" x-on:click="logoShape = 'circle'">
                                    {{ __('filament-short-url::default.qr_option_circle') }}
                                </button>
                            </div>
                        </div>

                        <div class="qr-slider-container">
                            <div style="display:flex;justify-content:space-between;align-items:center">
                                <span class="qr-label" style="margin-bottom:0">{{ __('filament-short-url::default.qr_label_logo_size') }}</span>
                                <span class="qr-badge" x-text="Math.round(logoSize * 100) + '%'"></span>
                            </div>
                            <div class="qr-slider-track-wrap">
                                <input type="range" x-model.number="logoSize" min="0.1" max="0.5" step="0.05" class="qr-range-input" />
                            </div>
                        </div>

                        <div class="qr-slider-container">
                            <div style="display:flex;justify-content:space-between;align-items:center">
                                <span class="qr-label" style="margin-bottom:0">{{ __('filament-short-url::default.qr_label_logo_margin') }}</span>
                                <span class="qr-badge" x-text="logoMargin + 'px'"></span>
                            </div>
                            <div class="qr-slider-track-wrap">
                                <input type="range" x-model.number="logoMargin" min="0" max="20" step="1" class="qr-range-input" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between;border-top:1px solid #e5e7eb;padding-top:12px">
                            <span style="font-size:13px;font-weight:600;color:#374151" class="dark:text-gray-300">{{ __('filament-short-url::default.qr_label_clear_dots') }}</span>
                            <button type="button" :class="logoHideBackground ? 'on' : 'off'" class="qr-toggle" x-on:click="logoHideBackground = !logoHideBackground">
                                <span class="qr-toggle-thumb"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══ RIGHT: Sticky Preview Card ══ --}}
    <div class="qr-preview-sticky">
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
                {{ $shortUrl }}
            </p>
        </div>
    </div>
</div>
</div>
