/**
 * ChurchSys 教會系統易 — Service Worker
 *
 * Strategy (kept deliberately conservative — this system is authenticated
 * and CSRF-protected, so HTML is NEVER served from cache):
 *
 *   - Navigations (page loads): network-only, offline -> /offline.html
 *   - Static assets (css/js/images/icons): stale-while-revalidate
 *   - Everything else (AJAX POSTs, storage files): untouched
 */

const VERSION = 'v1';
const PRECACHE = 'churchsys-precache-' + VERSION;
const RUNTIME = 'churchsys-static-' + VERSION;

const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.json',
    '/css/responsive.css',
    '/js/app-responsive.js',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PRECACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key !== PRECACHE && key !== RUNTIME)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only handle same-origin GETs
    if (request.method !== 'GET') {
        return;
    }
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Page navigations: always the network; show the offline page if down.
    // Authenticated HTML is never cached (avoids stale CSRF tokens/pages).
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );
        return;
    }

    // Static assets: stale-while-revalidate
    if (/^\/(css|js|images|icons)\//.test(url.pathname) ||
        url.pathname === '/favicon.ico') {
        event.respondWith(
            caches.open(RUNTIME).then(async (cache) => {
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        if (response && response.ok) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(() => cached);
                return cached || network;
            })
        );
    }
    // Everything else (e.g. /storage member photos, /members/autocomplete):
    // default browser behaviour, no interception.
});
