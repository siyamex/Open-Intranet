// Language picker in the avatar menu: saves the preference, reloads.
(function () {
    'use strict';
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.lang-btn');
        if (!btn) return;
        var body = new URLSearchParams();
        body.set('locale', btn.dataset.lang);
        fetch(btn.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-Token': document.body.dataset.csrf || '' },
            body: body
        }).then(function () { window.location.reload(); });
    });
})();
