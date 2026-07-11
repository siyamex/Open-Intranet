// Month/week/list calendar, no libraries.
(function () {
    'use strict';
    var root = document.getElementById('calendar');
    if (!root) return;
    var api = root.dataset.api;
    var body = document.getElementById('cal-body');
    var titleEl = document.getElementById('cal-title');
    var cursor = new Date();
    var view = 'month';
    var events = [];

    document.getElementById('cal-feed-url').textContent = root.dataset.feed;

    function ymd(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }
    function range() {
        var from, to;
        if (view === 'week') {
            var start = new Date(cursor);
            start.setDate(start.getDate() - ((start.getDay() + 6) % 7)); // Monday
            var end = new Date(start);
            end.setDate(end.getDate() + 6);
            from = start; to = end;
        } else if (view === 'list') {
            from = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
            to = new Date(cursor.getFullYear(), cursor.getMonth() + 2, 0);
        } else {
            from = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
            to = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0);
        }
        return { from: from, to: to };
    }

    function load() {
        var r = range();
        fetch(api + '?from=' + ymd(r.from) + '&to=' + ymd(r.to), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (data) { events = data.items; render(); })
            .catch(function () { body.innerHTML = '<p class="form-error">Could not load events.</p>'; });
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }
    function eventsOn(dateStr) {
        return events.filter(function (e) {
            return e.starts_at.slice(0, 10) <= dateStr && e.ends_at.slice(0, 10) >= dateStr;
        });
    }
    function chip(e) {
        return '<a class="cal-chip" style="background:' + esc(e.color) + ';" href="' + esc(e.url) + '" title="' + esc(e.title) + '">' + esc(e.title) + '</a>';
    }

    function render() {
        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        var r = range();
        if (view === 'month') {
            titleEl.textContent = months[cursor.getMonth()] + ' ' + cursor.getFullYear();
            var first = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
            var offset = (first.getDay() + 6) % 7;
            var days = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0).getDate();
            var todayStr = ymd(new Date());
            var html = '<div class="cal-grid">';
            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(function (d) {
                html += '<div class="cal-dow">' + d + '</div>';
            });
            for (var b = 0; b < offset; b++) html += '<div class="cal-cell empty"></div>';
            for (var d = 1; d <= days; d++) {
                var dateStr = ymd(new Date(cursor.getFullYear(), cursor.getMonth(), d));
                html += '<div class="cal-cell' + (dateStr === todayStr ? ' today' : '') + '"><span class="cal-daynum">' + d + '</span>'
                    + eventsOn(dateStr).map(chip).join('') + '</div>';
            }
            html += '</div>';
            body.innerHTML = html;
        } else if (view === 'week') {
            titleEl.textContent = 'Week of ' + r.from.toLocaleDateString();
            var html2 = '<div class="cal-week">';
            for (var i = 0; i < 7; i++) {
                var day = new Date(r.from);
                day.setDate(day.getDate() + i);
                var ds = ymd(day);
                html2 += '<div class="cal-week-day' + (ds === ymd(new Date()) ? ' today' : '') + '">'
                    + '<div class="cal-dow">' + day.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric' }) + '</div>'
                    + eventsOn(ds).map(chip).join('')
                    + '</div>';
            }
            body.innerHTML = html2 + '</div>';
        } else {
            titleEl.textContent = months[cursor.getMonth()] + ' – upcoming';
            if (!events.length) {
                body.innerHTML = '<p class="text-muted">No events in this period.</p>';
                return;
            }
            body.innerHTML = '<ul class="cal-list">' + events.map(function (e) {
                var d = new Date(e.starts_at.replace(' ', 'T'));
                return '<li><span class="cal-list-date">' + d.toLocaleDateString(undefined, { day: 'numeric', month: 'short' })
                    + (e.all_day ? '' : ' ' + d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })) + '</span> '
                    + chip(e) + (e.location ? ' <span class="text-muted">@ ' + esc(e.location) + '</span>' : '') + '</li>';
            }).join('') + '</ul>';
        }
    }

    document.getElementById('cal-prev').addEventListener('click', function () {
        view === 'week' ? cursor.setDate(cursor.getDate() - 7) : cursor.setMonth(cursor.getMonth() - 1);
        load();
    });
    document.getElementById('cal-next').addEventListener('click', function () {
        view === 'week' ? cursor.setDate(cursor.getDate() + 7) : cursor.setMonth(cursor.getMonth() + 1);
        load();
    });
    document.getElementById('cal-today').addEventListener('click', function () {
        cursor = new Date();
        load();
    });
    document.querySelectorAll('[data-cal-view]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            view = btn.dataset.calView;
            document.querySelectorAll('[data-cal-view]').forEach(function (b) { b.classList.toggle('active', b === btn); });
            load();
        });
    });

    load();
})();
