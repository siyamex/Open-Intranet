// Applies the color scheme before first paint. Preference order:
// localStorage ('theme-mode') > server-side user pref (data-theme-mode) > auto.
(function () {
    'use strict';
    var root = document.documentElement;
    var mode = localStorage.getItem('theme-mode') || root.getAttribute('data-theme-mode') || 'auto';
    if (['auto', 'light', 'dark'].indexOf(mode) === -1) mode = 'auto';
    var dark = mode === 'dark' || (mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    root.setAttribute('data-theme', dark ? 'dark' : 'light');
    root.setAttribute('data-theme-mode', mode);
})();
