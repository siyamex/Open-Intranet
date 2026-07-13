// OpenIntranet service worker. Cache-busted per theme change via __CACHE_VERSION__
// (the server replaces this token — see ManifestController::serviceWorker()).
var CACHE = 'openintranet-shell-__CACHE_VERSION__';
var BASE = '__APP_BASE__'; // e.g. '/intra' — empty string when served from the domain root
var APP_SHELL = [
    BASE + '/',
    BASE + '/assets/css/app.css',
    BASE + '/assets/js/app.js',
    BASE + '/assets/js/components.js',
    BASE + '/offline.html'
];
var OFFLINE_URL = BASE + '/offline.html';

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE).then(function (cache) {
            return Promise.all(APP_SHELL.map(function (url) {
                return cache.add(url).catch(function () { /* ignore individual misses */ });
            }));
        }).then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.filter(function (k) { return k.indexOf('openintranet-shell-') === 0 && k !== CACHE; })
                .map(function (k) { return caches.delete(k); }));
        }).then(function () { return self.clients.claim(); })
    );
});

// Network-first for pages, cache-first for static assets, offline fallback for navigations.
self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') return;
    var url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    var isAsset = /\/assets\//.test(url.pathname);
    if (isAsset) {
        event.respondWith(
            caches.match(req).then(function (cached) {
                return cached || fetch(req).then(function (res) {
                    var copy = res.clone();
                    caches.open(CACHE).then(function (cache) { cache.put(req, copy); });
                    return res;
                });
            })
        );
        return;
    }

    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).then(function (res) {
                var copy = res.clone();
                caches.open(CACHE).then(function (cache) { cache.put(req, copy); });
                return res;
            }).catch(function () {
                return caches.match(req).then(function (cached) {
                    return cached || caches.match(OFFLINE_URL);
                });
            })
        );
        return;
    }

    event.respondWith(
        fetch(req).catch(function () { return caches.match(req); })
    );
});

// ---- Web push ----
self.addEventListener('push', function (event) {
    var data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { title: 'Notification', body: event.data ? event.data.text() : '' }; }
    event.waitUntil(
        self.registration.showNotification(data.title || 'OpenIntranet', {
            body: data.body || '',
            icon: data.icon || (BASE + '/assets/icons/bell.svg'),
            data: { url: data.url || (BASE + '/') }
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(function (list) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].url.indexOf(url) !== -1 && 'focus' in list[i]) return list[i].focus();
            }
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});
