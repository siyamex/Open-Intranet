// Wiki editor: debounced server-side markdown preview.
(function () {
    'use strict';
    var editor = document.querySelector('.wiki-editor');
    if (!editor) return;
    var textarea = document.getElementById('wiki-md');
    var preview = document.getElementById('wiki-preview');
    var timer = null;

    function refresh() {
        var body = new URLSearchParams();
        body.set('body_md', textarea.value);
        fetch(editor.dataset.previewUrl, {
            method: 'POST',
            headers: { 'X-CSRF-Token': editor.dataset.csrf, 'Accept': 'application/json' },
            body: body
        }).then(function (r) { return r.json(); }).then(function (data) {
            preview.innerHTML = data.html;
        });
    }
    textarea.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(refresh, 350);
    });
    refresh();
})();
