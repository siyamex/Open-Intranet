// Dashboard app launcher: click tracking, favorites, personal drag order.
(function () {
    'use strict';
    var grid = document.getElementById('ql-grid');
    if (!grid) return;
    var csrf = grid.dataset.csrf;

    // Non-blocking click counter
    grid.addEventListener('click', function (e) {
        var link = e.target.closest('.ql-link');
        if (!link) return;
        var tile = link.closest('.ql-tile');
        var url = grid.dataset.clickUrl + '/' + tile.dataset.id + '/click';
        if (navigator.sendBeacon) {
            var fd = new FormData();
            fd.append('_token', csrf);
            navigator.sendBeacon(url, fd);
        } else {
            fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': csrf }, keepalive: true });
        }
    });

    // Favorites
    grid.addEventListener('click', function (e) {
        var pin = e.target.closest('.ql-pin');
        if (!pin) return;
        e.preventDefault();
        var tile = pin.closest('.ql-tile');
        fetch(grid.dataset.clickUrl + '/' + tile.dataset.id + '/pin', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf, 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            pin.classList.toggle('pinned', data.pinned);
            if (data.pinned) grid.prepend(tile);
        });
    });

    // Personal drag order
    var dragging = null;
    grid.addEventListener('dragstart', function (e) {
        dragging = e.target.closest('.ql-tile');
        if (dragging) dragging.classList.add('dragging');
    });
    grid.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragging) return;
        var over = e.target.closest('.ql-tile');
        if (!over || over === dragging) return;
        var rect = over.getBoundingClientRect();
        var before = (e.clientX - rect.left) < rect.width / 2;
        grid.insertBefore(dragging, before ? over : over.nextSibling);
    });
    grid.addEventListener('drop', function (e) {
        e.preventDefault();
        saveOrder();
    });
    grid.addEventListener('dragend', function () {
        if (dragging) dragging.classList.remove('dragging');
        dragging = null;
    });

    function saveOrder() {
        var order = Array.prototype.map.call(grid.querySelectorAll('.ql-tile'), function (t) {
            return parseInt(t.dataset.id, 10);
        });
        fetch(grid.dataset.orderUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify({ order: order })
        });
    }
})();
