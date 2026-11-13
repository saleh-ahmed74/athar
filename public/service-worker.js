const PAGE_CACHE = 'athar-offline-pages-v2';
const ASSET_CACHE = 'athar-offline-assets-v2';
const OFFLINE_PAGE = '/offline.html';
const MAX_PAGES = 30;
const MAX_ASSETS = 100;

self.addEventListener('install', event => {
    event.waitUntil(
        Promise.all([
            caches.open(PAGE_CACHE).then(cache => cache.add(OFFLINE_PAGE)),
            caches.open(ASSET_CACHE).then(cache => cache.add('/offline-reconnect.js')),
        ])
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys
                .filter(key => key.startsWith('athar-offline-') && ![PAGE_CACHE, ASSET_CACHE].includes(key))
                .map(key => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

const trimCache = async (cache, maxEntries) => {
    const requests = await cache.keys();
    await Promise.all(requests.slice(0, Math.max(0, requests.length - maxEntries)).map(request => cache.delete(request)));
};

const isPublicPage = pathname => /^\/(?:|shop\/?|about\/?|policy\/?|cart\/?|checkout\/?|category\/[^/]+\/?|product\/[^/]+\/?)$/.test(pathname);

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    const acceptsHtml = request.headers.get('accept')?.includes('text/html');
    if (acceptsHtml && isPublicPage(url.pathname)) {
        event.respondWith((async () => {
            const cache = await caches.open(PAGE_CACHE);
            try {
                const response = await fetch(request);
                if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
                    const html = await response.clone().text();
                    if (html.includes('<meta name="offline-cacheable" content="public">')) {
                        try {
                            await cache.put(request, response.clone());
                            await trimCache(cache, MAX_PAGES + 1);
                        } catch {}
                    }
                }
                return response;
            } catch {
                return await cache.match(request) || await cache.match(request.url, { ignoreSearch: true }) || await caches.match(OFFLINE_PAGE);
            }
        })());

        return;
    }

    const isStaticAsset = ['style', 'script', 'image', 'font'].includes(request.destination)
        || url.pathname.startsWith('/build/');
    if (!isStaticAsset) {
        return;
    }

    event.respondWith((async () => {
        const cache = await caches.open(ASSET_CACHE);
        const cached = await cache.match(request);
        if (cached) {
            const cacheControl = cached.headers.get('cache-control') || '';
            const isLongLived = /(?:immutable|max-age=(?:[1-9][0-9]{6,}))/i.test(cacheControl);

            if (url.pathname.startsWith('/build/') || isLongLived) {
                return cached;
            }

            event.waitUntil(fetch(request).then(response => {
                if (response.ok) {
                    return cache.put(request, response.clone()).then(() => trimCache(cache, MAX_ASSETS));
                }
            }).catch(() => {}));

            return cached;
        }

        try {
            const response = await fetch(request);
            if (response.ok) {
                try {
                    await cache.put(request, response.clone());
                    await trimCache(cache, MAX_ASSETS);
                } catch {}
            }

            return response;
        } catch {
            return new Response('', { status: 503, statusText: 'Offline' });
        }
    })());
});
