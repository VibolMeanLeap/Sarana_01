/* Coffee Corner Aura theme bootstrap. Keep this small script synchronous in <head> to prevent theme flash. */
(function () {
    'use strict';
    var allowed = ['light', 'dark', 'system'];
    var preference = 'system';
    try {
        var saved = window.localStorage.getItem('cc-aura-theme');
        if (allowed.indexOf(saved) !== -1) preference = saved;
    } catch (error) {
        /* Storage may be unavailable; system preference remains a safe fallback. */
    }
    var systemDark = false;
    try {
        systemDark = !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    } catch (error) {
        systemDark = false;
    }
    var resolved = preference === 'system' ? (systemDark ? 'dark' : 'light') : preference;
    var root = document.documentElement;
    root.setAttribute('data-cc-theme', resolved);
    root.setAttribute('data-cc-theme-preference', preference);
    root.setAttribute('data-bs-theme', resolved);
})();
