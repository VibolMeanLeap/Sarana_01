/**
 * Coffee Corner Aura — safe, additive Khmer UI translator.
 * Translates only exact, approved interface strings; never machine-translates
 * customer messages, product names, order notes, or other user-generated data.
 */
(() => {
    'use strict';

    const locale = document.documentElement.lang || (document.body?.classList.contains('locale-km') ? 'km' : 'en');
    if (locale !== 'km' && !document.body?.classList.contains('locale-km')) return;

    const dictionaryUrl = document.documentElement.dataset.khmerDictionary
        || window.CC_KHMER_DICTIONARY_URL
        || '/assets/lang/km-ui.json';

    const skipSelector = [
        'script', 'style', 'noscript', 'svg', 'code', 'pre',
        '[contenteditable="true"]', '[data-no-translate]',
        '.customer-message', '.chat-message', '.chat-bubble', '.message-body',
        '.thread-message', '.cs-message', '.cs-message-bubble', '.cs-message-meta', '.cs-chat-person',
        '.user-generated-content', '.product-name', '.product-title', '.product-description',
        '.customer-name', '.customer-note', '.order-note', '.review-comment', '.review-content'
    ].join(',');

    let dictionary = null;
    let observer = null;
    const pending = new Set();
    let scheduled = false;
    let phraseRules = [];

    const normalize = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();
    const escapeRegExp = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    function translateKnownPhrases(value) {
        let result = String(value ?? '');
        for (const rule of phraseRules) {
            result = result.replace(rule.pattern, (_match, prefix) => `${prefix}${rule.translated}`);
        }
        return result;
    }

    function translateTextNode(node) {
        if (!node || node.nodeType !== Node.TEXT_NODE || !node.nodeValue) return;
        const parent = node.parentElement;
        if (!parent || parent.closest(skipSelector) || parent.closest('textarea, input')) return;

        const original = node.nodeValue;
        const leading = original.match(/^\s*/)?.[0] ?? '';
        const trailing = original.match(/\s*$/)?.[0] ?? '';
        const key = normalize(original);
        if (!key) return;

        const translated = Object.prototype.hasOwnProperty.call(dictionary, key)
            ? dictionary[key]
            : translateKnownPhrases(original);
        if (translated && translated !== original) {
            node.nodeValue = `${leading}${translated.trim()}${trailing}`;
        }
    }

    function translateAttributes(root) {
        if (!root || root.nodeType !== Node.ELEMENT_NODE) return;
        const nodes = [];
        if (root.matches?.('[placeholder], [title], [aria-label], [alt], [data-bs-title], input[type=submit], input[type=button], input[type=reset]')) nodes.push(root);
        root.querySelectorAll?.('[placeholder], [title], [aria-label], [alt], [data-bs-title], input[type=submit], input[type=button], input[type=reset]').forEach((el) => nodes.push(el));

        for (const el of nodes) {
            if (el.closest(skipSelector)) continue;
            for (const attr of ['placeholder', 'title', 'aria-label', 'alt', 'data-bs-title']) {
                const value = el.getAttribute(attr);
                const key = normalize(value);
                if (key && Object.prototype.hasOwnProperty.call(dictionary, key)) {
                    el.setAttribute(attr, dictionary[key]);
                } else if (value) {
                    const translated = translateKnownPhrases(value);
                    if (translated !== value) el.setAttribute(attr, translated);
                }
            }
            if (el.matches('input[type=submit], input[type=button], input[type=reset]')) {
                const value = el.getAttribute('value');
                const key = normalize(value);
                if (key && Object.prototype.hasOwnProperty.call(dictionary, key)) el.setAttribute('value', dictionary[key]);
            }
        }
    }

    function translateRoot(root) {
        if (!root) return;
        if (root.nodeType === Node.TEXT_NODE) {
            translateTextNode(root);
            return;
        }
        if (root.nodeType !== Node.ELEMENT_NODE && root !== document) return;

        const start = root === document ? document.body : root;
        if (!start) return;
        if (start.nodeType === Node.ELEMENT_NODE && start.closest?.(skipSelector)) return;

        const walker = document.createTreeWalker(start, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) translateTextNode(node);
        if (start.nodeType === Node.ELEMENT_NODE) translateAttributes(start);
    }

    function flushPending() {
        scheduled = false;
        for (const node of pending) translateRoot(node);
        pending.clear();
    }

    function schedule(node) {
        if (!node) return;
        pending.add(node);
        if (!scheduled) {
            scheduled = true;
            window.requestAnimationFrame(flushPending);
        }
    }

    function startObserver() {
        translateRoot(document);
        if (observer || !document.body) return;
        observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === 'characterData') {
                    schedule(mutation.target);
                } else {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE || node.nodeType === Node.TEXT_NODE) schedule(node);
                    });
                    if (mutation.type === 'attributes') schedule(mutation.target);
                }
            }
        });
        observer.observe(document.body, {
            subtree: true,
            childList: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['placeholder', 'title', 'aria-label', 'alt', 'data-bs-title']
        });
    }

    fetch(dictionaryUrl, { headers: { 'Accept': 'application/json' }, cache: 'force-cache' })
        .then((response) => {
            if (!response.ok) throw new Error(`Khmer dictionary request failed (${response.status})`);
            return response.json();
        })
        .then((data) => {
            if (!data || typeof data !== 'object' || Array.isArray(data)) throw new Error('Invalid Khmer dictionary format');
            dictionary = data;
            phraseRules = Object.entries(dictionary)
                .filter(([key]) => key.includes(' ') && /[A-Za-z]/.test(key))
                .sort((a, b) => b[0].length - a[0].length)
                .map(([key, translated]) => {
                    const after = key.endsWith('#') ? '(?=\\d|$|[^A-Za-z0-9])' : '(?=$|[^A-Za-z0-9])';
                    return {
                        pattern: new RegExp(`(^|[^A-Za-z0-9])(${escapeRegExp(key)})${after}`, 'gi'),
                        translated
                    };
                });
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startObserver, { once: true });
            } else {
                startObserver();
            }
        })
        .catch((error) => {
            console.warn('[Coffee Corner Aura] Khmer UI translation unavailable:', error.message);
        });
})();
