// PWA bootstrap: register the service worker + manage the push subscribe toggle.
(function () {
    'use strict';
    if (!('serviceWorker' in navigator)) return;

    var root = document.documentElement;
    var swUrl = root.dataset.swUrl;
    var apiBase = root.dataset.appBase || '';

    navigator.serviceWorker.register(swUrl).catch(function () { /* offline-first is best-effort */ });

    var toggle = document.getElementById('push-toggle');
    if (!toggle) return;

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(base64);
        var arr = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    function refreshState() {
        navigator.serviceWorker.ready.then(function (reg) {
            return reg.pushManager.getSubscription();
        }).then(function (sub) {
            toggle.checked = !!sub;
        });
    }
    refreshState();

    toggle.addEventListener('change', function () {
        navigator.serviceWorker.ready.then(function (reg) {
            if (toggle.checked) {
                fetch(apiBase + '/push/public-key').then(function (r) { return r.json(); }).then(function (data) {
                    if (!data.key) {
                        toggle.checked = false;
                        alert('Push is not configured on this server yet.');
                        return;
                    }
                    reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(data.key)
                    }).then(function (sub) {
                        return fetch(apiBase + '/push/subscribe', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(sub)
                        });
                    }).catch(function () { toggle.checked = false; });
                });
            } else {
                reg.pushManager.getSubscription().then(function (sub) {
                    if (!sub) return;
                    var endpoint = sub.endpoint;
                    sub.unsubscribe().then(function () {
                        fetch(apiBase + '/push/unsubscribe', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ endpoint: endpoint })
                        });
                    });
                });
            }
        });
    });
})();
