if (!window.QrHelper) {
    window.QrHelper = {
        scriptPromise: null,
        loadScript() {
            if (window.QRCodeStyling) return Promise.resolve();
            if (this.scriptPromise) return this.scriptPromise;

            this.scriptPromise = new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.id = 'qr-styling-script-tag';
                s.src = '/js/janczakb/filament-short-url/qr-code-styling.js';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
            return this.scriptPromise;
        },

        processLogo(logo, logoShape, logoMargin) {
            if (!logo) return Promise.resolve('');

            const processImage = (img, hasCrossOrigin) => {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = 1200;
                    canvas.height = 1200;
                    const ctx = canvas.getContext('2d');
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';

                    const isCircle = logoShape === 'circle';
                    const targetDim = 1200;

                    ctx.save();
                    if (isCircle) {
                        ctx.beginPath();
                        ctx.arc(600, 600, targetDim / 2, 0, 2 * Math.PI);
                        ctx.clip();

                        const scale = Math.max(targetDim / img.width, targetDim / img.height);
                        const w = img.width * scale;
                        const h = img.height * scale;
                        const x = (1200 - w) / 2;
                        const y = (1200 - h) / 2;

                        ctx.drawImage(img, x, y, w, h);
                    } else {
                        ctx.beginPath();
                        const offset = (1200 - targetDim) / 2;
                        const radius = 144 * (targetDim / 1200);

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
                    if (hasCrossOrigin) throw e;
                    return logo;
                }
            };

            return new Promise((resolve) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';

                const srcUrl = (logo.startsWith('data:') || logo.startsWith('blob:'))
                    ? logo
                    : logo + (logo.includes('?') ? '&' : '?') + 't=' + new Date().getTime();

                img.onload = () => {
                    try {
                        resolve(processImage(img, true));
                    } catch (e) {
                        loadWithoutCORS();
                    }
                };

                const loadWithoutCORS = () => {
                    const imgRetry = new Image();
                    imgRetry.onload = () => {
                        try {
                            resolve(processImage(imgRetry, false));
                        } catch (err) {
                            resolve(logo);
                        }
                    };
                    imgRetry.onerror = () => {
                        resolve('');
                    };
                    imgRetry.src = srcUrl;
                };

                img.onerror = loadWithoutCORS;
                img.src = srcUrl;
            });
        },

        buildOptions(state, url, size) {
            const isGrad = state.colorMode === 'gradient';
            const dotsOptions = isGrad
                ? { type: state.dotStyle, gradient: { type: state.gradientType,
                    colorStops: [{ offset: 0, color: state.gradientFrom }, { offset: 1, color: state.gradientTo }] } }
                : { type: state.dotStyle, color: state.fgColor };

            const mainColor = isGrad ? state.gradientFrom : state.fgColor;
            const eyeSq = state.eyeConfigEnabled
                ? { type: state.eyeSquareStyle, color: state.eyeColor }
                : { type: state.dotStyle === 'dots' ? 'dot' : 'square', color: mainColor };
            const eyeDt = state.eyeConfigEnabled
                ? { type: state.eyeDotStyle, color: state.eyeColor }
                : { type: state.dotStyle === 'dots' ? 'dot' : 'square', color: mainColor };

            const options = {
                type: 'svg',
                width: size, height: size,
                data: url, margin: 1,
                dotsOptions,
                backgroundOptions: state.bgTransparent ? { color: 'rgba(0,0,0,0)' } : { color: state.bgColor },
                cornersSquareOptions: eyeSq,
                cornersDotOptions: eyeDt,
                qrOptions: { errorCorrectionLevel: state.logo ? 'H' : 'M' },
            };

            if (state.processedLogo) {
                options.image = state.processedLogo;
                options.imageOptions = {
                    crossOrigin: 'anonymous',
                    hideBackgroundDots: false, // Override to false so we can apply our mask instead of grid clearing
                    imageSize: parseFloat(state.logoSize) || 0.3,
                    margin: 0, // Override to 0 so the logo size remains constant
                    logoShape: state.logoShape,
                    actualMargin: state.logoMargin // Keep the actual margin here for our mask processor
                };
            }

            return options;
        },

        render(el, opts, fixIds = false) {
            if (!window.QRCodeStyling || !el) return null;
            el.innerHTML = '';
            const qr = new window.QRCodeStyling(opts);
            qr.append(el);

            const promise = qr._svgDrawingPromise || Promise.resolve();
            qr.drawingPromise = promise.then(() => {
                const svg = el.querySelector('svg');
                if (svg) {
                    const prefix = 'qr-' + Math.random().toString(36).substr(2, 9);
                    if (fixIds) {
                        const elementsWithId = svg.querySelectorAll('[id]');
                        const idMap = new Map();
                        
                        elementsWithId.forEach(elem => {
                            const oldId = elem.id;
                            const newId = prefix + '-' + oldId;
                            elem.id = newId;
                            idMap.set(oldId, newId);
                        });
                        
                        const attrsToUpdate = ['fill', 'stroke', 'clip-path', 'filter'];
                        attrsToUpdate.forEach(attrName => {
                            svg.querySelectorAll(`[${attrName}]`).forEach(elem => {
                                const val = elem.getAttribute(attrName);
                                if (val && val.includes('url(')) {
                                    const match = val.match(/url\(\s*['"]?#([^'"]+?)['"]?\s*\)/);
                                    if (match && match[1]) {
                                        const oldId = match[1];
                                        if (idMap.has(oldId)) {
                                            elem.setAttribute(attrName, `url(#${idMap.get(oldId)})`);
                                        }
                                    }
                                }
                            });
                        });
                        
                        svg.querySelectorAll('use, image').forEach(elem => {
                            ['href', 'xlink:href'].forEach(attrName => {
                                const val = elem.getAttribute(attrName);
                                if (val && val.startsWith('#')) {
                                    const oldId = val.substring(1);
                                    if (idMap.has(oldId)) {
                                        elem.setAttribute(attrName, `#${idMap.get(oldId)}`);
                                    }
                                }
                            });
                        });
                    }

                    // Custom shape clearing for logo background dots via SVG mask
                    const logoShape = opts.imageOptions?.logoShape;
                    if (logoShape) {
                        const width = parseFloat(svg.getAttribute('width')) || opts.width || 300;
                        const height = parseFloat(svg.getAttribute('height')) || opts.height || 300;
                        const imageSize = opts.imageOptions.imageSize || 0.3;
                        const logoMargin = parseFloat(opts.imageOptions.actualMargin) || 0;
                        // Scale the margin proportionally to the canvas size (default base width is 280)
                        const scaledMargin = logoMargin * (width / 280);
                        
                        // Calculate exact logo size matching QRCodeStyling's internal drawQR/drawImage hidden dots logic
                        let hideXDots = 7;
                        let dotSize = 9;
                        if (qr._qr) {
                            const e = qr._qr.getModuleCount();
                            const r = width - 2 * (opts.margin || 0);
                            dotSize = Math.floor(r / e);
                            
                            const errorCorrectionLevel = opts.qrOptions?.errorCorrectionLevel || 'M';
                            const coefficients = { L: 0.07, M: 0.15, Q: 0.25, H: 0.3 };
                            const coeff = coefficients[errorCorrectionLevel] || 0.15;
                            const c = imageSize * coeff;
                            const l = Math.floor(c * e * e);
                            
                            hideXDots = Math.floor(Math.sqrt(l));
                            if (hideXDots <= 0) hideXDots = 1;
                            const maxHiddenAxisDots = e - 14;
                            if (maxHiddenAxisDots > 0 && hideXDots > maxHiddenAxisDots) {
                                hideXDots = maxHiddenAxisDots;
                            }
                            if (hideXDots % 2 === 0) {
                                hideXDots--;
                            }
                            if (hideXDots < 1) hideXDots = 1;
                        } else {
                            // Fallback estimate for standard QR version 3
                            hideXDots = Math.floor(Math.sqrt(imageSize * 0.3 * 29 * 29));
                            if (hideXDots % 2 === 0) hideXDots--;
                            dotSize = width / 29;
                        }
                        const libraryClearedSize = hideXDots * dotSize;
                        const clearedSize = libraryClearedSize + 2 * scaledMargin;
                        
                        const defs = svg.querySelector('defs') || svg.appendChild(document.createElementNS('http://www.w3.org/2000/svg', 'defs'));
                        if (defs) {
                            const mask = document.createElementNS('http://www.w3.org/2000/svg', 'mask');
                            mask.setAttribute('id', `${prefix}-qr-logo-mask`);
                            
                            const maskBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                            maskBg.setAttribute('x', '0');
                            maskBg.setAttribute('y', '0');
                            maskBg.setAttribute('width', width.toString());
                            maskBg.setAttribute('height', height.toString());
                            maskBg.setAttribute('fill', 'white');
                            mask.appendChild(maskBg);
                            
                            if (logoShape === 'circle') {
                                const maskCut = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                                maskCut.setAttribute('cx', (width / 2).toString());
                                maskCut.setAttribute('cy', (height / 2).toString());
                                maskCut.setAttribute('r', (clearedSize / 2).toString());
                                maskCut.setAttribute('fill', 'black');
                                mask.appendChild(maskCut);
                            } else {
                                const maskCut = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                                const x = width / 2 - clearedSize / 2;
                                const y = height / 2 - clearedSize / 2;
                                maskCut.setAttribute('x', x.toString());
                                maskCut.setAttribute('y', y.toString());
                                maskCut.setAttribute('width', clearedSize.toString());
                                maskCut.setAttribute('height', clearedSize.toString());
                                maskCut.setAttribute('rx', (clearedSize * 0.12).toString());
                                maskCut.setAttribute('ry', (clearedSize * 0.12).toString());
                                maskCut.setAttribute('fill', 'black');
                                mask.appendChild(maskCut);
                            }
                            
                            defs.appendChild(mask);
                            
                            // Find the dots element and wrap it in a masked group
                            const dotsElement = svg.querySelector('[clip-path*="clip-path-dot-color"]');
                            if (dotsElement) {
                                const parent = dotsElement.parentNode;
                                const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                                g.setAttribute('mask', `url(#${prefix}-qr-logo-mask)`);
                                parent.replaceChild(g, dotsElement);
                                g.appendChild(dotsElement);
                            }
                        }
                    }

                    const newSvg = el.querySelector('svg');
                    if (newSvg) {
                        const w = newSvg.getAttribute('width') || opts.width || 300;
                        const h = newSvg.getAttribute('height') || opts.height || 300;
                        newSvg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
                        newSvg.style.width = '100%';
                        newSvg.style.height = '100%';
                        newSvg.style.maxWidth = '100%';
                        newSvg.style.maxHeight = '100%';
                    }
                }
            });

            return qr;
        }
    };
    window.QrHelper.loadScript();
}

