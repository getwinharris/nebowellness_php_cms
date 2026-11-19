// Both scopes use the same cache, which contains only versioned application assets.
const ASSET_CACHE = 'nebo-static-v1';
const LEGACY_CACHE = /^(?:nebo-user-v\d+|nebo-cache-admin-v\d+)$/;

self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter(key => LEGACY_CACHE.test(key)).map(key => caches.delete(key)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => new Response(
            'You are offline. Reconnect and reload this page to continue.',
            {status: 503, headers: {'Content-Type': 'text/plain; charset=utf-8', 'Cache-Control': 'no-store'}}
        )));
        return;
    }
    const url = new URL(request.url);
    if (url.origin !== self.location.origin ||
        !/^\/assets\/(?:css\/[\w-]+\.css|[\w-]+\.js)$/.test(url.pathname) ||
        !/^\?v=\d+$/.test(url.search) || request.headers.has('Authorization') ||
        request.cache === 'no-store') return;

    event.respondWith(serveAsset(request));
});

async function serveAsset(request) {
    let cache;
    try {
        cache = await caches.open(ASSET_CACHE);
        const cached = await cache.match(request);
        if (cached) return cached;
    } catch {
        // Storage may be disabled or full. The network still works.
    }
    const response = await fetch(request);
    if (cache && response.status === 200 && response.type === 'basic' && !response.redirected &&
        !/\b(?:private|no-store|no-cache)\b/i.test(response.headers.get('Cache-Control') || '') &&
        /^(?:text\/css|(?:text|application)\/(?:x-)?javascript)\b/i.test(response.headers.get('Content-Type') || '')) {
        try {
            await cache.put(request, response.clone());
        } catch {
            // Quota and eviction errors should never interrupt rendering.
        }
    }
    return response;
}
