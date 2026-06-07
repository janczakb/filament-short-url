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

window.fsuScrape = function (val, $get, $set, $el) {
    if (!val) {
        if ($el) {
            $el.dataset.scraped = 'false';
        }

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

    if ($el && $el.dataset.scraped === 'true') {
        return;
    }

    var api = window.fsuResolveFormApi($get, $set, $el);

    if (!api) {
        return;
    }

    window.fsuDispatchScraping(true);
    api.set('is_scraping', true);

    fetch('/short-url/scrape-meta?url=' + encodeURIComponent(val))
        .then(function (r) {
            return r.ok ? r.json() : null;
        })
        .then(function (data) {
            if (data && (data.title || data.description || data.image)) {
                if (data.title && !api.get('og_title')) {
                    api.set('og_title', data.title);
                }
                if (data.description && !api.get('og_description')) {
                    api.set('og_description', data.description);
                }
                if (data.image && !api.get('og_image_scraped') && !api.get('og_image')) {
                    api.set('og_image_scraped', data.image);
                } else {
                    api.set('is_scraping', false);
                    window.fsuDispatchScraping(false);
                }
                if ($el) {
                    $el.dataset.scraped = 'true';
                }
            } else {
                api.set('is_scraping', false);
                window.fsuDispatchScraping(false);
            }
        })
        .catch(function () {
            api.set('is_scraping', false);
            window.fsuDispatchScraping(false);
        });
};

window.fsuInitScrape = function ($get, $el) {
    var api = window.fsuResolveFormApi($get, null, $el);

    if ($el && api) {
        try {
            $el.dataset.scraped = (api.get('og_title') || api.get('og_description') || api.get('og_image_scraped')) ? 'true' : 'false';
        } catch (_) {
            $el.dataset.scraped = 'false';
        }
    } else if ($el) {
        $el.dataset.scraped = 'false';
    }
};
