/* Coffee Corner Aura theme controller: Light / Dark / Follow system. */
(function () {
    'use strict';

    var STORAGE_KEY = 'cc-aura-theme';
    var allowed = ['light', 'dark', 'system'];
    var root = document.documentElement;
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var labels = {
        en: { light: 'Light', dark: 'Dark', system: 'Follow system', changed: 'Appearance set to {mode}.' },
        km: { light: 'ពន្លឺ', dark: 'ងងឹត', system: 'តាមប្រព័ន្ធ', changed: 'បានប្តូររូបរាងទៅជា {mode}។' }
    };

    function currentLocale() {
        var switcher = document.querySelector('[data-cc-theme-switcher][data-cc-locale]');
        var locale = switcher ? switcher.getAttribute('data-cc-locale') : (document.documentElement.lang || 'en');
        return String(locale || 'en').toLowerCase().slice(0, 2);
    }

    function getPreference() {
        var value = root.getAttribute('data-cc-theme-preference') || 'system';
        try {
            var stored = window.localStorage.getItem(STORAGE_KEY);
            if (allowed.indexOf(stored) !== -1) value = stored;
        } catch (error) {
            /* Continue with the preference already applied by the bootstrap. */
        }
        return allowed.indexOf(value) !== -1 ? value : 'system';
    }

    function resolve(preference) {
        if (preference !== 'system') return preference;
        return media && media.matches ? 'dark' : 'light';
    }

    function apply(preference, announce) {
        if (allowed.indexOf(preference) === -1) preference = 'system';
        var resolved = resolve(preference);
        root.setAttribute('data-cc-theme', resolved);
        root.setAttribute('data-cc-theme-preference', preference);
        root.setAttribute('data-bs-theme', resolved);

        try {
            window.localStorage.setItem(STORAGE_KEY, preference);
        } catch (error) {
            /* Theme still applies for the current page even if storage is blocked. */
        }

        var locale = currentLocale();
        var copy = labels[locale] || labels.en;
        document.querySelectorAll('[data-cc-theme-current]').forEach(function (node) {
            node.textContent = copy[preference];
        });
        document.querySelectorAll('[data-cc-theme-mode]').forEach(function (button) {
            var selected = button.getAttribute('data-cc-theme-mode') === preference;
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('is-active', selected);
        });

        if (announce) {
            var live = document.querySelector('[data-cc-theme-live]');
            if (live) live.textContent = copy.changed.replace('{mode}', copy[preference]);
        }
    }

    function closeAll(except) {
        document.querySelectorAll('[data-cc-theme-switcher]').forEach(function (wrap) {
            if (wrap === except) return;
            var trigger = wrap.querySelector('[data-cc-theme-trigger]');
            var menu = wrap.querySelector('[data-cc-theme-menu]');
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
            if (menu) menu.hidden = true;
        });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-cc-theme-trigger]');
        if (trigger) {
            var wrap = trigger.closest('[data-cc-theme-switcher]');
            var menu = wrap && wrap.querySelector('[data-cc-theme-menu]');
            var willOpen = !!menu && menu.hidden;
            closeAll(wrap);
            if (menu) menu.hidden = !willOpen;
            trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }

        var choice = event.target.closest('[data-cc-theme-mode]');
        if (choice) {
            apply(choice.getAttribute('data-cc-theme-mode'), true);
            var parent = choice.closest('[data-cc-theme-switcher]');
            var parentMenu = parent && parent.querySelector('[data-cc-theme-menu]');
            var parentTrigger = parent && parent.querySelector('[data-cc-theme-trigger]');
            if (parentMenu) parentMenu.hidden = true;
            if (parentTrigger) parentTrigger.setAttribute('aria-expanded', 'false');
            return;
        }

        if (!event.target.closest('[data-cc-theme-switcher]')) closeAll(null);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAll(null);
            var active = document.activeElement;
            if (active && active.matches('[data-cc-theme-trigger]')) active.blur();
        }
    });

    if (media) {
        var onSystemChange = function () {
            if (getPreference() === 'system') apply('system', false);
        };
        if (typeof media.addEventListener === 'function') media.addEventListener('change', onSystemChange);
        else if (typeof media.addListener === 'function') media.addListener(onSystemChange);
    }

    /* Re-apply after page navigation and synchronize all selector instances. */
    apply(getPreference(), false);
})();
