@php
    $shortUrl = $record->getShortUrl();
    $qrTargetUrl = $shortUrl . '?source=qr';
    $destHost = parse_url($record->destination_url, PHP_URL_HOST) ?? '';
    $urlKey = $record->url_key;
    $eid = 'fsu_' . substr(md5($shortUrl), 0, 8);

    $qrHelperText = __('filament-short-url::default.qr_modal_helper');
    $downloadSvgText = __('filament-short-url::default.qr_download_svg');
    $downloadPngText = __('filament-short-url::default.qr_download_png');
    $closeButtonText = __('filament-short-url::default.close_button');
    $copyLinkText = __('filament-short-url::default.action_copy');
    $openLinkText = __('filament-short-url::default.open_link');

    $qrDefaults = $record->getQrOptions();
    $isGrad = ($qrDefaults['gradient_enabled'] ?? false) || (($qrDefaults['color_mode'] ?? '') === 'gradient');
    $dotStyle = $qrDefaults['dot_style'] ?? 'square';
    $fgColor = $qrDefaults['foreground_color'] ?? '#000000';
    $bgColor = ($qrDefaults['bg_transparent'] ?? false) ? 'rgba(0,0,0,0)' : ($qrDefaults['background_color'] ?? '#ffffff');

    $dotsOptions = $isGrad ? [
        'type' => $dotStyle,
        'gradient' => [
            'type' => $qrDefaults['gradient_type'] ?? 'linear',
            'colorStops' => [
                ['offset' => 0, 'color' => $qrDefaults['gradient_from'] ?? '#4f46e5'],
                ['offset' => 1, 'color' => $qrDefaults['gradient_to'] ?? '#06b6d4'],
            ],
        ],
    ] : [
        'type' => $dotStyle,
        'color' => $fgColor,
    ];

    $mainColor = $isGrad ? ($qrDefaults['gradient_from'] ?? '#4f46e5') : $fgColor;

    $eyeConfigEnabled = $qrDefaults['eye_config_enabled'] ?? false;
    $eyeSquareStyle = $qrDefaults['eye_square_style'] ?? ($dotStyle === 'dots' ? 'dot' : 'square');
    $eyeDotStyle = $qrDefaults['eye_dot_style'] ?? ($dotStyle === 'dots' ? 'dot' : 'square');
    $eyeColor = $qrDefaults['eye_color'] ?? $mainColor;

    $cornersSquareOptions = $eyeConfigEnabled ? [
        'type' => $eyeSquareStyle,
        'color' => $eyeColor,
    ] : [
        'type' => $dotStyle === 'dots' ? 'dot' : 'square',
        'color' => $mainColor,
    ];

    $cornersDotOptions = $eyeConfigEnabled ? [
        'type' => $eyeDotStyle,
        'color' => $eyeColor,
    ] : [
        'type' => $dotStyle === 'dots' ? 'dot' : 'square',
        'color' => $mainColor,
    ];

    $logo = $qrDefaults['logo'] ?? null;
    $logoSize = $qrDefaults['logo_size'] ?? 0.3;
    $logoMargin = $qrDefaults['logo_margin'] ?? 9;
    $logoHideBackground = $qrDefaults['logo_hide_background'] ?? true;
    $logoShape = $qrDefaults['logo_shape'] ?? 'square';

    $qrOptionsJson = json_encode([
        'type' => 'svg',
        'width' => 200,
        'height' => 200,
        'margin' => $qrDefaults['margin'] ?? 1,
        'dotsOptions' => $dotsOptions,
        'backgroundOptions' => ['color' => $bgColor],
        'cornersSquareOptions' => $cornersSquareOptions,
        'cornersDotOptions' => $cornersDotOptions,
        'image' => $logo ?: null,
        'imageOptions' => [
            'crossOrigin' => 'anonymous',
            'hideBackgroundDots' => $logoHideBackground,
            'imageSize' => $logoSize,
            'margin' => $logoMargin,
            'logoShape' => $logoShape,
        ],
        'qrOptions' => ['errorCorrectionLevel' => $logo ? 'H' : 'M'],
    ]);
@endphp

