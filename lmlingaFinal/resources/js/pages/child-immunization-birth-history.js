/**
 * Birth History dedicated edit page — preview-safe Save (no server write).
 *
 * Saves optional field values to sessionStorage for the current browser session,
 * then returns to Child Immunization. Close is a named-route link (no save).
 */

const STORAGE_PREFIX = 'lml.birthHistory.preview.';
const PREVIEW_SAVE_MESSAGE =
    'Preview only: Birth History changes were not permanently saved.';

function storageKey(householdNo, memberId) {
    return `${STORAGE_PREFIX}${householdNo}.${memberId}`;
}

function readPreview(householdNo, memberId) {
    try {
        const raw = window.sessionStorage.getItem(storageKey(householdNo, memberId));
        if (!raw) {
            return null;
        }
        const parsed = JSON.parse(raw);
        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
        return null;
    }
}

function writePreview(householdNo, memberId, values, { announce = false } = {}) {
    try {
        window.sessionStorage.setItem(
            storageKey(householdNo, memberId),
            JSON.stringify({
                weight: String(values.weight ?? ''),
                length: String(values.length ?? ''),
                pcab: String(values.pcab ?? ''),
                breastfeeding_date: String(values.breastfeeding_date ?? ''),
                announce: Boolean(announce),
                message: PREVIEW_SAVE_MESSAGE,
            })
        );
    } catch {
        // sessionStorage may be unavailable; navigation still proceeds.
    }
}

function snapshotBirthForm(form) {
    /** @type {Record<string, string>} */
    const values = {};
    form.querySelectorAll('[data-child-imm-birth-field]').forEach((field) => {
        const key = field.getAttribute('data-child-imm-birth-field');
        if (!key) {
            return;
        }
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) {
            values[key] = String(field.value ?? '');
        }
    });
    return values;
}

function applyPreviewToForm(form, preview) {
    if (!preview) {
        return;
    }

    form.querySelectorAll('[data-child-imm-birth-field]').forEach((field) => {
        const key = field.getAttribute('data-child-imm-birth-field');
        if (!key || !(key in preview)) {
            return;
        }
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement) {
            field.value = String(preview[key] ?? '');
        }
    });
}

function initBirthHistoryEdit(root) {
    if (!(root instanceof HTMLElement) || root.dataset.bhEditReady === 'true') {
        return;
    }
    root.dataset.bhEditReady = 'true';

    const householdNo = root.getAttribute('data-household-no') || '';
    const memberId = root.getAttribute('data-member-id') || '';
    const returnUrl = root.getAttribute('data-return-url') || '';
    const form = root.querySelector('[data-child-imm-birth-form]');

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    applyPreviewToForm(form, readPreview(householdNo, memberId));

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const values = snapshotBirthForm(form);
        writePreview(householdNo, memberId, values, { announce: true });

        if (returnUrl) {
            window.location.assign(returnUrl);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-bh-edit]').forEach(initBirthHistoryEdit);
});
