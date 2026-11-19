const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require.resolve('../assets/pwa/sw-user.js'), 'utf8');
const origin = 'https://example.test';

function worker() {
    const events = new Map();
    const stores = new Map();
    const deleted = [];
    let response = new Response('/* asset */', {headers: {'Content-Type': 'text/css'}});
    Object.defineProperty(response, 'type', {value: 'basic'});
    vm.runInNewContext(source, {
        URL, Response,
        fetch: async () => {
            if (response instanceof Error) throw response;
            return response;
        },
        self: {
            location: {origin},
            addEventListener: (name, handler) => events.set(name, handler),
            clients: {claim: async () => {}}
        },
        caches: {
            open: async key => {
                if (!stores.has(key)) stores.set(key, new Map());
                const store = stores.get(key);
                return {match: req => store.get(req.url), put: (req, res) => store.set(req.url, res)};
            },
            keys: async () => ['nebo-user-v3', 'nebo-cache-admin-v2', 'unrelated', 'nebo-static-v1'],
            delete: async key => deleted.push(key)
        }
    });
    return {
        stores, deleted, setResponse(next) { response = next; },
        activate: () => new Promise(resolve => events.get('activate')({waitUntil: resolve})),
        load(path, options = {}) {
            let result;
            const request = {url: new URL(path, origin).href, method: 'GET', headers: new Headers(), ...options};
            events.get('fetch')({request, respondWith: promise => { result = promise; }});
            return result;
        }
    };
}

test('both legacy caches are retired while unrelated caches survive', async () => {
    const browser = worker();
    await browser.activate();
    assert.deepEqual(browser.deleted, ['nebo-user-v3', 'nebo-cache-admin-v2']);
});

test('dynamic requests bypass storage and offline navigation returns a fresh 503', async () => {
    const browser = worker();
    for (const path of ['/account', '/admin/agent', '/support/history', '/storage/private.png', '/api/data.js', '/assets/styles.js?token=private', 'https://other.test/assets/site.js?v=1']) {
        assert.equal(browser.load(path), undefined);
    }
    browser.setResponse(new Error('offline'));
    const page = await browser.load('/admin/agent', {mode: 'navigate'});
    assert.equal(page.status, 503);
    assert.equal(page.headers.get('Cache-Control'), 'no-store');
    assert.equal(browser.stores.size, 0);
});

test('only successful public versioned styles and scripts can be reused offline', async () => {
    const browser = worker();
    const path = '/assets/css/band.css?v=123';
    await browser.load(path);
    browser.setResponse(new Error('offline'));
    assert.equal(await (await browser.load(path)).text(), '/* asset */');
    await assert.rejects(browser.load('/assets/css/band.css?v=124'), /offline/);

    for (const [type, policy] of [['text/html', 'public'], ['text/css', 'no-store'], ['text/css', 'private']]) {
        const response = new Response('sensitive', {headers: {'Content-Type': type, 'Cache-Control': policy}});
        Object.defineProperty(response, 'type', {value: 'basic'});
        browser.setResponse(response);
        await browser.load('/assets/css/private.css?v=1');
        browser.setResponse(new Error('offline'));
        await assert.rejects(browser.load('/assets/css/private.css?v=1'), /offline/);
    }
});
