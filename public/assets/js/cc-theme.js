/* SK Coffee theme controller: one-click Light / Dark toggle with a smooth transition. */
(function () {
    "use strict";

    var STORAGE_KEY = "cc-aura-theme";
    var allowed = ["light", "dark", "system"];
    var root = document.documentElement;
    var media = window.matchMedia
        ? window.matchMedia("(prefers-color-scheme: dark)")
        : null;
    var animationTimer = null;
    var animationFrame = null;
    var labels = {
        en: {
            light: "Light mode enabled",
            dark: "Dark mode enabled",
            switchToLight: "Switch to light mode",
            switchToDark: "Switch to dark mode",
        },
        km: {
            light: "បានបើករូបរាងភ្លឺ",
            dark: "បានបើករូបរាងងងឹត",
            switchToLight: "ប្តូរទៅរូបរាងភ្លឺ",
            switchToDark: "ប្តូរទៅរូបរាងងងឹត",
        },
    };

    function currentLocale() {
        var switcher = document.querySelector("[data-cc-theme-switcher][data-cc-locale]");
        var locale = switcher ? switcher.getAttribute("data-cc-locale") : (root.lang || "en");
        return String(locale || "en").toLowerCase().slice(0, 2);
    }

    function getPreference() {
        var value = root.getAttribute("data-cc-theme-preference") || "system";
        try {
            var stored = window.localStorage.getItem(STORAGE_KEY);
            if (allowed.indexOf(stored) !== -1) value = stored;
        } catch (error) {
            /* Use the preference already applied by the synchronous bootstrap. */
        }
        return allowed.indexOf(value) !== -1 ? value : "system";
    }

    function resolve(preference) {
        if (preference !== "system") return preference;
        return media && media.matches ? "dark" : "light";
    }

    function updateCharts(theme) {
        if (!window.Chart || !window.Chart.defaults) return;
        window.Chart.defaults.color = theme === "dark" ? "#c5d3c9" : "#596a60";
        window.Chart.defaults.borderColor = theme === "dark" ? "#34463b" : "#e3eae5";
        if (window.Chart.instances) {
            Object.keys(window.Chart.instances).forEach(function (key) {
                var chart = window.Chart.instances[key];
                if (chart && typeof chart.update === "function") chart.update("none");
            });
        }
    }

    function updateToggle(preference, resolved, announce) {
        var locale = currentLocale();
        var copy = labels[locale] || labels.en;
        var switchToLight = resolved === "dark";
        var nextLabel = switchToLight ? copy.switchToLight : copy.switchToDark;

        document.querySelectorAll("[data-cc-theme-toggle]").forEach(function (button) {
            var icon = button.querySelector("[data-cc-theme-icon]");
            button.setAttribute("aria-label", nextLabel);
            button.setAttribute("title", nextLabel);
            button.setAttribute("aria-pressed", resolved === "dark" ? "true" : "false");
            if (icon) {
                icon.classList.toggle("bi-sun-fill", switchToLight);
                icon.classList.toggle("bi-moon-stars-fill", !switchToLight);
            }
        });

        if (announce) {
            document.querySelectorAll("[data-cc-theme-live]").forEach(function (node) {
                node.textContent = copy[resolved];
            });
        }
    }

    function commit(preference, announce) {
        var resolved = resolve(preference);
        root.setAttribute("data-cc-theme", resolved);
        root.setAttribute("data-cc-theme-preference", preference);
        root.setAttribute("data-bs-theme", resolved);

        try {
            window.localStorage.setItem(STORAGE_KEY, preference);
        } catch (error) {
            /* Theme still applies for this page if storage is unavailable. */
        }

        updateCharts(resolved);
        updateToggle(preference, resolved, announce);
        window.dispatchEvent(new CustomEvent("cc-theme-change", {
            detail: { preference: preference, theme: resolved }
        }));
    }

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
    }

    function apply(preference, announce, animate) {
        if (allowed.indexOf(preference) === -1) preference = "system";

        if (!animate || prefersReducedMotion()) {
            commit(preference, announce);
            return;
        }

        if (animationTimer) window.clearTimeout(animationTimer);
        if (animationFrame) window.cancelAnimationFrame(animationFrame);
        root.classList.add("cc-theme-animating");
        /* Commit the transition rules before changing the theme attributes. */
        void root.offsetWidth;
        document.querySelectorAll("[data-cc-theme-icon]").forEach(function (icon) {
            icon.classList.remove("is-switching");
            void icon.offsetWidth;
            icon.classList.add("is-switching");
        });

        animationFrame = window.requestAnimationFrame(function () {
            commit(preference, announce);
            animationTimer = window.setTimeout(function () {
                root.classList.remove("cc-theme-animating");
                document.querySelectorAll("[data-cc-theme-icon]").forEach(function (icon) {
                    icon.classList.remove("is-switching");
                });
                animationTimer = null;
                animationFrame = null;
            }, 420);
        });
    }

    document.addEventListener("click", function (event) {
        var button = event.target.closest("[data-cc-theme-toggle]");
        if (!button) return;

        var currentTheme = root.getAttribute("data-cc-theme") || resolve(getPreference());
        var nextTheme = currentTheme === "dark" ? "light" : "dark";
        apply(nextTheme, true, true);
    });

    if (media) {
        var onSystemChange = function () {
            if (getPreference() === "system") apply("system", false, false);
        };
        if (typeof media.addEventListener === "function") media.addEventListener("change", onSystemChange);
        else if (typeof media.addListener === "function") media.addListener(onSystemChange);
    }

    /* Apply the saved preference without animating page load. */
    apply(getPreference(), false, false);
})();
