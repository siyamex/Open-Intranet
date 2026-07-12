// Ctrl/Cmd+K global search overlay with keyboard navigation.
(function () {
    'use strict';
    var overlay = document.getElementById('search-overlay');
    if (!overlay) return;
    var input = document.getElementById('overlay-q');
    var results = document.getElementById('overlay-results');
    var debounce = null;
    var active = -1;

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            overlay.showModal();
            input.value = '';
            results.innerHTML = '';
            input.focus();
        }
    });
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) overlay.close();
    });

    input.addEventListener('input', function () {
        clearTimeout(debounce);
        var q = input.value.trim();
        if (q.length < 2) {
            results.innerHTML = '';
            return;
        }
        debounce = setTimeout(function () {
            fetch(overlay.dataset.url + '?format=json&q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(render)
                .catch(function () { results.innerHTML = '<p class="text-muted" style="padding:0.5rem;">Search failed.</p>'; });
        }, 220);
    });

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function render(data) {
        active = -1;
        if (!data.groups.length) {
            results.innerHTML = '<p class="text-muted" style="padding:0.5rem;">No results.</p>';
            return;
        }
        var html = '';
        data.groups.forEach(function (group) {
            html += '<div class="overlay-group">' + esc(group.label) + '</div>';
            group.items.forEach(function (item) {
                html += '<a class="overlay-item" href="' + esc(item.url) + '">'
                    + '<strong>' + esc(item.title) + '</strong>'
                    + '<span class="text-muted"> · ' + esc(item.meta) + '</span>'
                    + '</a>';
            });
        });
        results.innerHTML = html;
    }

    input.addEventListener('keydown', function (e) {
        var items = results.querySelectorAll('.overlay-item');
        if (!items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            active += e.key === 'ArrowDown' ? 1 : -1;
            active = Math.max(0, Math.min(items.length - 1, active));
            items.forEach(function (el, i) { el.classList.toggle('active', i === active); });
            items[active].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && active >= 0) {
            e.preventDefault();
            window.location.href = items[active].href;
        }
    });
})();