document.addEventListener('alpine:init', () => {
    Alpine.data('shortUrlQrPreview', (config) => ({
        qrInstance: null,
        url: '',
        processedLogo: '',
        
        dotStyle: config.options.dot_style ?? 'square',
        colorMode: config.options.color_mode ?? 'solid',
        fgColor: config.options.foreground_color ?? '#000000',
        gradientFrom: config.options.gradient_from ?? '#4f46e5',
        gradientTo: config.options.gradient_to ?? '#06b6d4',
        gradientType: config.options.gradient_type ?? 'linear',
        bgTransparent: !!config.options.bg_transparent,
        bgColor: config.options.background_color ?? '#ffffff',
        eyeConfigEnabled: !!config.options.eye_config_enabled,
        eyeSquareStyle: config.options.eye_square_style ?? 'square',
        eyeDotStyle: config.options.eye_dot_style ?? 'square',
        eyeColor: config.options.eye_color ?? '#000000',
        logo: config.logo ?? '',
        logoSize: config.options.logo_size ?? 0.55,
        logoMargin: config.options.logo_margin ?? 8,
        logoHideBackground: true,
        logoShape: config.options.logo_shape ?? 'square',
        
        domains: config.domains,
        defaultDomain: config.defaultDomain,
        routePrefix: config.routePrefix,
        defaultUrlKey: config.defaultUrlKey,
        protocol: config.protocol || 'https',
        
        getLogoUrl(logoFile) {
            if (!logoFile) return '';
            
            let logoPath = null;
            if (typeof logoFile === 'string') {
                logoPath = logoFile;
            } else if (Array.isArray(logoFile)) {
                logoPath = logoFile[0];
            } else if (logoFile && typeof logoFile === 'object') {
                const vals = Object.values(logoFile);
                if (vals.length > 0) {
                    logoPath = vals[0];
                }
            }

            if (!logoPath || typeof logoPath !== 'string') return '';

            if (/^(https?:|data:)/.test(logoPath)) {
                return logoPath;
            }

            if (logoPath.startsWith('short-urls/')) {
                return `/storage/${logoPath}`;
            }

            return `/short-url/logo/${logoPath.split('/').pop()}`;
        },

        init() {
            this.designUpdatedHandler = (event) => {
                // Livewire v4 dispatches plain objects; v3 wrapped them in an array.
                const payload = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                if (payload?.options) {
                    this.applyQrDesign(payload.options, payload.logo ?? null);
                }
            };
            window.addEventListener('qr-design-updated', this.designUpdatedHandler);

            this.$nextTick(() => {
                const optionsInput = document.getElementById('qr-options-json-input');
                if (optionsInput && optionsInput.value && optionsInput.value !== '[object Object]') {
                    try {
                        const val = JSON.parse(optionsInput.value);
                        if (val && typeof val === 'object') {
                            this.applyState(val);
                        }
                    } catch (err) {
                        console.error('QR Preview: Error parsing initial options:', err);
                    }
                }
                const logoInput = document.getElementById('qr-logo-path-input');
                if (logoInput && logoInput.value) {
                    this.logo = this.getLogoUrl(logoInput.value);
                }
                this.updateUrl();

                window.QrHelper.loadScript().then(() => {
                    this.updateProcessedLogo().then(() => {
                        this.render();
                    });
                });
            });

            const container = this.$el.closest('form') ||
                              this.$el.closest('.fi-modal-window') ||
                              this.$el.closest('.fi-layout') ||
                              document;

            this.inputHandler = (e) => {
                if (e.target) {
                    const name = e.target.name || '';
                    const id = e.target.id || '';
                    if (name.includes('url_key') || id.includes('url_key') ||
                        name.includes('custom_domain_id') || id.includes('custom_domain_id')) {
                        this.updateUrl();
                        this.render();
                    }
                }
            };

            container.addEventListener('input', this.inputHandler);
            container.addEventListener('change', this.inputHandler);
        },

        destroy() {
            if (this.designUpdatedHandler) {
                window.removeEventListener('qr-design-updated', this.designUpdatedHandler);
            }
            const container = this.$el.closest('form') ||
                              this.$el.closest('.fi-modal-window') ||
                              this.$el.closest('.fi-layout') ||
                              document;
            if (this.inputHandler) {
                container.removeEventListener('input', this.inputHandler);
                container.removeEventListener('change', this.inputHandler);
            }
        },

        applyState(val) {
            if (val.dot_style            !== undefined) this.dotStyle           = val.dot_style;
            if (val.color_mode           !== undefined) this.colorMode          = val.color_mode;
            if (val.foreground_color     !== undefined) this.fgColor            = val.foreground_color;
            if (val.gradient_from        !== undefined) this.gradientFrom       = val.gradient_from;
            if (val.gradient_to          !== undefined) this.gradientTo         = val.gradient_to;
            if (val.gradient_type        !== undefined) this.gradientType       = val.gradient_type;
            if (val.bg_transparent       !== undefined) this.bgTransparent      = !!val.bg_transparent;
            if (val.background_color     !== undefined) this.bgColor            = val.background_color;
            if (val.eye_config_enabled   !== undefined) this.eyeConfigEnabled   = !!val.eye_config_enabled;
            if (val.eye_square_style     !== undefined) this.eyeSquareStyle     = val.eye_square_style;
            if (val.eye_dot_style        !== undefined) this.eyeDotStyle        = val.eye_dot_style;
            if (val.eye_color            !== undefined) this.eyeColor           = val.eye_color;
            if (val.logo_size            !== undefined) this.logoSize           = val.logo_size;
            if (val.logo_margin          !== undefined) this.logoMargin         = val.logo_margin;
            this.logoHideBackground = true;
            if (val.logo_shape           !== undefined) this.logoShape          = val.logo_shape;
            if (val.logo                 !== undefined) {
                this.logo = this.getLogoUrl(val.logo);
            }
        },

        updateProcessedLogo() {
            return window.QrHelper.processLogo(this.logo, this.logoShape, this.logoMargin).then(res => {
                this.processedLogo = res;
            });
        },

        calculateUrl() {
            const keyInput = document.querySelector('[name$="url_key"]') || document.querySelector('[id$="url_key"]');
            const key = keyInput?.value || this.defaultUrlKey || 'preview';

            const domainSelect = document.querySelector('[name$="custom_domain_id"]') || document.querySelector('[id$="custom_domain_id"]');
            const domainId = domainSelect?.value || '';
            let domain = this.defaultDomain;

            if (domainId && this.domains[domainId]) {
                domain = this.domains[domainId];
            }

            if (this.routePrefix && !domainId) {
                return `${this.protocol}://${domain}/${this.routePrefix}/${key}`;
            }

            return `${this.protocol}://${domain}/${key}`;
        },

        updateUrl() {
            this.url = this.calculateUrl();
        },

        buildOptions() {
            return window.QrHelper.buildOptions(this, this.url, 200);
        },

        render() {
            const el = this.$refs.sidebarQrCanvas;
            if (el) {
                this.qrInstance = window.QrHelper.render(el, this.buildOptions(), true);
            }
        },

        applyQrDesign(opts, logo = null) {
            if (!opts) return;

            this.applyState(opts);

            // Update logo if provided (path stored in qr_logo field)
            if (logo !== null) {
                this.logo = this.getLogoUrl(logo);
            }

            this.updateProcessedLogo().then(() => {
                this.updateUrl();
                this.render();
            });
        }
    }));

    /**
     * Alpine component powering the live QR preview inside the designer modal.
     *
     * Architecture
     * ────────────
     * Filament / Livewire v4 stores each action's form data at a container path
     * such as "mountedActions.1.data".  The full container object is not exposed
     * via $wire.$get, but every scalar leaf (e.g. "mountedActions.1.data.dot_style")
     * resolves correctly.  We discover the containerPath once from the nearest
     * filamentSchemaComponent ancestor, then register one $wire.$watch per design
     * field.  These are purely client-side reactive subscriptions — zero network
     * overhead.  All change callbacks are funnelled through a 50 ms debounce so
     * that a single user interaction producing N field changes yields one QR render.
     */
    const QR_FIELDS = Object.freeze([
        'dot_style', 'color_mode',
        'foreground_color', 'gradient_from', 'gradient_to', 'gradient_type',
        'bg_transparent', 'background_color',
        'eye_config_enabled', 'eye_square_style', 'eye_dot_style', 'eye_color',
        'logo_shape', 'logo_size', 'logo_margin', 'logo_hide_background', 'logo_file',
    ]);

    Alpine.data('shortUrlQrDesignerPreview', (config) => ({

        // ── Public config ─────────────────────────────────────────────────────
        url: config.url,

        // ── QR visual state ───────────────────────────────────────────────────
        dotStyle: 'square', colorMode: 'solid',
        fgColor: '#000000',
        gradientFrom: '#4f46e5', gradientTo: '#06b6d4', gradientType: 'linear',
        bgTransparent: false, bgColor: '#ffffff',
        eyeConfigEnabled: false,
        eyeSquareStyle: 'square', eyeDotStyle: 'square', eyeColor: '#000000',
        logo: '', processedLogo: '',
        logoSize: 0.55, logoMargin: 8, logoHideBackground: true, logoShape: 'square',

        // ── Internal ──────────────────────────────────────────────────────────
        _activePondFile: null,
        qrInstance:      null,
        _containerPath:  null,
        _fieldWatchers:  [],     // $wire.$watch unsubscribe callbacks
        _debounceTimer:  null,
        _onFileAdd:      null,
        _onFileRemove:   null,
        _accordionHandler: null,

        // ── Lifecycle ─────────────────────────────────────────────────────────

        init() {
            this._containerPath = this._resolveContainerPath();

            if (!this._containerPath) {
                return; // no form context — nothing to preview
            }

            // Register one client-side $wire.$watch per design field.
            // Livewire v4's $wire.$watch is reactive and carries zero network cost.
            for (const field of QR_FIELDS) {
                const unwatch = this.$wire.$watch(
                    `${this._containerPath}.${field}`,
                    () => this._scheduleRender()
                );
                this._fieldWatchers.push(unwatch);
            }

            // FilePond file-picker events (logo upload)
            const modal = this.$el.closest('.fi-modal-window');
            if (modal) {
                this._onFileAdd = (e) => {
                    console.log('[QR Preview] FilePond:addfile event triggered', e.detail);
                    const fileItem = e.detail?.file;
                    if (!fileItem) return;
                    if (fileItem.origin === 3) {
                        console.log('[QR Preview] Ignoring initial server file in addfile event');
                        return;
                    }
                    const file = fileItem.file;
                    if (file) {
                        console.log('[QR Preview] Found local file in event:', file);
                        this._activePondFile = file;
                        if (this.logo && this.logo.startsWith('blob:')) {
                            URL.revokeObjectURL(this.logo);
                        }
                        this.logo = URL.createObjectURL(file);
                        this._processLogo().then(() => {
                            console.log('[QR Preview] Local logo processed, rendering...');
                            this.render();
                        });
                    } else {
                        console.log('[QR Preview] No file in event, falling back to fields read...');
                        const data = this._readFields();
                        if (data) this._resolveLogo(data.logo_file).then(() => this.render());
                    }
                };
                this._onFileRemove = () => {
                    console.log('[QR Preview] FilePond:removefile event triggered');
                    this._activePondFile = null;
                    if (this.logo && this.logo.startsWith('blob:')) {
                        URL.revokeObjectURL(this.logo);
                    }
                    this.logo = '';
                    this.processedLogo = '';
                    this.render();
                };
                modal.addEventListener('FilePond:addfile', this._onFileAdd);
                modal.addEventListener('FilePond:removefile', this._onFileRemove);
            }

            // Accordion: opening one section collapses all sibling sections.
            this._initAccordion(modal);

            // Initial paint — deferred so Livewire has hydrated all field values.
            this.$nextTick(() => this._refresh());
        },

        destroy() {
            this._fieldWatchers.forEach(unwatch => unwatch?.());
            this._fieldWatchers = [];
            clearTimeout(this._debounceTimer);

            const modal = this.$el.closest('.fi-modal-window');
            if (modal) {
                if (this._onFileAdd)    modal.removeEventListener('FilePond:addfile',    this._onFileAdd);
                if (this._onFileRemove) modal.removeEventListener('FilePond:removefile', this._onFileRemove);
                if (this._accordionHandler) modal.removeEventListener('click', this._accordionHandler);
            }
        },

        // ── Private helpers ───────────────────────────────────────────────────

        /**
         * Extract the Filament schema containerPath from the nearest ancestor element
         * carrying a filamentSchemaComponent x-data declaration.
         */
        _resolveContainerPath() {
            const ancestor = this.$el.closest('[x-data*="containerPath"]');
            if (!ancestor) return null;
            const m = (ancestor.getAttribute('x-data') ?? '').match(
                /containerPath\s*:\s*['"]([^'"]+)['"]/
            );
            return m?.[1] ?? null;
        },

        /**
         * Accordion behavior for the left-panel sections.
         *
         * Filament's <x-filament::section> already handles the
         * `collapse-section.window` event natively (see support section blade).
         * We detect which section was just OPENED and dispatch that event for
         * every other sibling fi-section so they auto-collapse.
         */
        _initAccordion(container) {
            if (!container) return;

            this._accordionHandler = (e) => {
                const header = e.target.closest('.fi-section-header');
                if (!header) return;

                // The inner <section class="fi-section"> is where Alpine isCollapsed lives
                const clickedInner = header.closest('.fi-section');
                if (!clickedInner) return;

                // Wait for Alpine to apply the fi-collapsed class
                this.$nextTick(() => {
                    // Only collapse siblings when this section just OPENED (not collapsed)
                    if (clickedInner.classList.contains('fi-collapsed')) return;

                    // Walk up to outer wrapper: div.fi-sc-section — that has the ID
                    // that Filament uses as collapseId in x-on:collapse-section.window
                    container.querySelectorAll('.fi-sc-section').forEach(outerWrapper => {
                        const innerSection = outerWrapper.querySelector(':scope > .fi-section, :scope > x-filament\.section > .fi-section, .fi-section');
                        if (!innerSection || innerSection === clickedInner) return;
                        if (innerSection.classList.contains('fi-collapsed')) return; // already collapsed

                        // collapseId matches the outer wrapper's id attribute
                        const collapseId = outerWrapper.id;
                        if (collapseId) {
                            window.dispatchEvent(
                                new CustomEvent('collapse-section', { detail: { id: collapseId } })
                            );
                        }
                    });
                });
            };

            container.addEventListener('click', this._accordionHandler);
        },

        /** Coalesce rapid multi-field changes into a single render. */
        _scheduleRender() {
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(() => this._refresh(), 50);
        },

        _refresh() {
            if (!this.$el?.isConnected) return;
            const data = this._readFields();
            if (data) this.applyActionData(data);
        },

        /**
         * Read every design field via individual $wire.$get leaf-path calls.
         *
         * Livewire v4's $wire proxy resolves scalar leaf values correctly, but does
         * not expose the container object itself through $get.  Reading fields one by
         * one is therefore the canonical approach for nested action form data.
         *
         * "dot_style" acts as the mount probe: undefined means the action form has
         * not yet been fully hydrated by Livewire.
         */
        _readFields() {
            const path = this._containerPath;
            if (!path) return null;

            const g = (field) => {
                try { return this.$wire.$get(`${path}.${field}`); } catch { return undefined; }
            };

            const dotStyle = g('dot_style');
            if (dotStyle === undefined) return null; // form not ready yet

            return {
                dot_style:            dotStyle,
                color_mode:           g('color_mode'),
                foreground_color:     g('foreground_color'),
                gradient_from:        g('gradient_from'),
                gradient_to:          g('gradient_to'),
                gradient_type:        g('gradient_type'),
                bg_transparent:       g('bg_transparent'),
                background_color:     g('background_color'),
                eye_config_enabled:   g('eye_config_enabled'),
                eye_square_style:     g('eye_square_style'),
                eye_dot_style:        g('eye_dot_style'),
                eye_color:            g('eye_color'),
                logo_shape:           g('logo_shape'),
                logo_size:            g('logo_size'),
                logo_margin:          g('logo_margin'),
                logo_hide_background: g('logo_hide_background'),
                logo_file:            g('logo_file'),
            };
        },

        // ── State application ─────────────────────────────────────────────────

        applyActionData(data) {
            this.dotStyle           = data.dot_style         ?? 'square';
            this.colorMode          = data.color_mode        ?? 'solid';
            this.fgColor            = data.foreground_color  ?? '#000000';
            this.gradientFrom       = data.gradient_from     ?? '#4f46e5';
            this.gradientTo         = data.gradient_to       ?? '#06b6d4';
            this.gradientType       = data.gradient_type     ?? 'linear';
            this.bgTransparent      = !!data.bg_transparent;
            this.bgColor            = data.background_color  ?? '#ffffff';
            this.eyeConfigEnabled   = !!data.eye_config_enabled;
            this.eyeSquareStyle     = data.eye_square_style  ?? 'square';
            this.eyeDotStyle        = data.eye_dot_style     ?? 'square';
            this.eyeColor           = data.eye_color         ?? '#000000';
            this.logoShape          = data.logo_shape        ?? 'square';
            this.logoSize           = parseFloat(data.logo_size)   || 0.55;
            this.logoMargin         = parseFloat(data.logo_margin)  || 8;
            this.logoHideBackground = true;

            this._resolveLogo(data.logo_file).then(() => this.render());
        },

        _resolveLogo(logoFile) {
            console.log('[QR Preview] Resolving logo with:', logoFile);
            
            if (this._activePondFile) {
                console.log('[QR Preview] Using active FilePond file:', this._activePondFile);
                if (!this.logo || !this.logo.startsWith('blob:')) {
                    this.logo = URL.createObjectURL(this._activePondFile);
                }
                return this._processLogo();
            }

            let logoPath = null;
            if (typeof logoFile === 'string') {
                logoPath = logoFile;
            } else if (Array.isArray(logoFile)) {
                logoPath = logoFile[0];
            } else if (logoFile && typeof logoFile === 'object') {
                const vals = Object.values(logoFile);
                if (vals.length > 0) {
                    logoPath = vals[0];
                }
            }

            if (logoPath && typeof logoPath === 'string') {
                if (this.logo && this.logo.startsWith('blob:')) {
                    URL.revokeObjectURL(this.logo);
                }
                this.logo = /^(https?:|data:)/.test(logoPath)
                    ? logoPath
                    : `/short-url/logo/${logoPath.split('/').pop()}`;
                console.log('[QR Preview] Resolved logo URL from path:', this.logo);
                return this._processLogo();
            }

            console.log('[QR Preview] No logo file to resolve.');
            if (this.logo && this.logo.startsWith('blob:')) {
                URL.revokeObjectURL(this.logo);
            }
            this.logo = '';
            this.processedLogo = '';
            return Promise.resolve();
        },

        _processLogo() {
            console.log('[QR Preview] Processing logo image:', this.logo);
            return window.QrHelper
                .processLogo(this.logo, this.logoShape, this.logoMargin)
                .then(result => { 
                    this.processedLogo = result; 
                    console.log('[QR Preview] Processed logo data URI length:', result ? result.length : 0);
                });
        },

        // ── Public API ────────────────────────────────────────────────────────

        buildOptions() {
            return window.QrHelper.buildOptions(this, this.url, 280);
        },

        render() {
            const canvas = this.$refs.qrCanvas;
            if (!canvas) {
                console.log('[QR Preview] Canvas ref not found!');
                return;
            }
            console.log('[QR Preview] Rendering QR with options:', this.buildOptions());
            window.QrHelper.loadScript().then(() => {
                this.qrInstance = window.QrHelper.render(canvas, this.buildOptions(), true);
            });
        },

        download(extension) {
            const exportOpts = window.QrHelper.buildOptions(this, this.url, 2000);
            
            // Render to a temporary container to apply our custom post-processing (like circular clip-path)
            const tempDiv = document.createElement('div');
            tempDiv.style.display = 'none';
            document.body.appendChild(tempDiv);
            
            window.QrHelper.loadScript().then(() => {
                const qr = window.QrHelper.render(tempDiv, exportOpts, true);
                if (!qr) {
                    if (tempDiv.parentNode) document.body.removeChild(tempDiv);
                    return;
                }
                
                qr.drawingPromise.then(() => {
                    const svg = tempDiv.querySelector('svg');
                    if (!svg) {
                        if (tempDiv.parentNode) document.body.removeChild(tempDiv);
                        return;
                    }
                    
                    if (extension === 'svg') {
                        const svgString = new XMLSerializer().serializeToString(svg);
                        const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
                        const svgUrl = URL.createObjectURL(svgBlob);
                        
                        const a = document.createElement('a');
                        a.href = svgUrl;
                        a.download = 'qr-code.svg';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(svgUrl);
                        if (tempDiv.parentNode) document.body.removeChild(tempDiv);
                    } else {
                        // For PNG, draw the modified SVG to a canvas
                        const svgString = new XMLSerializer().serializeToString(svg);
                        const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' });
                        const svgUrl = URL.createObjectURL(svgBlob);
                        
                        const img = new Image();
                        img.onload = () => {
                            const canvas = document.createElement('canvas');
                            canvas.width = 2000;
                            canvas.height = 2000;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(img, 0, 0);
                            
                            canvas.toBlob((blob) => {
                                const pngUrl = URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = pngUrl;
                                a.download = 'qr-code.png';
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                                URL.revokeObjectURL(pngUrl);
                                URL.revokeObjectURL(svgUrl);
                                if (tempDiv.parentNode) document.body.removeChild(tempDiv);
                            }, 'image/png');
                        };
                        img.src = svgUrl;
                    }
                });
            });
        },
    }));
});
