// Runs inside the theme editor's preview iframe: applies token updates
// pushed via postMessage instantly, without reloads.
(function () {
    'use strict';
    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) return;
        var data = event.data || {};
        if (data.type !== 'theme-tokens') return;
        var root = document.documentElement;
        root.setAttribute('data-theme', data.mode === 'dark' ? 'dark' : 'light');
        // Reset previously inline-applied vars, then apply the new set
        root.style.cssText = '';
        var vars = (data.mode === 'dark') ? Object.assign({}, data.vars, data.dark) : data.vars;
        Object.keys(vars || {}).forEach(function (key) {
            if (/^[a-z0-9-]+$/.test(key)) {
                root.style.setProperty('--' + key, String(vars[key]));
            }
        });
        var style = document.getElementById('tp-custom-css');
        if (!style) {
            style = document.createElement('style');
            style.id = 'tp-custom-css';
            document.head.appendChild(style);
        }
        style.textContent = data.customCss || '';
    });
})();
