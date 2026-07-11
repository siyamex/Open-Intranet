// OpenIntranet app shell: sidebar, dropdowns, notifications, dark mode, toasts.
(function () {
    'use strict';

    // ---- CSP-safe confirmations (replaces inline onsubmit/onclick) --------
    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-confirm]');
        if (form && !window.confirm(form.dataset.confirm.split('\\n').join('\n'))) {
            event.preventDefault();
        }
    });
    document.addEventListener('click', function (event) {
        var btn = event.target.closest('button[data-confirm], a[data-confirm]');
        if (btn && !btn.closest('form[data-confirm]')) {
            if (!window.confirm(btn.dataset.confirm.split('\\n').join('\n'))) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }
        var selectable = event.target.closest('[data-select-onclick]');
        if (selectable && selectable.select) selectable.select();
    });

    // ---- Toasts -----------------------------------------------------------
    document.addEventListener('click', function (event) {
        var close = event.target.closest('.toast-close');
        if (close) {
            var toast = close.closest('.toast');
            if (toast) toast.remove();
        }
    });
    document.querySelectorAll('.toast').forEach(function (toast) {
        setTimeout(function () {
            toast.style.transition = 'opacity 0.4s';
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 400);
        }, 6000);
    });

    // ---- Sidebar toggle (collapse on desktop, off-canvas on mobile) -------
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebar-backdrop');
    var toggle = document.getElementById('sidebar-toggle');
    var mobile = function () { return window.innerWidth < 992; };

    if (sidebar && localStorage.getItem('sidebar-collapsed') === '1' && !mobile()) {
        document.body.classList.add('sidebar-collapsed');
    }
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            if (mobile()) {
                var open = sidebar.classList.toggle('open');
                if (backdrop) backdrop.hidden = !open;
            } else {
                var collapsed = document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
            }
        });
    }
    if (backdrop) {
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('open');
            backdrop.hidden = true;
        });
    }

    // ---- Sidebar submenus ---------------------------------------------------
    document.querySelectorAll('.submenu-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var submenu = btn.parentElement.querySelector('.sidebar-submenu');
            if (!submenu) return;
            var isOpen = btn.classList.toggle('open');
            submenu.hidden = !isOpen;
        });
    });

    // ---- Generic dropdowns ---------------------------------------------------
    document.querySelectorAll('.dropdown').forEach(function (dropdown) {
        var trigger = dropdown.querySelector('button');
        var menu = dropdown.querySelector('.dropdown-menu');
        if (!trigger || !menu) return;
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasHidden = menu.hidden;
            document.querySelectorAll('.dropdown-menu').forEach(function (m) { m.hidden = true; });
            menu.hidden = !wasHidden;
            if (!menu.hidden && dropdown.id === 'notif-dropdown') loadNotifications();
        });
    });
    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown-menu').forEach(function (m) { m.hidden = true; });
    });
    document.querySelectorAll('.dropdown-menu').forEach(function (m) {
        m.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    // ---- Notifications -------------------------------------------------------
    var notifToggle = document.getElementById('notif-toggle');
    function loadNotifications() {
        if (!notifToggle) return;
        var list = document.getElementById('notif-list');
        fetch(notifToggle.dataset.url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                updateBadge(data.unread);
                if (!data.items.length) {
                    list.innerHTML = '<p class="text-muted dropdown-empty">No notifications yet.</p>';
                    return;
                }
                list.innerHTML = '';
                data.items.forEach(function (n) {
                    var a = document.createElement('a');
                    a.className = 'notif-item' + (n.read_at ? '' : ' unread');
                    a.href = n.url || '#';
                    a.innerHTML = '<strong></strong><span class="text-muted notif-time"></span>';
                    a.querySelector('strong').textContent = n.title;
                    a.querySelector('.notif-time').textContent = n.time_ago;
                    a.addEventListener('click', function () { markRead(n.id); });
                    list.appendChild(a);
                });
            })
            .catch(function () {
                list.innerHTML = '<p class="text-muted dropdown-empty">Could not load notifications.</p>';
            });
    }
    function markRead(id) {
        if (!notifToggle) return;
        var body = new URLSearchParams();
        if (id) body.set('id', id);
        fetch(notifToggle.dataset.readUrl, {
            method: 'POST',
            headers: { 'X-CSRF-Token': notifToggle.dataset.csrf, 'Accept': 'application/json' },
            body: body
        }).then(function (r) { return r.json(); }).then(function (data) {
            updateBadge(data.unread);
        });
    }
    function updateBadge(count) {
        var badge = document.getElementById('notif-badge');
        if (!badge) return;
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.toggle('hidden', count === 0);
    }
    var markAll = document.getElementById('notif-mark-all');
    if (markAll) {
        markAll.addEventListener('click', function () {
            markRead(null);
            loadNotifications();
        });
    }

    // ---- Animated poll result bars -----------------------------------------
    document.querySelectorAll('.poll-bar-fill').forEach(function (bar) {
        requestAnimationFrame(function () {
            setTimeout(function () { bar.style.width = bar.dataset.width; }, 60);
        });
    });

    // ---- Dark mode toggle: cycles auto -> light -> dark -------------------------
    var darkToggle = document.getElementById('dark-toggle');
    if (darkToggle) {
        var applyMode = function (mode) {
            var root = document.documentElement;
            var dark = mode === 'dark' || (mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            root.setAttribute('data-theme', dark ? 'dark' : 'light');
            root.setAttribute('data-theme-mode', mode);
            darkToggle.title = 'Theme: ' + mode + ' (click to change)';
        };
        applyMode(document.documentElement.getAttribute('data-theme-mode') || 'auto');
        darkToggle.addEventListener('click', function () {
            var order = ['auto', 'light', 'dark'];
            var current = document.documentElement.getAttribute('data-theme-mode') || 'auto';
            var next = order[(order.indexOf(current) + 1) % order.length];
            applyMode(next);
            localStorage.setItem('theme-mode', next);
            var prefUrl = document.body.dataset.themePrefUrl;
            var csrf = document.body.dataset.csrf || (notifToggle ? notifToggle.dataset.csrf : '');
            if (prefUrl) {
                var body = new URLSearchParams();
                body.set('mode', next);
                fetch(prefUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrf },
                    body: body
                });
            }
        });
    }
})();
