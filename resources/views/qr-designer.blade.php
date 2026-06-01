@php
    /** @var \Bjanczak\FilamentShortUrl\Models\ShortUrl|null $record */
    $record = $this->record ?? null;
    $shortUrl = $record ? $record->getShortUrl() : (config('app.url').'/s/preview');
    $opts = $record ? $record->getQrOptions() : config('filament-short-url.qr_defaults', []);
@endphp

<style>
/* Color picker */
.qr-color-picker input[type=color] {
    position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;
}
/* Radio buttons */
.qr-radio-option {
    display:flex;align-items:center;gap:8px;cursor:pointer;
    padding:8px 12px;border-radius:8px;border:1.5px solid #e5e7eb;
    transition:all .15s;font-size:13px;font-weight:500;color:#374151;
    background:white;
}
.dark .qr-radio-option { background:#1f2937;border-color:#374151;color:#d1d5db; }
.qr-radio-option.active {
    border-color:#6366f1;background:#eef2ff;color:#4338ca;
}
.dark .qr-radio-option.active { background:#1e1b4b;border-color:#818cf8;color:#a5b4fc; }
.qr-radio-dot {
    width:15px;height:15px;border-radius:50%;border:2px solid currentColor;
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
}
.qr-radio-option.active .qr-radio-dot::after {
    content:'';width:6px;height:6px;border-radius:50%;background:currentColor;
}
/* Toggle */
.qr-toggle {
    position:relative;display:inline-flex;align-items:center;
    height:22px;width:42px;cursor:pointer;border-radius:9999px;
    transition:background-color .2s ease;flex-shrink:0;
}
.qr-toggle.on  { background:#6366f1; }
.qr-toggle.off { background:#d1d5db; }
.dark .qr-toggle.off { background:#4b5563; }
.qr-toggle-thumb {
    pointer-events:none;height:18px;width:18px;border-radius:9999px;
    background:white;box-shadow:0 1px 3px rgba(0,0,0,.25);
    transition:transform .2s ease;position:absolute;left:2px;
}
.qr-toggle.on  .qr-toggle-thumb { transform:translateX(20px); }
.qr-toggle.off .qr-toggle-thumb { transform:translateX(0); }
/* Labels */
.qr-label {
    font-size:11px;font-weight:700;color:#9ca3af;
    text-transform:uppercase;letter-spacing:.06em;
    margin-bottom:6px;display:block;
}
/* Selects */
.qr-select {
    width:100%;padding:7px 30px 7px 10px;border-radius:8px;font-size:13px;font-weight:500;
    border:1.5px solid #e5e7eb;background:#fff;color:#111827;
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 8px center;background-size:16px;
    cursor:pointer;transition:border-color .15s;
}
.qr-select:focus { outline:none;border-color:#6366f1; }
.dark .qr-select { background-color:#374151;border-color:#4b5563;color:#f9fafb; }
/* Number input */
.qr-input-num {
    width:100%;padding:7px 10px;border-radius:8px;font-size:13px;font-weight:500;
    border:1.5px solid #e5e7eb;background:#fff;color:#111827;transition:border-color .15s;
}
.qr-input-num:focus { outline:none;border-color:#6366f1; }
.dark .qr-input-num { background-color:#374151;border-color:#4b5563;color:#f9fafb; }
/* Color swatch */
.qr-color-swatch {
    position:relative;width:36px;height:36px;border-radius:8px;
    border:1.5px solid #e5e7eb;cursor:pointer;overflow:hidden;flex-shrink:0;
}
/* Hex input */
.qr-hex-input {
    flex:1;min-width:0;padding:7px 10px;border-radius:8px;
    font-size:12px;font-family:monospace;font-weight:600;
    border:1.5px solid #e5e7eb;background:#fff;color:#374151;
    letter-spacing:.03em;transition:border-color .15s;
}
.qr-hex-input:focus { outline:none;border-color:#6366f1; }
.dark .qr-hex-input { background:#374151;border-color:#4b5563;color:#f9fafb; }
/* Sections */
.qr-section { padding:14px 0;border-bottom:1px solid #f3f4f6; }
.dark .qr-section { border-color:#374151; }
.qr-section:last-child { border-bottom:none; }
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
@keyframes qr-spin { to { transform: rotate(360deg); } }
.qr-margin-top {
    margin-top: 12px !important;
}
.qr-space-y > div {
    margin-top: 12px !important;
}
.qr-space-y > div:first-child {
    margin-top: 0 !important;
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
        qrInstance: null,

        init() {
            this.$nextTick(() => {
                this.loadScript().then(() => {
                    this.render();
                    ['size','margin','dotStyle','colorMode','fgColor','gradientFrom',
                     'gradientTo','gradientType','bgTransparent','bgColor',
                     'eyeConfigEnabled','eyeSquareStyle','eyeDotStyle','eyeColor'
                    ].forEach(k => this.$watch(k, () => { this.syncDom(); this.render(); }));

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

            return {
                width: +this.size || 300, height: +this.size || 300,
                data: this.url, margin: +this.margin || 1,
                dotsOptions,
                backgroundOptions: this.bgTransparent ? { color: 'rgba(0,0,0,0)' } : { color: this.bgColor },
                cornersSquareOptions: eyeSq,
                cornersDotOptions: eyeDt,
                qrOptions: { errorCorrectionLevel: 'M' },
            };
        },

        render() {
            const el = this.$refs.qrCanvas;
            if (!el || !window.QRCodeStyling) return;
            el.innerHTML = '';
            this.qrInstance = new window.QRCodeStyling(this.buildOptions());
            this.qrInstance.append(el);
        },

        syncDom() {
            const el = document.getElementById('qr-options-json-input');
            if (!el) return;
            el.value = JSON.stringify({
                size: +this.size, margin: +this.margin, dot_style: this.dotStyle,
                color_mode: this.colorMode, foreground_color: this.fgColor,
                gradient_from: this.gradientFrom, gradient_to: this.gradientTo,
                gradient_type: this.gradientType, bg_transparent: this.bgTransparent,
                background_color: this.bgColor, eye_config_enabled: this.eyeConfigEnabled,
                eye_square_style: this.eyeSquareStyle, eye_dot_style: this.eyeDotStyle,
                eye_color: this.eyeColor,
            });
            el.dispatchEvent(new Event('input', { bubbles: true }));
        },

        download(ext) { this.qrInstance?.download({ name: 'qr-code', extension: ext }); },
        setHex(field, val) { if (/^#[0-9A-Fa-f]{6}$/.test(val)) this[field] = val; },
    }"
    class="w-full"
>
{{-- CSS Grid: left=fixed 280px, right=fills remaining space, both columns same height --}}
<div style="display:grid;grid-template-columns:280px 1fr;gap:2rem;width:100%;min-width:0;align-items:stretch">

    {{-- ══ LEFT: settings panel ══ --}}
    <div style="min-width:0;overflow:hidden">

        {{-- Size & Margin --}}
        <div class="qr-section">
            <div class="grid grid-cols-2 gap-3" style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:12px">
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

        {{-- Dot Style --}}
        <div class="qr-section">
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

        {{-- Foreground Color --}}
        <div class="qr-section">
            <span class="qr-label">{{ __('filament-short-url::default.qr_label_foreground_color') }}</span>

            {{-- Mode radio --}}
            <div class="flex gap-2" style="display:flex;gap:8px">
                <button type="button"
                    :class="colorMode === 'solid' ? 'active' : ''"
                    class="qr-radio-option flex-1 justify-center text-center"
                    x-on:click="colorMode = 'solid'">
                    <span class="qr-radio-dot"></span>
                    {{ __('filament-short-url::default.qr_label_single_color') }}
                </button>
                <button type="button"
                    :class="colorMode === 'gradient' ? 'active' : ''"
                    class="qr-radio-option flex-1 justify-center text-center"
                    x-on:click="colorMode = 'gradient'">
                    <span class="qr-radio-dot"></span>
                    {{ __('filament-short-url::default.qr_label_gradient') }}
                </button>
            </div>

            {{-- Single color picker --}}
            <div x-show="colorMode === 'solid'" x-transition style="display:block" class="mt-3 qr-margin-top">
                <span class="qr-label">{{ __('filament-short-url::default.qr_label_color') }}</span>
                <div class="flex items-center gap-2" style="display:flex;align-items:center;gap:8px">
                    <div class="qr-color-swatch qr-color-picker" :style="'background:'+fgColor">
                        <input type="color" x-model="fgColor" />
                    </div>
                    <input type="text" class="qr-hex-input"
                        :value="fgColor"
                        x-on:change="setHex('fgColor', $event.target.value)"
                        maxlength="7" placeholder="#000000" />
                </div>
            </div>

            {{-- Gradient pickers --}}
            <div x-show="colorMode === 'gradient'" x-transition style="display:none" class="mt-3 space-y-3 qr-space-y qr-margin-top">
                <div class="grid grid-cols-2 gap-2" style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:8px">
                    <div>
                        <span class="qr-label">{{ __('filament-short-url::default.qr_label_from') }}</span>
                        <div class="flex items-center gap-1.5" style="display:flex;align-items:center;gap:6px">
                            <div class="qr-color-swatch qr-color-picker" :style="'background:'+gradientFrom">
                                <input type="color" x-model="gradientFrom" />
                            </div>
                            <input type="text" class="qr-hex-input"
                                :value="gradientFrom"
                                x-on:change="setHex('gradientFrom', $event.target.value)"
                                maxlength="7" />
                        </div>
                    </div>
                    <div>
                        <span class="qr-label">{{ __('filament-short-url::default.qr_label_to') }}</span>
                        <div class="flex items-center gap-1.5" style="display:flex;align-items:center;gap:6px">
                            <div class="qr-color-swatch qr-color-picker" :style="'background:'+gradientTo">
                                <input type="color" x-model="gradientTo" />
                            </div>
                            <input type="text" class="qr-hex-input"
                                :value="gradientTo"
                                x-on:change="setHex('gradientTo', $event.target.value)"
                                maxlength="7" />
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
        </div>

        {{-- Background --}}
        <div class="qr-section">
            <span class="qr-label">{{ __('filament-short-url::default.qr_label_background') }}</span>
            <div class="flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-size:13px;font-weight:500;color:#374151" class="dark:text-gray-300">{{ __('filament-short-url::default.qr_label_transparent') }}</span>
                <button type="button"
                    :class="bgTransparent ? 'on' : 'off'"
                    class="qr-toggle"
                    x-on:click="bgTransparent = !bgTransparent">
                    <span class="qr-toggle-thumb"></span>
                </button>
            </div>
            <div x-show="!bgTransparent" x-transition style="display:block;margin-top:12px" class="mt-3 qr-margin-top">
                <span class="qr-label">{{ __('filament-short-url::default.qr_label_color') }}</span>
                <div class="flex items-center gap-2" style="display:flex;align-items:center;gap:8px">
                    <div class="qr-color-swatch qr-color-picker" :style="'background:'+bgColor">
                        <input type="color" x-model="bgColor" />
                    </div>
                    <input type="text" class="qr-hex-input"
                        :value="bgColor"
                        x-on:change="setHex('bgColor', $event.target.value)"
                        maxlength="7" placeholder="#ffffff" />
                </div>
            </div>
            <div x-show="bgTransparent" x-transition style="display:none;margin-top:12px" class="mt-3 qr-margin-top">
                <div class="qr-checker flex h-9 w-full items-center justify-center rounded-lg border border-dashed border-gray-300" style="display:flex;align-items:center;justify-content:center;font-size:11px;color:#9ca3af;font-weight:600">
                    TRANSPARENT
                </div>
            </div>
        </div>

        {{-- Eye Config --}}
        <div class="qr-section">
            <div class="flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between">
                <span class="qr-label" style="margin-bottom:0">{{ __('filament-short-url::default.qr_label_eye_config') }}</span>
                <button type="button"
                    :class="eyeConfigEnabled ? 'on' : 'off'"
                    class="qr-toggle"
                    x-on:click="eyeConfigEnabled = !eyeConfigEnabled">
                    <span class="qr-toggle-thumb"></span>
                </button>
            </div>
            <div x-show="eyeConfigEnabled" x-transition style="display:none" class="mt-3 space-y-3 qr-space-y qr-margin-top">
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
                    <div class="flex items-center gap-2" style="display:flex;align-items:center;gap:8px">
                        <div class="qr-color-swatch qr-color-picker" :style="'background:'+eyeColor">
                            <input type="color" x-model="eyeColor" />
                        </div>
                        <input type="text" class="qr-hex-input"
                            :value="eyeColor"
                            x-on:change="setHex('eyeColor', $event.target.value)"
                            maxlength="7" />
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══ RIGHT: Preview ══ --}}
    <div style="display:flex;flex-direction:column;min-width:0;height:100%">

        {{-- Top bar --}}
        <div class="mb-4 flex items-center justify-between" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <span style="font-size:13px;font-weight:600;color:#9ca3af">{{ __('filament-short-url::default.qr_label_preview') }}</span>
            <div class="flex items-center gap-2" style="display:flex;align-items:center;gap:8px">
                <button type="button" x-on:click="download('png')" class="qr-dl-btn">
                    <svg style="width:13px;height:13px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    PNG
                </button>
                <button type="button" x-on:click="download('svg')" class="qr-dl-btn">
                    <svg style="width:13px;height:13px" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    SVG
                </button>
            </div>
        </div>

        {{--
            Canvas box — ALL styles in :style (object) so Alpine does NOT overwrite
            the static style attribute (Alpine :style REPLACES, not merges, with static style).
        --}}
        <div :class="bgTransparent ? 'qr-checker' : ''"
             :style="{
                flex: '1',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                borderRadius: '15px',
                border: '1.5px solid #e5e7eb',
                minHeight: '280px',
                padding: '32px',
                position: 'relative',
                background: bgTransparent ? '' : bgColor,
             }">
            {{-- Spinner --}}
            <div x-show="!qrInstance"
                 style="width:36px;height:36px;border:3px solid #e5e7eb;
                        border-top-color:#6366f1;border-radius:50%;
                        animation:qr-spin 0.8s linear infinite">
            </div>
            {{-- QR canvas --}}
            <div x-ref="qrCanvas" style="line-height:0"></div>
        </div>

        <p class="mt-2 text-center font-mono" style="font-size:11px;color:#9ca3af">
            {{ $shortUrl }}
        </p>
    </div>

</div>
</div>
