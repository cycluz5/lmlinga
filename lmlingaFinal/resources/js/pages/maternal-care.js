/**
 * Household Profiling — Maternal Care Phase 1 (UI preview).
 *
 * - Accordion expand/collapse with aria-expanded / aria-controls
 * - Edit / Save UI mode toggling (server session save on submit)
 * - Delivery outcome + birth attendant conditional fields
 * - Register form BMI / EDD helpers
 */

const PREVIEW_SAVE_HINT =
    'Preview only: Maternal Care changes are kept in this browser session and are not permanently saved.';

function initMaternalCare(root) {
    initAccordions(root);
    initEditSave(root);
    initRegisterHelpers(root);
    initDeliveryConditionals(root);
}

function initAccordions(root) {
    root.querySelectorAll('[data-mc-accordion]').forEach((accordion) => {
        const trigger = accordion.querySelector('[data-mc-accordion-trigger]');
        const panel = accordion.querySelector('[data-mc-accordion-panel]');
        if (!trigger || !panel) {
            return;
        }

        // Laboratory cards start expanded in markup (no hidden attr).
        const startsOpen = !panel.hasAttribute('hidden');
        setAccordionOpen(trigger, panel, startsOpen);

        trigger.addEventListener('click', () => {
            const open = trigger.getAttribute('aria-expanded') === 'true';
            setAccordionOpen(trigger, panel, !open);
        });
    });
}

function setAccordionOpen(trigger, panel, open) {
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
        panel.removeAttribute('hidden');
        panel.querySelectorAll('[data-mc-field]').forEach((el) => {
            // Keep disabled state managed by edit mode; only restore tabindex when open.
            if (el.hasAttribute('data-mc-inert-when-collapsed')) {
                el.removeAttribute('tabindex');
            }
        });
    } else {
        panel.setAttribute('hidden', '');
        // Prevent keyboard focus into collapsed content.
        panel.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        ).forEach((el) => {
            if (el === trigger) {
                return;
            }
            // Collapsed + hidden is enough for browsers that honor [hidden];
            // also disable focusables that are currently enabled (edit mode).
            if (!el.disabled && el.matches('[data-mc-field]')) {
                el.setAttribute('tabindex', '-1');
                el.setAttribute('data-mc-inert-when-collapsed', 'true');
            }
        });
    }
}

function initEditSave(root) {
    root.querySelectorAll('[data-mc-section-form]').forEach((form) => {
        const section = form.getAttribute('data-mc-section-form');
        if (!section || section === 'trans-out') {
            // Trans-out is always editable.
            setFormEditing(form, true);
            return;
        }

        const editBtn = root.querySelector(`[data-mc-edit-for="${section}"]`);
        const saveBtn = root.querySelector(`[data-mc-save-for="${section}"]`);

        setFormEditing(form, false);

        editBtn?.addEventListener('click', () => {
            setFormEditing(form, true);
            if (editBtn) {
                editBtn.hidden = true;
            }
            if (saveBtn) {
                saveBtn.hidden = false;
            }
            syncDeliveryConditionals(root);
        });
    });
}

function setFormEditing(form, editing) {
    form.setAttribute('data-editing', editing ? 'true' : 'false');
    form.querySelectorAll('[data-mc-field]').forEach((el) => {
        // Conditional fields may remain disabled even in edit mode.
        if (el.closest('[data-mc-conditional][hidden]')) {
            el.disabled = true;
            return;
        }
        if (el.closest('[data-mc-delivery-details-fields], [data-mc-place-fields]')) {
            const terminal = isTerminalOutcome(form);
            el.disabled = !editing || terminal;
            return;
        }
        el.disabled = !editing;
    });
}

function isTerminalOutcome(form) {
    const selected = form.querySelector('[data-mc-outcome]:checked');
    const value = selected?.value || '';
    return value === 'FD' || value === 'AB';
}

