(() => {
    'use strict';

    const forms = document.querySelectorAll('form[data-login-lockout]');
    if (!forms.length) return;

    forms.forEach((form) => {
        const button = form.querySelector('button[type="submit"], input[type="submit"]');
        if (!button) return;

        const portal = form.dataset.loginPortal || 'login';
        const storageKey = `coffee-corner:login-lockout:${portal}`;
        const serverSeconds = Math.max(0, Number.parseInt(form.dataset.loginLockSeconds || '0', 10) || 0);
        const waitLabel = form.dataset.loginWaitLabel || 'Sign-in temporarily locked';
        const originalHtml = button.tagName === 'BUTTON' ? button.innerHTML : null;
        const originalValue = button.tagName === 'INPUT' ? button.value : null;

        let unlockAt = serverSeconds > 0 ? Date.now() + (serverSeconds * 1000) : 0;
        try {
            const storedUntil = Number.parseInt(localStorage.getItem(storageKey) || '0', 10) || 0;
            unlockAt = Math.max(unlockAt, storedUntil);
        } catch (_) {
            // Storage may be disabled. Server-side lockout remains authoritative.
        }

        if (unlockAt <= Date.now()) {
            try { localStorage.removeItem(storageKey); } catch (_) {}
            return;
        }

        try { localStorage.setItem(storageKey, String(unlockAt)); } catch (_) {}

        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
        button.classList.add('login-lockout-counting');

        let timerId = null;
        const render = () => {
            const remaining = Math.max(0, Math.ceil((unlockAt - Date.now()) / 1000));

            if (remaining <= 0) {
                button.disabled = false;
                button.removeAttribute('aria-disabled');
                button.classList.remove('login-lockout-counting');
                if (originalHtml !== null) button.innerHTML = originalHtml;
                if (originalValue !== null) button.value = originalValue;
                try { localStorage.removeItem(storageKey); } catch (_) {}
                if (timerId !== null) window.clearTimeout(timerId);
                return;
            }

            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            const clock = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            const label = `${waitLabel} · ${clock}`;

            if (button.tagName === 'INPUT') button.value = label;
            else button.textContent = label;

            timerId = window.setTimeout(render, 250);
        };

        render();
    });
})();
