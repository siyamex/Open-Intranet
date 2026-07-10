// Employee directory: debounced search-as-you-type, filters, A–Z bar,
// grid/table toggle, pagination — all against /api/directory JSON.
(function () {
    'use strict';
    var filters = document.getElementById('dir-filters');
    if (!filters) return;
    var api = filters.dataset.api;
    var results = document.getElementById('dir-results');
    var statusEl = document.getElementById('dir-status');
    var pagination = document.getElementById('dir-pagination');
    var state = { q: '', department_id: '', location: '', role_id: '', letter: '', page: 1, view: 'grid' };
    var debounceTimer = null;
    var inflight = null;

    function load() {
        if (inflight) inflight.abort();
        inflight = new AbortController();
        statusEl.textContent = 'Searching…';
        results.classList.add('loading');
        var params = new URLSearchParams();
        Object.keys(state).forEach(function (k) {
            if (k !== 'view' && state[k]) params.set(k, state[k]);
        });
        fetch(api + '?' + params.toString(), { signal: inflight.signal, headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(render)
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                statusEl.textContent = '';
                results.classList.remove('loading');
                results.innerHTML = '<div class="card"><p class="form-error" style="margin:0;">Could not load the directory — please try again.</p></div>';
            });
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function avatarHtml(p, size) {
        if (p.avatar_url) {
            return '<img class="avatar" src="' + esc(p.avatar_url) + '" alt="" style="width:' + size + 'px;height:' + size + 'px;" loading="lazy">';
        }
        var initials = String(p.name || '?').split(/\s+/).slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase();
        return '<span class="avatar avatar-initials" style="width:' + size + 'px;height:' + size + 'px;font-size:' + Math.round(size * 0.4) + 'px;">' + esc(initials) + '</span>';
    }

    function actionsHtml(p) {
        var html = '';
        if (p.email) html += '<a class="icon-btn" href="mailto:' + esc(p.email) + '" title="Email">✉</a>';
        if (p.phone) html += '<a class="icon-btn" href="tel:' + esc(p.phone) + '" title="Call">✆</a>';
        if (p.chat_url) html += '<a class="icon-btn" href="' + esc(p.chat_url) + '" target="_blank" rel="noopener" title="Chat">💬</a>';
        html += '<a class="icon-btn" href="' + esc(p.vcard_url) + '" title="Download vCard">📇</a>';
        return html;
    }

    function render(data) {
        results.classList.remove('loading');
        statusEl.textContent = data.total + ' people';
        if (!data.items.length) {
            results.innerHTML = '<div class="card"><p class="text-muted" style="margin:0;">Nobody matches your search.</p></div>';
            pagination.innerHTML = '';
            return;
        }
        if (state.view === 'grid') {
            results.className = 'dir-grid';
            results.innerHTML = data.items.map(function (p) {
                return '<div class="card dir-card">'
                    + '<a href="' + esc(p.profile_url) + '">' + avatarHtml(p, 64) + '</a>'
                    + '<a class="dir-name" href="' + esc(p.profile_url) + '">' + esc(p.name) + '</a>'
                    + '<span class="text-muted dir-title">' + esc(p.title || '') + '</span>'
                    + '<span class="text-muted dir-meta">' + esc(p.department || '') + (p.location ? ' · ' + esc(p.location) : '') + (p.local_time ? ' · 🕒 ' + esc(p.local_time) : '') + '</span>'
                    + (p.skills && p.skills.length ? '<span class="dir-skills">' + p.skills.slice(0, 4).map(function (s) { return '<span class="skill-chip">' + esc(s) + '</span>'; }).join('') + '</span>' : '')
                    + '<span class="dir-actions">' + actionsHtml(p) + '</span>'
                    + '</div>';
            }).join('');
        } else {
            results.className = 'table-wrap';
            results.innerHTML = '<table class="table"><thead><tr><th></th><th>Name</th><th>Title</th><th>Department</th><th>Contact</th><th></th></tr></thead><tbody>'
                + data.items.map(function (p) {
                    return '<tr>'
                        + '<td>' + avatarHtml(p, 32) + '</td>'
                        + '<td><a href="' + esc(p.profile_url) + '"><strong>' + esc(p.name) + '</strong></a>' + (p.location ? '<br><span class="text-muted">' + esc(p.location) + '</span>' : '') + '</td>'
                        + '<td>' + esc(p.title || '') + '</td>'
                        + '<td>' + esc(p.department || '') + '</td>'
                        + '<td>' + (p.email ? '<a href="mailto:' + esc(p.email) + '">' + esc(p.email) + '</a>' : '') + (p.phone ? '<br>' + esc(p.phone) : '') + '</td>'
                        + '<td>' + actionsHtml(p) + '</td>'
                        + '</tr>';
                }).join('')
                + '</tbody></table>';
        }
        pagination.innerHTML = '';
        if (data.pages > 1) {
            for (var p = 1; p <= data.pages; p++) {
                (function (page) {
                    var a = document.createElement('button');
                    a.type = 'button';
                    a.className = 'page-link' + (page === data.page ? ' active' : '');
                    a.textContent = page;
                    a.addEventListener('click', function () {
                        state.page = page;
                        load();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                    pagination.appendChild(a);
                })(p);
            }
        }
    }

    document.getElementById('dir-q').addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var value = this.value.trim();
        debounceTimer = setTimeout(function () {
            state.q = value;
            state.page = 1;
            load();
        }, 250);
    });
    [['dir-department', 'department_id'], ['dir-location', 'location'], ['dir-role', 'role_id']].forEach(function (pair) {
        document.getElementById(pair[0]).addEventListener('change', function () {
            state[pair[1]] = this.value;
            state.page = 1;
            load();
        });
    });
    document.querySelectorAll('.az-letter').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.az-letter').forEach(function (b) { b.classList.toggle('active', b === btn); });
            state.letter = btn.dataset.letter;
            state.page = 1;
            load();
        });
    });
    document.getElementById('dir-view-toggle').addEventListener('click', function () {
        state.view = state.view === 'grid' ? 'table' : 'grid';
        this.textContent = state.view === 'grid' ? 'Table view' : 'Grid view';
        load();
    });

    load();
})();