function initRegisterHelpers(root) {
    const form = root.querySelector('[data-mc-register-form]');
    if (!form) {
        return;
    }

    const lmp = form.querySelector('[data-mc-lmp]');
    const edd = form.querySelector('[data-mc-edd]');
    const weight = form.querySelector('[data-mc-weight]');
    const height = form.querySelector('[data-mc-height]');
    const bmi = form.querySelector('[data-mc-bmi]');

    const updateEdd = () => {
        if (!lmp || !edd || !lmp.value || edd.value) {
            return;
        }
        const date = new Date(`${lmp.value}T00:00:00`);
        if (Number.isNaN(date.getTime())) {
            return;
        }
        date.setDate(date.getDate() + 280);
        edd.value = formatYmd(date);
    };

    const updateBmi = () => {
        if (!weight || !height || !bmi) {
            return;
        }
        const w = Number.parseFloat(weight.value);
        const hCm = Number.parseFloat(height.value);
        if (!Number.isFinite(w) || !Number.isFinite(hCm) || w <= 0 || hCm <= 0) {
            bmi.value = '';
            return;
        }
        const hM = hCm / 100;
        bmi.value = (w / (hM * hM)).toFixed(1);
    };

    lmp?.addEventListener('change', updateEdd);
    weight?.addEventListener('input', updateBmi);
    height?.addEventListener('input', updateBmi);
}

function initDeliveryConditionals(root) {
    const form = root.querySelector('[data-mc-section-form="delivery"]');
    if (!form) {
        return;
    }

    form.querySelectorAll('[data-mc-outcome]').forEach((input) => {
        input.addEventListener('change', () => {
            syncDeliveryConditionals(root);
            form.querySelectorAll('.lml-mc__radio').forEach((label) => {
                const radio = label.querySelector('[data-mc-outcome]');
                label.classList.toggle('is-selected', Boolean(radio?.checked));
            });
        });
    });

    const attendant = form.querySelector('[data-mc-birth-attendant]');
    attendant?.addEventListener('change', () => {
        syncDeliveryConditionals(root);
    });

    syncDeliveryConditionals(root);
}

function syncDeliveryConditionals(root) {
    const form = root.querySelector('[data-mc-section-form="delivery"]');
    if (!form) {
        return;
    }

    const editing = form.getAttribute('data-editing') === 'true';
    const selected = form.querySelector('[data-mc-outcome]:checked');
    const outcome = selected?.value || '';
    const terminal = outcome === 'FD' || outcome === 'AB';

    const fdBlock = form.querySelector('[data-mc-conditional="fd"]');
    const abBlock = form.querySelector('[data-mc-conditional="ab"]');
    setConditional(fdBlock, outcome === 'FD', editing);
    setConditional(abBlock, outcome === 'AB', editing);

    const details = form.querySelector('[data-mc-delivery-details]');
    const place = form.querySelector('[data-mc-place-of-delivery]');
    if (details) {
        details.toggleAttribute('aria-disabled', terminal);
    }
    if (place) {
        place.toggleAttribute('aria-disabled', terminal);
    }

    form.querySelectorAll('[data-mc-delivery-details-fields] [data-mc-field], [data-mc-place-fields] [data-mc-field]').forEach(
        (el) => {
            el.disabled = !editing || terminal;
        }
    );

    const attendant = form.querySelector('[data-mc-birth-attendant]');
    const otherBlock = form.querySelector('[data-mc-conditional="attendant-other"]');
    const showOther = !terminal && attendant?.value === 'Others';
    setConditional(otherBlock, showOther, editing && !terminal);

    if (!showOther) {
        const otherInput = form.querySelector('[data-mc-birth-attendant-other]');
        if (otherInput && !editing) {
            // keep stored value for read mode when previously Others
        } else if (otherInput && attendant?.value !== 'Others') {
            otherInput.value = '';
        }
    }
}

function setConditional(block, visible, editing) {
    if (!block) {
        return;
    }
    if (visible) {
        block.removeAttribute('hidden');
    } else {
        block.setAttribute('hidden', '');
    }
    block.querySelectorAll('[data-mc-field]').forEach((el) => {
        el.disabled = !visible || !editing;
        if (!visible && el.matches('[data-mc-birth-attendant-other]')) {
            // Clear stale custom attendant when leaving Others.
            el.value = '';
        }
    });
}

function formatYmd(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-mc]').forEach((root) => {
        initMaternalCare(root);
    });
});

export { initMaternalCare, PREVIEW_SAVE_HINT };
