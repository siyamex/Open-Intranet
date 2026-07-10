// Applies the saved color scheme before first paint to avoid flashing.
(function () {
    'use strict';
    var pref = localStorage.getItem('theme') || 'auto';
    var dark = pref === 'dark' || (pref === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
})();
