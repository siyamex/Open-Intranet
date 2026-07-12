// Kudos wall: emoji reactions.
(function () {
    'use strict';
    document.querySelectorAll('.kudos-reactions').forEach(function (box) {
        box.addEventListener('click', function (e) {
            var btn = e.target.closest('.reaction');
            if (!btn) return;
            var body = new URLSearchParams();
            body.set('emoji', btn.dataset.emoji);
            fetch(box.dataset.url + '/' + btn.dataset.id + '/react', {
                method: 'POST',
                headers: { 'X-CSRF-Token': box.dataset.csrf, 'Accept': 'application/json' },
                body: body
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (!data.ok) return;
                btn.querySelector('.reaction-count').textContent = data.count;
                btn.classList.toggle('mine', data.mine);
            });
        });
    });
})();