<div data-qr-options="{{ $qrOptionsJson }}" x-data="{
    init() {
        const el = this.$el;
        const eid = '{{ $eid }}';
        const qrTargetUrl = '{{ $qrTargetUrl }}';
        
        this.$nextTick(() => {
            this.loadScript().then(() => {
                const canvas = document.getElementById(eid + '_qr_canvas');
                if (canvas && !canvas.innerHTML) {
                    const opts = JSON.parse(el.getAttribute('data-qr-options'));
                    opts.data = qrTargetUrl;
                    
                    const fixSvg = () => {
                        const svg = canvas.querySelector('svg');
                        if (svg) {
                            const w = svg.getAttribute('width') || opts.width || 200;
                            const h = svg.getAttribute('height') || opts.height || 200;
                            svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
                            svg.style.width = '100%';
                            svg.style.height = '100%';
                        }
                    };

                    if (opts.image && opts.imageOptions) {
                        const img = new Image();
                        img.onload = () => {
                            const cv = document.createElement('canvas');
                            cv.width = 1200;
                            cv.height = 1200;
                            const cx = cv.getContext('2d');
                            cx.imageSmoothingEnabled = true;
                            cx.imageSmoothingQuality = 'high';

                            const isCircle = opts.imageOptions.logoShape === 'circle';
                            const targetDim = 1200 - (parseFloat(opts.imageOptions.margin || 0) * 20);

                            cx.save();
                            if (isCircle) {
                                cx.beginPath();
                                cx.arc(600, 600, targetDim / 2, 0, 2 * Math.PI);
                                cx.clip();

                                const scale = Math.max(targetDim / img.width, targetDim / img.height);
                                const w = img.width * scale;
                                const h = img.height * scale;
                                const x = (1200 - w) / 2;
                                const y = (1200 - h) / 2;

                                cx.drawImage(img, x, y, w, h);
                            } else {
                                cx.beginPath();
                                const offset = (1200 - targetDim) / 2;
                                const radius = 144 * (targetDim / 1200);

                                if (typeof cx.roundRect === 'function') {
                                    cx.roundRect(offset, offset, targetDim, targetDim, radius);
                                } else {
                                    cx.moveTo(offset + radius, offset);
                                    cx.lineTo(offset + targetDim - radius, offset);
                                    cx.quadraticCurveTo(offset + targetDim, offset, offset + targetDim, offset + radius);
                                    cx.lineTo(offset + targetDim, offset + targetDim - radius);
                                    cx.quadraticCurveTo(offset + targetDim, offset + targetDim, offset + targetDim - radius, offset + targetDim);
                                    cx.lineTo(offset + radius, offset + targetDim);
                                    cx.quadraticCurveTo(offset, offset + targetDim, offset, offset + targetDim - radius);
                                    cx.lineTo(offset, offset + radius);
                                    cx.quadraticCurveTo(offset, offset, offset + radius, offset);
                                    cx.closePath();
                                }
                                cx.clip();

                                const scale = Math.max(targetDim / img.width, targetDim / img.height);
                                const w = img.width * scale;
                                const h = img.height * scale;
                                const x = (1200 - w) / 2;
                                const y = (1200 - h) / 2;

                                cx.drawImage(img, x, y, w, h);
                            }
                            cx.restore();

                            opts.image = cv.toDataURL('image/png');
                            opts.imageOptions.margin = 0;

                            window['qr_' + eid] = new window.QRCodeStyling(opts);
                            window['qr_' + eid].append(canvas);
                            fixSvg();
                        };
                        img.onerror = () => {
                            opts.image = null;
                            window['qr_' + eid] = new window.QRCodeStyling(opts);
                            window['qr_' + eid].append(canvas);
                            fixSvg();
                        };
                        img.src = opts.image;
                    } else {
                        window['qr_' + eid] = new window.QRCodeStyling(opts);
                        window['qr_' + eid].append(canvas);
                        fixSvg();
                    }
                }
            });
        });
    },
    loadScript() {
        return new Promise((resolve, reject) => {
            if (window.QRCodeStyling) return resolve();
            const s = document.createElement('script');
            s.src = '/js/janczakb/filament-short-url/qr-code-styling.js';
            s.onload = () => resolve();
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }
}">

<!-- Close Button (x) -->
<button type="button" x-on:click="event.preventDefault(); event.stopPropagation(); const f=$el.closest('.fi-modal-window'); const m=f?(f.getAttribute('x-on:keydown.window.escape')||'').match(/'(fi-[^']*)'/):null; if(m){$dispatch('close-modal',{id:m[1]})}else{$wire.unmountAction()}"
        style="position:absolute;top:16px;right:16px;z-index:50;width:28px;height:28px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;transition:color .15s,background-color .15s"
        onmouseover="this.style.color='#374151';this.style.backgroundColor='#e5e7eb'"
        onmouseout="this.style.color='#6b7280';this.style.backgroundColor='#f3f4f6'"
        title="{{ e($closeButtonText) }}">
    <svg style="width:16px;height:16px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>

<div style="text-align:center;padding:4px 0 8px">
    <p style="font-size:13px;color:#9ca3af;margin:0">{{ $qrHelperText }}</p>
</div>

<!-- URL pill -->
<div style="display:flex;align-items:center;gap:10px;background:#EFF6FF;border-radius:999px;padding:10px 14px;margin:18px 0 0">
    <div style="width:30px;height:30px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #e5e7eb">
        <img src="https://icons.duckduckgo.com/ip2/{{ e($destHost) }}.ico" style="width:16px;height:16px;object-fit:contain" onerror="this.style.display='none'">
    </div>
    <span style="flex:1;font-size:14px;font-weight:600;color:#1d4ed8;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $shortUrl }}</span>
    <div style="display:flex;align-items:center;gap:4px;flex-shrink:0">
        <button id="{{ e($eid) }}_copy" type="button"
            onclick="
                event.preventDefault();
                event.stopPropagation();
                const u='{{ e($shortUrl) }}';
                if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(u);}
                else{const t=document.createElement('textarea');t.value=u;t.style.cssText='position:fixed;left:-9999px';document.body.appendChild(t);t.select();document.execCommand('copy');t.remove();}
                const b=document.getElementById('{{ e($eid) }}_copy');
                const prev=b.innerHTML;
                b.innerHTML='<svg style=\'width:16px;height:16px;color:#16a34a\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M5 13l4 4L19 7\'/></svg>';
                setTimeout(()=>b.innerHTML=prev,1800);
            "
            style="width:30px;height:30px;border-radius:7px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .15s"
            onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'" title="{{ e($copyLinkText) }}">
            <svg style="width:16px;height:16px;color:#6b7280" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 15H4a2 2 0 01-2-2V6a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
        </button>
        <a href="{{ $shortUrl }}" target="_blank" rel="noopener noreferrer"
           style="width:30px;height:30px;border-radius:7px;border:1px solid #e5e7eb;background:#fff;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:background .15s"
           onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'" title="{{ e($openLinkText) }}">
            <svg style="width:15px;height:15px;color:#6b7280" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
    </div>
