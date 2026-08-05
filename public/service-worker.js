/* =============================================================================
   SalesFlow Enterprise — Service Worker
   App-shell precache, runtime caching, offline fallback, Web Push handling.
   ========================================================================== */
const VERSION = 'sf-v1.0.0';
const SHELL = `${VERSION}-shell`;
const RUNTIME = `${VERSION}-runtime`;

const PRECACHE = [
    '/offline.html',
    '/assets/css/design-system.css',
    '/assets/css/app.css',
    '/assets/js/app.js',
    '/assets/js/charts.js',
    '/manifest.json',
    '/assets/icons/icon.svg'
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(SHELL).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Never cache API or auth flows.
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/cti/') ||
        url.pathname.startsWith('/login') || url.pathname.startsWith('/logout')) {
        return;
    }

    // Static assets → cache-first.
    if (url.pathname.startsWith('/assets/')) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((res) => {
                const copy = res.clone();
                caches.open(RUNTIME).then((c) => c.put(request, copy));
                return res;
            }).catch(() => cached))
        );
        return;
    }

    // Navigations → network-first with offline fallback.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(request).then((c) => c || caches.match('/offline.html')))
        );
        return;
    }

    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

/* ---- Web Push -------------------------------------------------------------- */
self.addEventListener('push', (event) => {
    let data = { title: 'SalesFlow', body: 'Je hebt een nieuwe melding.', url: '/notifications' };
    try { if (event.data) data = Object.assign(data, event.data.json()); } catch (_) {}
    event.waitUntil(self.registration.showNotification(data.title, {
        body: data.body,
        icon: '/assets/icons/icon-192.png',
        badge: '/assets/icons/badge.png',
        data: { url: data.url },
        vibrate: [80, 40, 80]
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data?.url || '/dashboard';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
            for (const client of list) {
                if ('focus' in client) { client.navigate(target); return client.focus(); }
            }
            return self.clients.openWindow(target);
        })
    );
});
