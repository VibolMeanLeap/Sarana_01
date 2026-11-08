/**
 * Coffee Corner Aura - shared form validation UX.
 * Laravel validation remains authoritative; this script provides consistent
 * client-side required checks, red borders, inline feedback and focus management.
 */
(() => {
    'use strict';

    const serverErrors = window.CoffeeCornerValidationErrors || {};
    const controlSelector = 'input[name], select[name], textarea[name]';
    const ignoredTypes = new Set(['hidden', 'submit', 'button', 'reset', 'image']);
    const checkableTypes = new Set(['checkbox', 'radio']);

    const asText = value => String(value ?? '').trim();

    function isVisibleControl(field) {
        if (!field || field.disabled || field.readOnly) return false;
        if (ignoredTypes.has((field.type || '').toLowerCase())) return false;
        if (field.closest('[hidden], [aria-hidden="true"]')) return false;
        return true;
    }

    function conditionIsActive(field) {
        const sourceName = field.dataset.requiredWhen;
        if (sourceName) {
            const source = field.form?.elements?.namedItem(sourceName);
            if (!source) return false;
            const option = source.selectedOptions?.[0];
            const current = asText(option?.dataset?.type || source.value || option?.textContent).toLowerCase();
            return current === asText(field.dataset.requiredWhenValue).toLowerCase();
        }

        const filledSourceName = field.dataset.requiredWhenFilled;
        if (filledSourceName) {
            const source = field.form?.elements?.namedItem(filledSourceName);
            return Boolean(source && asText(source.value));
        }

        return false;
    }

    function shouldBeRequired(field) {
        if (field.dataset.requiredWhen || field.dataset.requiredWhenFilled) {
            return conditionIsActive(field);
        }
        if (field.hasAttribute('data-optional')) return false;
        if (checkableTypes.has((field.type || '').toLowerCase())) {
            // Checkboxes/radios are optional unless the HTML explicitly requires them.
            return field.hasAttribute('required') || field.dataset.required === 'true';
        }
        return true;
    }

    function radioGroup(form, name) {
        return Array.from(form?.elements || []).filter(field =>
            field.type === 'radio' && field.name === name && !field.disabled
        );
    }

    function validationMessage(field) {
        const type = (field.type || '').toLowerCase();
        const value = asText(field.value);

        if (type === 'radio') {
            const group = radioGroup(field.form, field.name);
            const groupRequired = group.some(item => item.required || item.dataset.required === 'true');
            if (groupRequired && !group.some(item => item.checked)) return 'Please select an option.';
            return '';
        }

        if (type === 'checkbox') {
            if (field.required && !field.checked) return 'Please select this option.';
            return '';
        }

        if (field.required && !value) return 'Please complete this field.';

        if (field.name === 'password_confirmation') {
            const password = field.form?.elements?.namedItem('password');
            if (password && password.value && field.value !== password.value) {
                return 'The password confirmation does not match.';
            }
        }

        // Empty optional fields are valid, even when they have type-specific constraints.
        if (!value && !field.required) return '';

        if (field.validity && !field.validity.valid) {
            if (field.validity.typeMismatch) return 'Please enter a valid value.';
            if (field.validity.tooShort) return `Please enter at least ${field.minLength} characters.`;
            if (field.validity.tooLong) return `Please enter no more than ${field.maxLength} characters.`;
            if (field.validity.rangeUnderflow) return `The value must be at least ${field.min}.`;
            if (field.validity.rangeOverflow) return `The value must be no more than ${field.max}.`;
            if (field.validity.stepMismatch) return 'Please enter a valid increment for this field.';
            if (field.validity.patternMismatch) return 'Please match the requested format.';
            return field.validationMessage || 'Please check this field.';
        }

        return '';
    }

    function feedbackAnchor(field) {
        return field.closest('.input-group, .auth-field, .customer-auth-field, .form-floating') || field;
    }

    function getFeedback(field) {
        const anchor = feedbackAnchor(field);
        const next = anchor.nextElementSibling;
        if (next && (next.classList.contains('invalid-feedback') || next.classList.contains('cc-invalid-feedback'))) {
            return next;
        }

        const container = anchor.parentElement;
        const existing = container?.querySelector(':scope > .invalid-feedback, :scope > .cc-invalid-feedback');
        if (existing) return existing;

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback cc-invalid-feedback';
        feedback.setAttribute('role', 'alert');
        anchor.insertAdjacentElement('afterend', feedback);
        return feedback;
    }

    function markInvalid(field, message) {
        field.classList.add('is-invalid', 'cc-field-invalid');
        field.setAttribute('aria-invalid', 'true');
        const feedback = getFeedback(field);
        feedback.textContent = message || 'Please check this field.';
        feedback.classList.add('d-block');
    }

    function clearInvalid(field) {
        field.classList.remove('is-invalid', 'cc-field-invalid');
        field.removeAttribute('aria-invalid');
        const anchor = feedbackAnchor(field);
        const next = anchor.nextElementSibling;
        if (next && (next.classList.contains('invalid-feedback') || next.classList.contains('cc-invalid-feedback'))) {
            next.textContent = '';
            next.classList.remove('d-block');
        }
    }

    function fieldsFor(form) {
        return Array.from(form.querySelectorAll(controlSelector)).filter(isVisibleControl);
    }

    function prepareForm(form) {
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method === 'get' || form.hasAttribute('data-skip-validation')) return;

        // All visible data-entry fields are required by default. Mark optional fields
        // explicitly with data-optional in Blade; checkboxes/radios remain opt-in.
        fieldsFor(form).forEach(field => {
            field.required = shouldBeRequired(field);
        });

        form.noValidate = true;
        form.addEventListener('submit', event => {
            if (!validate(form)) {
                event.preventDefault();
                event.stopPropagation();
            }
        }, true);

        form.addEventListener('change', () => {
            fieldsFor(form).forEach(field => {
                if (field.dataset.requiredWhen || field.dataset.requiredWhenFilled) {
                    field.required = shouldBeRequired(field);
                    if (!field.required && !field.dataset.requiredWhenFilled) clearInvalid(field);
                }
            });
        });
    }

    function validate(form) {
        if (!form) return true;

        let firstInvalid = null;
        let invalidCount = 0;
        const processedRadioGroups = new Set();
        const fields = fieldsFor(form);

        fields.forEach(field => {
            const type = (field.type || '').toLowerCase();
            field.required = shouldBeRequired(field);

            if (type === 'radio') {
                if (processedRadioGroups.has(field.name)) return;
                processedRadioGroups.add(field.name);
                const group = radioGroup(form, field.name);
                const groupRequired = group.some(item => item.required || item.dataset.required === 'true');
                const selected = group.some(item => item.checked);
                if (groupRequired && !selected) {
                    invalidCount += 1;
                    group.forEach(item => item.classList.add('is-invalid', 'cc-field-invalid'));
                    markInvalid(group[0], 'Please select an option.');
                    if (!firstInvalid) firstInvalid = group[0];
                } else {
                    group.forEach(clearInvalid);
                }
                return;
            }

            const message = validationMessage(field);
            if (message) {
                invalidCount += 1;
                markInvalid(field, message);
                if (!firstInvalid) firstInvalid = field;
            } else {
                clearInvalid(field);
            }
        });

        form.querySelector('.cc-validation-summary')?.remove();
        if (invalidCount > 0) {
            const summary = document.createElement('div');
            summary.className = 'cc-validation-summary';
            summary.setAttribute('role', 'alert');
            summary.setAttribute('aria-live', 'polite');
            summary.innerHTML = `<strong>Please complete the form.</strong> ${invalidCount} field${invalidCount === 1 ? '' : 's'} need your attention. The missing or invalid fields are outlined in red.`;
            form.prepend(summary);
            firstInvalid?.focus({ preventScroll: true });
            firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        return true;
    }

    function showErrors(form, errors) {
        if (!form || !errors) return;
        form.querySelector('.cc-validation-summary')?.remove();
        Object.entries(errors).forEach(([name, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;
            fieldsFor(form)
                .filter(field => field.name === name || field.name.replace(/\[(\d+)\]/g, '.$1') === name)
                .forEach(field => markInvalid(field, message));
        });
        if (form.querySelector('.is-invalid')) {
            const summary = document.createElement('div');
            summary.className = 'cc-validation-summary';
            summary.setAttribute('role', 'alert');
            summary.setAttribute('aria-live', 'polite');
            summary.textContent = 'Some information needs correction. Please review the fields outlined in red.';
            form.prepend(summary);
        }
    }

    function applyServerErrors() {
        if (!serverErrors || typeof serverErrors !== 'object') return;

        const affectedForms = new Map();
        Object.entries(serverErrors).forEach(([name, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;
            document.querySelectorAll('form').forEach(form => {
                const matchingFields = fieldsFor(form).filter(field =>
                    field.name === name || field.name.replace(/\[(\d+)\]/g, '.$1') === name
                );
                if (!matchingFields.length) return;

                matchingFields.forEach(field => markInvalid(field, message));
                affectedForms.set(form, (affectedForms.get(form) || 0) + matchingFields.length);
            });
        });

        affectedForms.forEach((count, form) => {
            form.querySelector('.cc-validation-summary')?.remove();
            const summary = document.createElement('div');
            summary.className = 'cc-validation-summary';
            summary.setAttribute('role', 'alert');
            summary.setAttribute('aria-live', 'polite');
            summary.innerHTML = `<strong>We could not save this form.</strong> ${count} field${count === 1 ? '' : 's'} need your attention. Review the messages below.`;
            form.prepend(summary);
        });
    }

    function clearCorrectedField(field) {
        if (!field?.matches?.(controlSelector)) return;
        const form = field.closest('form');
        if (!form) return;
        field.required = shouldBeRequired(field);

        if (field.type === 'radio') {
            const group = radioGroup(form, field.name);
            if (!validationMessage(field)) group.forEach(clearInvalid);
        } else if (!validationMessage(field)) {
            clearInvalid(field);
        }

        if (!form.querySelector('.is-invalid')) form.querySelector('.cc-validation-summary')?.remove();
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form').forEach(prepareForm);
        applyServerErrors();

        document.addEventListener('input', event => clearCorrectedField(event.target));
        document.addEventListener('change', event => clearCorrectedField(event.target));
    });

    window.CoffeeCornerFormValidator = { validate, showErrors, markInvalid };
})();
