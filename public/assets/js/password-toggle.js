(() => {
    'use strict';

    const iconMarkup = (visible) => visible
        ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
        : '<i class="bi bi-eye" aria-hidden="true"></i>';

    function hasExistingToggle(input) {
        const wrapper = input.closest('.auth-field, .customer-auth-field, .password-field, .cc-password-field');
        if (wrapper && wrapper.querySelector('button.auth-eye, button.customer-auth-eye, button[data-password-toggle], button.cc-password-toggle')) {
            return true;
        }
        return Boolean(input.nextElementSibling?.matches('button[aria-label*="password" i]'));
    }

    function initializePasswordToggles(scope = document) {
        scope.querySelectorAll('input[type="password"]').forEach((input) => {
            if (input.dataset.ccPasswordReady === '1') return;
            input.dataset.ccPasswordReady = '1';

            // Existing login-page controls already work and should not be duplicated.
            if (hasExistingToggle(input)) return;

            const wrapper = input.parentElement;
            if (!wrapper) return;

            wrapper.classList.add('cc-password-field');
            input.classList.add('cc-password-input');
            input.setAttribute('spellcheck', 'false');

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'cc-password-toggle';
            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute('title', 'Show password');
            button.innerHTML = iconMarkup(false);

            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.innerHTML = iconMarkup(show);
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                button.setAttribute('title', show ? 'Hide password' : 'Show password');
                button.setAttribute('aria-pressed', show ? 'true' : 'false');
                input.focus({ preventScroll: true });
            });

            wrapper.appendChild(button);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initializePasswordToggles());
    } else {
        initializePasswordToggles();
    }

    // Also handle password fields rendered later inside dialogs or dynamically loaded forms.
    if ('MutationObserver' in window) {
        const observer = new MutationObserver((records) => {
            records.forEach((record) => record.addedNodes.forEach((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) return;
                if (node.matches?.('input[type="password"]')) initializePasswordToggles(node.parentElement || document);
                if (node.querySelectorAll) initializePasswordToggles(node);
            }));
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
