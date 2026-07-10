// Interactive org chart: SVG tree with pan/zoom, collapse, search,
// department dimming, side panel and PNG export. No libraries.
(function () {
    'use strict';
    var dataEl = document.getElementById('oc-data');
    var svg = document.getElementById('oc-svg');
    if (!dataEl || !svg) return;
    var tree = JSON.parse(dataEl.textContent).tree;
    var NS = 'http://www.w3.org/2000/svg';
    var CARD_W = 190, CARD_H = 70, GAP_X = 18, GAP_Y = 46;
    var deptColors = {};
    var palette = ['#4f46e5', '#0ea5e9', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0f766e', '#db2777'];
    var viewBox = { x: 0, y: 0, w: 1200, h: 700 };
    var filterDept = '';
    var highlightId = null;

    // ---- view toggle ----
    document.querySelectorAll('[data-oc-view]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-oc-view]').forEach(function (b) { b.classList.toggle('active', b === btn); });
            ['hierarchy', 'departments', 'flat'].forEach(function (view) {
                document.getElementById('oc-' + view).hidden = view !== btn.dataset.ocView;
            });
        });
    });

    function deptColor(id) {
        if (id == null) return '#94a3b8';
        if (!deptColors[id]) deptColors[id] = palette[Object.keys(deptColors).length % palette.length];
        return deptColors[id];
    }

    // Collapse everything beyond depth 3 initially ("lazy" deep branches)
    (function prime(nodes, depth) {
        nodes.forEach(function (n) {
            n._collapsed = depth >= 3 && n.children.length > 0;
            prime(n.children, depth + 1);
        });
    })(tree, 1);

    // ---- layout (tidy-ish): leaves get sequential x, parents center over children ----
    var cursor = 0;
    function layout(nodes, depth) {
        nodes.forEach(function (n) {
            n._y = (depth - 1) * (CARD_H + GAP_Y);
            var visibleKids = n._collapsed ? [] : n.children;
            if (!visibleKids.length) {
                n._x = cursor * (CARD_W + GAP_X);
                cursor++;
            } else {
                layout(visibleKids, depth + 1);
                n._x = (visibleKids[0]._x + visibleKids[visibleKids.length - 1]._x) / 2;
            }
        });
    }

    function el(tag, attrs) {
        var node = document.createElementNS(NS, tag);
        Object.keys(attrs || {}).forEach(function (k) { node.setAttribute(k, attrs[k]); });
        return node;
    }

    function render() {
        cursor = 0;
        layout(tree, 1);
        while (svg.firstChild) svg.removeChild(svg.firstChild);
        var root = el('g', { id: 'oc-root' });
        svg.appendChild(root);
        var bounds = { minX: Infinity, maxX: -Infinity, maxY: 0 };

        (function draw(nodes, parent) {
            nodes.forEach(function (n) {
                bounds.minX = Math.min(bounds.minX, n._x);
                bounds.maxX = Math.max(bounds.maxX, n._x + CARD_W);
                bounds.maxY = Math.max(bounds.maxY, n._y + CARD_H);

                if (parent) {
                    var midParentX = parent._x + CARD_W / 2, midX = n._x + CARD_W / 2;
                    var startY = parent._y + CARD_H, midY = (startY + n._y) / 2;
                    root.appendChild(el('path', {
                        d: 'M' + midParentX + ',' + startY + ' V' + midY + ' H' + midX + ' V' + n._y,
                        fill: 'none', stroke: 'var(--color-border)', 'stroke-width': 2
                    }));
                }

                var dimmed = filterDept && String(n.dept_id) !== filterDept;
                var g = el('g', { class: 'oc-node' + (dimmed ? ' dimmed' : '') + (n.id === highlightId ? ' highlight' : ''), 'data-id': n.id });
                g.appendChild(el('rect', {
                    x: n._x, y: n._y, width: CARD_W, height: CARD_H, rx: 10,
                    fill: 'var(--color-surface)', stroke: n.id === highlightId ? 'var(--color-primary)' : 'var(--color-border)',
                    'stroke-width': n.id === highlightId ? 3 : 1.5
                }));
                g.appendChild(el('rect', { x: n._x, y: n._y, width: 5, height: CARD_H, rx: 2, fill: deptColor(n.dept_id) }));

                if (n.avatar) {
                    var clipId = 'clip-' + n.id;
                    var clip = el('clipPath', { id: clipId });
                    clip.appendChild(el('circle', { cx: n._x + 32, cy: n._y + CARD_H / 2, r: 20 }));
                    g.appendChild(clip);
                    g.appendChild(el('image', {
                        href: n.avatar, x: n._x + 12, y: n._y + CARD_H / 2 - 20,
                        width: 40, height: 40, 'clip-path': 'url(#' + clipId + ')', preserveAspectRatio: 'xMidYMid slice'
                    }));
                } else {
                    g.appendChild(el('circle', { cx: n._x + 32, cy: n._y + CARD_H / 2, r: 20, fill: deptColor(n.dept_id) }));
                    var initials = el('text', {
                        x: n._x + 32, y: n._y + CARD_H / 2 + 5, 'text-anchor': 'middle',
                        'font-size': 14, 'font-weight': 700, fill: '#fff'
                    });
                    initials.textContent = String(n.name).split(/\s+/).slice(0, 2).map(function (w) { return w[0] || ''; }).join('').toUpperCase();
                    g.appendChild(initials);
                }

                var name = el('text', { x: n._x + 60, y: n._y + 28, 'font-size': 13, 'font-weight': 700, fill: 'var(--color-text)' });
                name.textContent = n.name.length > 17 ? n.name.slice(0, 16) + '…' : n.name;
                g.appendChild(name);
                var title = el('text', { x: n._x + 60, y: n._y + 46, 'font-size': 11, fill: 'var(--color-text-muted)' });
                title.textContent = (n.title || '').length > 21 ? n.title.slice(0, 20) + '…' : (n.title || '');
                g.appendChild(title);

                if (n.children.length) {
                    var badge = el('g', { class: 'oc-toggle', 'data-toggle': n.id });
                    badge.appendChild(el('circle', { cx: n._x + CARD_W / 2, cy: n._y + CARD_H, r: 11, fill: 'var(--color-primary)' }));
                    var count = el('text', { x: n._x + CARD_W / 2, y: n._y + CARD_H + 4, 'text-anchor': 'middle', 'font-size': 10, 'font-weight': 700, fill: 'var(--color-primary-contrast)' });
                    count.textContent = n._collapsed ? '+' + countAll(n) : String(n.children.length);
                    badge.appendChild(count);
                    g.appendChild(badge);
                }
                root.appendChild(g);
                if (!n._collapsed) draw(n.children, n);
            });
        })(tree, null);

        svg.dataset.minX = bounds.minX;
        svg.dataset.maxX = bounds.maxX;
        svg.dataset.maxY = bounds.maxY;
        applyViewBox();
    }

    function countAll(n) {
        return n.children.reduce(function (sum, c) { return sum + 1 + countAll(c); }, 0);
    }

    function applyViewBox() {
        svg.setAttribute('viewBox', viewBox.x + ' ' + viewBox.y + ' ' + viewBox.w + ' ' + viewBox.h);
    }
    function fit() {
        var minX = parseFloat(svg.dataset.minX || 0), maxX = parseFloat(svg.dataset.maxX || 1200), maxY = parseFloat(svg.dataset.maxY || 700);
        var pad = 40;
        viewBox = { x: minX - pad, y: -pad, w: (maxX - minX) + pad * 2, h: maxY + pad * 2 };
        applyViewBox();
    }

    // ---- interactions ----
    svg.addEventListener('click', function (e) {
        var toggle = e.target.closest('.oc-toggle');
        if (toggle) {
            var node = findNode(tree, parseInt(toggle.dataset.toggle, 10));
            if (node) { node._collapsed = !node._collapsed; render(); }
            return;
        }
        var card = e.target.closest('.oc-node');
        if (card) openPanel(findNode(tree, parseInt(card.dataset.id, 10)));
    });

    function findNode(nodes, id) {
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].id === id) return nodes[i];
            var found = findNode(nodes[i].children, id);
            if (found) return found;
        }
        return null;
    }

    function openPanel(n) {
        if (!n) return;
        var panel = document.getElementById('oc-panel');
        var body = document.getElementById('oc-panel-body');
        body.innerHTML = '';
        var h = document.createElement('h2'); h.textContent = n.name;
        var t = document.createElement('p'); t.className = 'text-muted'; t.textContent = (n.title || '') + (n.dept ? ' · ' + n.dept : '');
        var reports = document.createElement('p'); reports.textContent = n.children.length + ' direct report(s)';
        var link = document.createElement('a'); link.className = 'btn btn-primary btn-sm'; link.href = n.profile_url; link.textContent = 'Full profile';
        body.appendChild(h); body.appendChild(t); body.appendChild(reports); body.appendChild(link);
        panel.hidden = false;
    }
    document.getElementById('oc-panel-close').addEventListener('click', function () {
        document.getElementById('oc-panel').hidden = true;
    });

    // pan
    var panning = null;
    svg.addEventListener('mousedown', function (e) {
        panning = { x: e.clientX, y: e.clientY, vx: viewBox.x, vy: viewBox.y };
    });
    window.addEventListener('mousemove', function (e) {
        if (!panning) return;
        var scale = viewBox.w / svg.clientWidth;
        viewBox.x = panning.vx - (e.clientX - panning.x) * scale;
        viewBox.y = panning.vy - (e.clientY - panning.y) * scale;
        applyViewBox();
    });
    window.addEventListener('mouseup', function () { panning = null; });

    // zoom
    function zoom(factor) {
        var cx = viewBox.x + viewBox.w / 2, cy = viewBox.y + viewBox.h / 2;
        viewBox.w *= factor; viewBox.h *= factor;
        viewBox.x = cx - viewBox.w / 2; viewBox.y = cy - viewBox.h / 2;
        applyViewBox();
    }
    svg.addEventListener('wheel', function (e) {
        e.preventDefault();
        zoom(e.deltaY > 0 ? 1.12 : 0.89);
    }, { passive: false });
    document.getElementById('oc-zoom-in').addEventListener('click', function () { zoom(0.8); });
    document.getElementById('oc-zoom-out').addEventListener('click', function () { zoom(1.25); });
    document.getElementById('oc-fit').addEventListener('click', fit);

    // search: expand the path to the first match + center it
    document.getElementById('oc-search').addEventListener('input', function () {
        var term = this.value.trim().toLowerCase();
        highlightId = null;
        if (term.length >= 2) {
            var path = [];
            (function search(nodes, trail) {
                for (var i = 0; i < nodes.length; i++) {
                    if (path.length) return;
                    if (String(nodes[i].name).toLowerCase().indexOf(term) !== -1) {
                        path = trail.concat([nodes[i]]);
                        return;
                    }
                    search(nodes[i].children, trail.concat([nodes[i]]));
                }
            })(tree, []);
            if (path.length) {
                path.forEach(function (n) { n._collapsed = false; });
                highlightId = path[path.length - 1].id;
                render();
                var target = path[path.length - 1];
                viewBox.x = target._x + CARD_W / 2 - viewBox.w / 2;
                viewBox.y = target._y + CARD_H / 2 - viewBox.h / 2;
                applyViewBox();
                return;
            }
        }
        render();
    });

    // department dim filter
    document.getElementById('oc-dept-filter').addEventListener('change', function () {
        filterDept = this.value;
        render();
    });

    // PNG export
    document.getElementById('oc-export').addEventListener('click', function () {
        var clone = svg.cloneNode(true);
        // inline the CSS variables so the standalone SVG keeps its colors
        var styles = getComputedStyle(document.documentElement);
        ['--color-surface', '--color-border', '--color-text', '--color-text-muted', '--color-primary', '--color-primary-contrast'].forEach(function (v) {
            clone.innerHTML = clone.innerHTML.split('var(' + v + ')').join(styles.getPropertyValue(v).trim() || '#888');
        });
        clone.setAttribute('xmlns', NS);
        var vb = svg.getAttribute('viewBox').split(' ');
        clone.setAttribute('width', vb[2]);
        clone.setAttribute('height', vb[3]);
        var blob = new Blob([new XMLSerializer().serializeToString(clone)], { type: 'image/svg+xml;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var img = new Image();
        img.onload = function () {
            var canvas = document.createElement('canvas');
            canvas.width = img.width * 2;
            canvas.height = img.height * 2;
            var ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);
            var a = document.createElement('a');
            a.download = 'org-chart.png';
            a.href = canvas.toDataURL('image/png');
            a.click();
        };
        img.src = url;
    });

    render();
    fit();
})();
