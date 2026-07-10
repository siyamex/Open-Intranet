// Visual theme editor: token controls, WCAG checks, dark derivation,
// live iframe preview via postMessage, save/save-as/reset.
(function () {
    'use strict';
    var editor = document.getElementById('theme-editor');
    if (!editor) return;

    var COLOR_TOKENS = [
        ['color-primary', 'Primary'], ['color-primary-contrast', 'Primary contrast'],
        ['color-accent', 'Accent'], ['color-bg', 'Background'],
        ['color-surface', 'Surface'], ['color-surface-2', 'Surface 2'],
        ['color-text', 'Text'], ['color-text-muted', 'Muted text'],
        ['color-border', 'Border'], ['color-success', 'Success'],
        ['color-warning', 'Warning'], ['color-danger', 'Danger']
    ];
    var DEFAULTS = {
        'color-primary': '#4f46e5', 'color-primary-contrast': '#ffffff', 'color-accent': '#0ea5e9',
        'color-bg': '#f3f4f6', 'color-surface': '#ffffff', 'color-surface-2': '#f9fafb',
        'color-text': '#111827', 'color-text-muted': '#6b7280', 'color-border': '#e5e7eb',
        'color-success': '#16a34a', 'color-warning': '#d97706', 'color-danger': '#dc2626'
    };

    var vars = JSON.parse(editor.dataset.vars || '{}');
    var dark = JSON.parse(editor.dataset.dark || '{}');
    var saved = { vars: JSON.parse(JSON.stringify(vars)), dark: JSON.parse(JSON.stringify(dark)), css: cssArea().value };
    var variant = 'light';
    var dirty = false;
    var frame = document.getElementById('preview-frame');

    function cssArea() { return document.getElementById('ctl-custom-css'); }
    function current() { return variant === 'dark' ? dark : vars; }
    function get(key) {
        if (variant === 'dark' && dark[key] !== undefined) return dark[key];
        if (vars[key] !== undefined) return vars[key];
        return DEFAULTS[key] || '';
    }
    function set(key, value) {
        current()[key] = value;
        markDirty();
        push();
    }
    function markDirty() {
        dirty = true;
        document.getElementById('dirty-hint').hidden = false;
    }

    // ---- Color controls -----------------------------------------------------
    var colorBox = document.getElementById('color-controls');
    function buildColors() {
        colorBox.innerHTML = '';
        COLOR_TOKENS.forEach(function (pair) {
            var key = pair[0], label = pair[1];
            var row = document.createElement('div');
            row.className = 'token-row';
            var value = normalizeHex(get(key)) || '#000000';
            row.innerHTML = '<label></label><input type="color"><input type="text" class="form-control hex-input" maxlength="9">';
            row.querySelector('label').textContent = label;
            var picker = row.querySelector('input[type=color]');
            var hex = row.querySelector('.hex-input');
            picker.value = value.length === 7 ? value : '#000000';
            hex.value = get(key);
            picker.addEventListener('input', function () {
                hex.value = picker.value;
                set(key, picker.value);
                checkContrast();
            });
            hex.addEventListener('change', function () {
                var v = normalizeHex(hex.value);
                if (v) { picker.value = v.slice(0, 7); set(key, v); checkContrast(); }
            });
            colorBox.appendChild(row);
        });
        checkContrast();
    }
    function normalizeHex(v) {
        v = String(v || '').trim();
        if (/^#[0-9a-f]{3}$/i.test(v)) {
            return '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
        }
        return /^#[0-9a-f]{6}([0-9a-f]{2})?$/i.test(v) ? v.toLowerCase() : null;
    }

    // ---- WCAG contrast --------------------------------------------------------
    function luminance(hex) {
        var v = normalizeHex(hex);
        if (!v) return null;
        var rgb = [1, 3, 5].map(function (i) {
            var c = parseInt(v.substr(i, 2), 16) / 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
    }
    function ratio(a, b) {
        var l1 = luminance(a), l2 = luminance(b);
        if (l1 === null || l2 === null) return null;
        var hi = Math.max(l1, l2), lo = Math.min(l1, l2);
        return (hi + 0.05) / (lo + 0.05);
    }
    function checkContrast() {
        var box = document.getElementById('contrast-warnings');
        box.innerHTML = '';
        [
            ['color-text', 'color-bg', 'Text on background'],
            ['color-text', 'color-surface', 'Text on cards'],
            ['color-primary-contrast', 'color-primary', 'Primary button text']
        ].forEach(function (check) {
            var r = ratio(get(check[0]), get(check[1]));
            if (r === null) return;
            var p = document.createElement('p');
            var pass = r >= 4.5;
            p.className = 'contrast-note ' + (pass ? 'pass' : 'fail');
            p.textContent = (pass ? '✓ ' : '⚠ ') + check[2] + ': ' + r.toFixed(1) + ':1' + (pass ? ' (AA)' : ' — below WCAG AA (4.5:1)');
            box.appendChild(p);
        });
    }

    // ---- Suggested palettes ------------------------------------------------------
    document.querySelectorAll('[data-palette]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var palette = JSON.parse(btn.dataset.palette);
            Object.keys(palette).forEach(function (k) { current()[k] = palette[k]; });
            markDirty();
            buildColors();
            push();
        });
    });

    // ---- Typography / shape / layout controls -------------------------------------
    function bindSelect(id, key, transform) {
        var el = document.getElementById(id);
        el.addEventListener('change', function () {
            (transform || function (v) { set(key, v); })(el.value);
        });
        return el;
    }
    var fontSel = bindSelect('ctl-font-family', 'font-family');
    fontSel.value = get('font-family') || fontSel.options[0].value;

    var fontSize = document.getElementById('ctl-font-size');
    fontSize.value = parseFloat(get('font-size-base')) || 16;
    document.getElementById('font-size-value').textContent = fontSize.value + 'px';
    fontSize.addEventListener('input', function () {
        document.getElementById('font-size-value').textContent = fontSize.value + 'px';
        set('font-size-base', fontSize.value + 'px');
    });

    var radius = document.getElementById('ctl-radius');
    radius.value = parseInt(get('radius-md'), 10) || 10;
    document.getElementById('radius-value').textContent = radius.value + 'px';
    radius.addEventListener('input', function () {
        var r = parseInt(radius.value, 10);
        document.getElementById('radius-value').textContent = r + 'px';
        current()['radius-md'] = r + 'px';
        current()['radius-sm'] = Math.max(0, r - 4) + 'px';
        current()['radius-lg'] = (r + 6) + 'px';
        markDirty();
        push();
    });

    bindSelect('ctl-density', 'space-unit');
    bindSelect('ctl-shadow', '', function (v) {
        var map = {
            none: ['none', 'none'],
            soft: ['0 1px 2px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.08)', '0 4px 12px rgba(0,0,0,0.10)'],
            strong: ['0 2px 4px rgba(0,0,0,0.12)', '0 10px 28px rgba(0,0,0,0.22)']
        };
        current()['shadow-1'] = map[v][0];
        current()['shadow-2'] = map[v][1];
        markDirty();
        push();
    });
    bindSelect('ctl-navbar', '', function (v) {
        var map = {
            solid: 'var(--color-surface)',
            gradient: 'linear-gradient(90deg, var(--color-primary), var(--color-accent))',
            transparent: 'transparent'
        };
        set('navbar-bg', map[v]);
    });
    bindSelect('ctl-sidebar', '', function (v) {
        var map = { light: 'var(--color-surface)', dark: '#1e293b', brand: 'var(--color-primary)' };
        set('sidebar-bg', map[v]);
    });
    bindSelect('ctl-links', 'link-decoration');

    // ---- Login background ------------------------------------------------------
    var loginMode = document.getElementById('ctl-login-bg-mode');
    var loginImageRow = document.getElementById('login-image-row');
    loginMode.addEventListener('change', function () {
        loginImageRow.hidden = loginMode.value !== 'image';
        if (loginMode.value === 'color') {
            delete current()['login-bg'];
            delete current()['login-overlay'];
            markDirty(); push();
        } else if (loginMode.value === 'gradient') {
            set('login-bg', 'linear-gradient(135deg, var(--color-primary), var(--color-accent))');
        }
    });
    document.getElementById('ctl-login-image').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('image', file);
        fd.append('_token', editor.dataset.csrf);
        fetch(editor.dataset.uploadUrl, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { alert(data.error); return; }
                var path = new URL(data.url, window.location.href).pathname;
                set('login-bg', 'url(' + path + ') center / cover');
                var overlay = document.getElementById('ctl-login-overlay');
                set('login-overlay', String(parseInt(overlay.value, 10) / 100));
            });
    });
    var overlaySlider = document.getElementById('ctl-login-overlay');
    overlaySlider.addEventListener('input', function () {
        document.getElementById('overlay-value').textContent = overlaySlider.value + '%';
        set('login-overlay', String(parseInt(overlaySlider.value, 10) / 100));
    });
    document.getElementById('overlay-value').textContent = overlaySlider.value + '%';

    // ---- Dark variant tools ---------------------------------------------------
    document.querySelectorAll('[data-variant]').forEach(function (tabBtn) {
        tabBtn.addEventListener('click', function () {
            variant = tabBtn.dataset.variant;
            document.querySelectorAll('[data-variant]').forEach(function (b) {
                b.classList.toggle('active', b === tabBtn);
            });
            document.getElementById('dark-tools').hidden = variant !== 'dark';
            document.getElementById('preview-dark').checked = variant === 'dark';
            buildColors();
            push();
        });
    });
    document.getElementById('derive-btn').addEventListener('click', function () {
        var curve = parseInt(document.getElementById('derive-curve').value, 10);
        dark = deriveDark(vars, curve);
        markDirty();
        buildColors();
        push();
    });
    function hexToHsl(hex) {
        var v = normalizeHex(hex) || '#888888';
        var r = parseInt(v.substr(1, 2), 16) / 255, g = parseInt(v.substr(3, 2), 16) / 255, b = parseInt(v.substr(5, 2), 16) / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), h = 0, s = 0, l = (max + min) / 2;
        var d = max - min;
        if (d !== 0) {
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) / 6;
            else if (max === g) h = ((b - r) / d + 2) / 6;
            else h = ((r - g) / d + 4) / 6;
        }
        return [h * 360, s * 100, l * 100];
    }
    function hslToHex(h, s, l) {
        h /= 360; s = Math.min(100, Math.max(0, s)) / 100; l = Math.min(100, Math.max(0, l)) / 100;
        var q = l < 0.5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
        var to = function (t) {
            if (t < 0) t += 1; if (t > 1) t -= 1;
            if (t < 1 / 6) return p + (q - p) * 6 * t;
            if (t < 1 / 2) return q;
            if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
            return p;
        };
        var hex = function (x) { return Math.round(x * 255).toString(16).padStart(2, '0'); };
        return '#' + hex(to(h + 1 / 3)) + hex(to(h)) + hex(to(h - 1 / 3));
    }
    function deriveDark(light, curve) {
        var bgH = hexToHsl(light['color-bg'] || DEFAULTS['color-bg']);
        var primary = hexToHsl(light['color-primary'] || DEFAULTS['color-primary']);
        var out = {
            'color-bg': hslToHex(bgH[0], Math.min(bgH[1], 35), curve * 0.55),
            'color-surface': hslToHex(bgH[0], Math.min(bgH[1], 35), curve * 0.85),
            'color-surface-2': hslToHex(bgH[0], Math.min(bgH[1], 35), curve * 1.1),
            'color-border': hslToHex(bgH[0], Math.min(bgH[1], 30), curve * 1.6),
            'color-text': hslToHex(bgH[0], 15, 90),
            'color-text-muted': hslToHex(bgH[0], 12, 64),
            'color-primary': hslToHex(primary[0], primary[1], Math.min(78, primary[2] + 14)),
            'color-primary-contrast': hslToHex(primary[0], 30, 10)
        };
        out['navbar-bg'] = out['color-surface'];
        out['sidebar-bg'] = out['color-surface'];
        return out;
    }

    // ---- Custom CSS lint -----------------------------------------------------------
    cssArea().addEventListener('input', function () {
        markDirty();
        lintCss();
        push();
    });
    function lintCss() {
        var css = cssArea().value;
        var lint = document.getElementById('css-lint');
        var problems = [];
        if (css.split('{').length !== css.split('}').length) problems.push('Unbalanced braces.');
        if (/@import/i.test(css)) problems.push('@import is banned.');
        if (/expression\s*\(/i.test(css)) problems.push('expression() is banned.');
        lint.hidden = problems.length === 0;
        lint.textContent = problems.join(' ');
        return problems.length === 0;
    }

    // ---- Live preview via postMessage -----------------------------------------------
    function push() {
        if (!frame || !frame.contentWindow) return;
        frame.contentWindow.postMessage({
            type: 'theme-tokens',
            vars: vars,
            dark: dark,
            mode: document.getElementById('preview-dark').checked ? 'dark' : 'light',
            customCss: cssArea().value
        }, window.location.origin);
    }
    frame.addEventListener('load', push);
    document.getElementById('preview-dark').addEventListener('change', push);

    // ---- Device width toggle ----
    document.querySelectorAll('.device-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.device-btn').forEach(function (b) { b.classList.toggle('active', b === btn); });
            frame.style.width = btn.dataset.width;
        });
    });

    // ---- Save / save-as / reset / unsaved-warning ----
    var form = document.getElementById('theme-form');
    form.addEventListener('submit', function (e) {
        if (!lintCss()) {
            e.preventDefault();
            alert('Fix the custom CSS problems before saving.');
            return;
        }
        document.getElementById('field-variables').value = JSON.stringify(vars);
        document.getElementById('field-dark-variables').value = Object.keys(dark).length ? JSON.stringify(dark) : 'null';
        dirty = false;
    });
    document.getElementById('save-as-btn').addEventListener('click', function (e) {
        var name = window.prompt('Name for the new theme:');
        if (!name) { e.preventDefault(); return; }
        var nameField = document.getElementById('save-as-name');
        nameField.value = name;
        nameField.disabled = false;
    });
    document.getElementById('reset-btn').addEventListener('click', function () {
        vars = JSON.parse(JSON.stringify(saved.vars));
        dark = JSON.parse(JSON.stringify(saved.dark));
        cssArea().value = saved.css;
        dirty = false;
        document.getElementById('dirty-hint').hidden = true;
        buildColors();
        push();
    });
    window.addEventListener('beforeunload', function (e) {
        if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });

    buildColors();
})();
