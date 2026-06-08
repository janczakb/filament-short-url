window.addEventListener('error', function (e) {
    var errorData = {
        message: e.message,
        file: e.filename,
        line: e.lineno,
        col: e.colno,
        stack: e.error ? e.error.stack : ''
    };
    fetch('/short-url/log-error', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify(errorData)
    }).catch(function () {});
});

window.fsuDispatchScraping = function (isScraping) {
    window.dispatchEvent(new CustomEvent(isScraping ? 'fsu-scraping-start' : 'fsu-scraping-end'));
};

window.fsuResolveFormApi = function ($get, $set, $el) {
    if (typeof $get === 'function' && typeof $set === 'function') {
        return {
            get: $get,
            set: function (path, value) {
                $set(path, value, false, true);
            },
        };
    }

    var host = $el || null;

    if (host && typeof host.closest === 'function') {
        var componentEl = host.closest('[x-data*="filamentSchemaComponent"]');

        if (componentEl && typeof Alpine !== 'undefined') {
            var data = Alpine.$data(componentEl);

            if (data && typeof data.$get === 'function' && typeof data.$set === 'function') {
                return {
                    get: data.$get.bind(data),
                    set: function (path, value) {
                        data.$set(path, value, false, true);
                    },
                };
            }
        }
    }

    return null;
};

window.fsuIsPasswordProtected = function (api) {
    if (!api || typeof api.get !== 'function') {
        return false;
    }

    return !!(api.get('password_active_flag') || api.get('password'));
};

window.fsuHasManualOgImage = function (get) {
    if (typeof get !== 'function') {
        return false;
    }

    var image = get('og_image');

    if (!image) {
        return false;
    }

    if (typeof image === 'string') {
        return image.trim() !== '';
    }

    if (Array.isArray(image)) {
        return image.length > 0;
    }

    if (typeof image === 'object') {
        return Object.keys(image).length > 0;
    }

    return false;
};

window.fsuLockScrape = function (inputEl) {
    if (inputEl) {
        inputEl.dataset.fsuScrapeLocked = 'true';

        return;
    }

    document.querySelectorAll('[data-fsu-destination-url]').forEach(function (el) {
        el.dataset.fsuScrapeLocked = 'true';
    });
};

window.fsuUnlockScrape = function (inputEl) {
    if (inputEl) {
        delete inputEl.dataset.fsuScrapeLocked;

        return;
    }

    document.querySelectorAll('[data-fsu-destination-url]').forEach(function (el) {
        delete el.dataset.fsuScrapeLocked;
    });
};

window.fsuIsScrapeLocked = function (inputEl, get) {
    if (inputEl && inputEl.dataset.fsuScrapeLocked === 'true') {
        return true;
    }

    return window.fsuHasManualOgImage(get);
};

window.fsuGetScrapedUrl = function (inputEl) {
    return inputEl && inputEl.dataset.scrapedUrl ? inputEl.dataset.scrapedUrl : null;
};

window.fsuMarkDestinationScraped = function (inputEl, url) {
    if (inputEl && url) {
        inputEl.dataset.scrapedUrl = url;
    }
};

window.fsuClearDestinationScrape = function (inputEl) {
    if (inputEl) {
        delete inputEl.dataset.scrapedUrl;
    }
};

window.fsuShouldSkipScrape = function (inputEl, url, get) {
    if (window.fsuIsScrapeLocked(inputEl, get)) {
        return true;
    }

    return window.fsuGetScrapedUrl(inputEl) === url;
};

window.fsuStopScraping = function (api) {
    window.fsuDispatchScraping(false);
    window.dispatchEvent(new CustomEvent('fsu-og-image-updated'));

    if (api && typeof api.set === 'function') {
        api.set('is_scraping', false);
    }
};

window.fsuOnManualOgImage = function ($get, $set) {
    var api = window.fsuResolveFormApi($get, $set);

    if (api) {
        api.set('og_image_scraped', null);
    }

    window.fsuStopScraping(api);
    window.fsuLockScrape();
};

window.fsuClearOgMetadata = function (api) {
    if (!api || typeof api.set !== 'function') {
        return;
    }

    api.set('og_title', null);
    api.set('og_description', null);
    api.set('og_image_scraped', null);
};

window.fsuRetryScrapeAfterImageRemoved = function ($get, $set) {
    var api = window.fsuResolveFormApi($get, $set);
    var dest = api ? api.get('destination_url') : null;

    if (!dest || window.fsuHasManualOgImage(api ? api.get : $get)) {
        return;
    }

    window.fsuUnlockScrape();
    window.fsuClearOgMetadata(api);

    document.querySelectorAll('[data-fsu-destination-url]').forEach(function (el) {
        window.fsuClearDestinationScrape(el);
        window.fsuScrape(dest, $get, $set, el);
    });
};

window.fsuScrape = function (val, $get, $set, $el) {
    if (!val) {
        return;
    }

    try {
        var url = new URL(val);
        if (url.protocol !== 'http:' && url.protocol !== 'https:') {
            return;
        }
    } catch (_) {
        return;
    }

    var api = window.fsuResolveFormApi($get, $set, $el);

    if (!api) {
        return;
    }

    if (window.fsuIsPasswordProtected(api)) {
        window.fsuStopScraping(api);

        return;
    }

    if (window.fsuShouldSkipScrape($el, val, api.get)) {
        window.fsuStopScraping(api);

        return;
    }

    window.fsuDispatchScraping(true);
    api.set('is_scraping', true);

    fetch('/short-url/scrape-meta?url=' + encodeURIComponent(val))
        .then(function (r) {
            return r.ok ? r.json() : null;
        })
        .then(function (data) {
            if (window.fsuIsPasswordProtected(api)) {
                window.fsuStopScraping(api);

                return;
            }

            if (window.fsuIsScrapeLocked($el, api.get)) {
                window.fsuStopScraping(api);

                return;
            }

            if (data && (data.title || data.description || data.image)) {
                if (data.title && !api.get('og_title')) {
                    api.set('og_title', data.title);
                }
                if (data.description && !api.get('og_description')) {
                    api.set('og_description', data.description);
                }
                if (data.image && !window.fsuHasManualOgImage(api.get)) {
                    api.set('og_image_scraped', data.image);
                    window.fsuLockScrape($el);
                } else {
                    window.fsuStopScraping(api);
                }

                window.fsuMarkDestinationScraped($el, val);
            } else {
                window.fsuStopScraping(api);
            }
        })
        .catch(function () {
            window.fsuStopScraping(api);
        });
};

window.fsuInitScrape = function ($get, $el) {
    var api = window.fsuResolveFormApi($get, null, $el);

    if (!$el || !api) {
        return;
    }

    var dest = api.get('destination_url');

    if (!dest) {
        return;
    }

    if (window.fsuHasManualOgImage(api.get)) {
        window.fsuLockScrape($el);
        window.fsuMarkDestinationScraped($el, dest);

        return;
    }

    if (
        !window.fsuGetScrapedUrl($el)
        && (api.get('og_title') || api.get('og_description') || api.get('og_image_scraped'))
    ) {
        window.fsuMarkDestinationScraped($el, dest);
    }
};