</div>

<!-- QR Code Container (Visible immediately) -->
<div id="{{ e($eid) }}_qr_container" style="display:block;margin:16px 0 0;text-align:center;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:16px">
    <div style="display:flex;justify-content:center;margin-bottom:12px">
        <div id="{{ e($eid) }}_qr_canvas" style="background:#fff;padding:8px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.05)"></div>
    </div>
    <div style="display:flex;justify-content:center;gap:8px">
        <button type="button" onclick="event.preventDefault();event.stopPropagation();window['qr_{{ e($eid) }}']?.download({ name: '{{ e($urlKey) }}-qr', extension: 'svg' })" style="font-size:12px;font-weight:600;color:#374151;border:1.5px solid #e5e7eb;background:#fff;padding:6px 12px;border-radius:8px;cursor:pointer;transition:background .15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">{{ $downloadSvgText }}</button>
        <button type="button" onclick="event.preventDefault();event.stopPropagation();window['qr_{{ e($eid) }}']?.download({ name: '{{ e($urlKey) }}-qr', extension: 'png' })" style="font-size:12px;font-weight:600;color:#374151;border:1.5px solid #e5e7eb;background:#fff;padding:6px 12px;border-radius:8px;cursor:pointer;transition:background .15s" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">{{ $downloadPngText }}</button>
    </div>
</div>
