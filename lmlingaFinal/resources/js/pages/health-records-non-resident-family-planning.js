/**
 * Health Records → Family Planning → Non-Resident clients (UI preview).
 */

const PREVIEW_SAVE_MESSAGE =
    'Preview only: Non-resident family planning data was not permanently saved. Backend persistence is not yet implemented.';

const PREVIEW_DELETE_MESSAGE =
    'Preview only: Delete Visit is not available until backend support is implemented. No visit was removed.';

const PREVIEW_EXPORT_MESSAGE =
    'Preview only: Export will be available when non-resident family planning data export is implemented.';

function showNrToast(root, message) {
    const toast = root.querySelector('[data-hr-fp-nr-toast]');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.hidden = false;

    window.clearTimeout(showNrToast._timer);
    showNrToast._timer = window.setTimeout(() => {
        toast.hidden = true;
        toast.textContent = '';
    }, 4200);
}

function applyNrListingFilters(root) {
    const tbody = root.querySelector('[data-hr-fp-nr-tbody]');
    const empty = root.querySelector('[data-hr-fp-nr-empty]');
    const results = root.querySelector('[data-hr-fp-nr-results]');
    const tableScroll = root.querySelector('.lml-hr-fp-nr__table-scroll');
    const searchInput = root.querySelector('[data-hr-fp-nr-search]');
    const barangaySelect = root.querySelector('[data-hr-fp-nr-barangay]');
    const yearSelect = root.querySelector('[data-hr-fp-nr-year]');

    if (!tbody) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('[data-hr-fp-nr-row]'));
    const total = Number(root.dataset.total || rows.length);
    const query = (searchInput?.value || '').trim().toLowerCase();
    const barangay = barangaySelect?.value || 'all';
    const year = yearSelect?.value || 'all';

    let visible = 0;

    rows.forEach((row) => {
        const name = row.dataset.name || '';
        const rowBarangay = row.dataset.barangay || '';
        const rowYear = row.dataset.year || '';

        const matchesSearch = !query || name.includes(query);
        const matchesBarangay = barangay === 'all' || rowBarangay === barangay;
        const matchesYear = year === 'all' || rowYear === year;
        const show = matchesSearch && matchesBarangay && matchesYear;

        row.hidden = !show;
        if (show) {
            visible += 1;
        }
    });

    if (results) {
        results.textContent = `Showing ${visible} of ${total} non-resident clients`;
    }

    if (empty) {
        empty.hidden = visible > 0 || rows.length === 0;
    }

    if (tableScroll) {
        tableScroll.hidden = rows.length > 0 && visible === 0;
    }
}

function reindexNrCommodityRows(list, idPrefix) {
    const rows = Array.from(list.querySelectorAll('[data-hr-fp-nr-commodity-row]'));
    rows.forEach((row, index) => {
        const name = row.querySelector('[data-hr-fp-nr-commodity-name]');
        const qty = row.querySelector('[data-hr-fp-nr-commodity-qty]');
        const nameLabel = row.querySelector('.lml-hr-fp-nr__field label');
        const qtyLabel = row.querySelector('.lml-hr-fp-nr__field--qty label');
        const prefix = idPrefix || 'lml-hr-fp-nr';
        const nameId = `${prefix}-commodity-${index}`;
        const qtyId = `${prefix}-qty-${index}`;

        if (name instanceof HTMLSelectElement) {
            name.id = nameId;
            name.name = `commodities[${index}][name]`;
        }
        if (qty instanceof HTMLInputElement) {
            qty.id = qtyId;
            qty.name = `commodities[${index}][quantity]`;
        }
        if (nameLabel instanceof HTMLLabelElement) {
            nameLabel.setAttribute('for', nameId);
        }
        if (qtyLabel instanceof HTMLLabelElement) {
            qtyLabel.setAttribute('for', qtyId);
        }
    });

    rows.forEach((row) => {
        const removeBtn = row.querySelector('[data-hr-fp-nr-commodity-remove]');
        if (removeBtn instanceof HTMLElement) {
            removeBtn.hidden = rows.length <= 1;
        }
    });
}

function initNrCommodities(root) {
    const list = root.querySelector('[data-hr-fp-nr-commodity-list]');
    const template = root.querySelector('[data-hr-fp-nr-commodity-template]');
    const addBtn = root.querySelector('[data-hr-fp-nr-commodity-add]');
    if (!(list instanceof HTMLElement)) {
        return;
    }

    const idPrefix =
        root.querySelector('[id^="lml-hr-fp-nr-create-commodity-"]') != null
            ? 'lml-hr-fp-nr-create'
            : root.querySelector('[id^="lml-hr-fp-nr-edit-commodity-"]') != null
              ? 'lml-hr-fp-nr-edit'
              : root.querySelector('[id^="lml-hr-fp-nr-add-commodity-"]') != null
                ? 'lml-hr-fp-nr-add'
                : 'lml-hr-fp-nr';

    addBtn?.addEventListener('click', () => {
        if (!(template instanceof HTMLTemplateElement)) {
            return;
        }
        list.appendChild(template.content.cloneNode(true));
        reindexNrCommodityRows(list, idPrefix);
        const rows = list.querySelectorAll('[data-hr-fp-nr-commodity-row]');
        const last = rows[rows.length - 1];
        const focusTarget = last?.querySelector('[data-hr-fp-nr-commodity-name]');
        if (focusTarget instanceof HTMLElement) {
            focusTarget.focus();
        }
    });

    list.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('[data-hr-fp-nr-commodity-remove]');
        if (!(removeBtn instanceof HTMLElement) || !list.contains(removeBtn)) {
            return;
        }
        const row = removeBtn.closest('[data-hr-fp-nr-commodity-row]');
        const rows = list.querySelectorAll('[data-hr-fp-nr-commodity-row]');
        if (!(row instanceof HTMLElement) || rows.length <= 1) {
            return;
        }
        row.remove();
        reindexNrCommodityRows(list, idPrefix);
    });

    reindexNrCommodityRows(list, idPrefix);
}

function initNrListing(root) {
    const exportBtn = root.querySelector('[data-hr-fp-nr-export]');
    const searchInput = root.querySelector('[data-hr-fp-nr-search]');
    const barangaySelect = root.querySelector('[data-hr-fp-nr-barangay]');
    const yearSelect = root.querySelector('[data-hr-fp-nr-year]');

    const refresh = () => applyNrListingFilters(root);

    exportBtn?.addEventListener('click', () => {
        showNrToast(root, PREVIEW_EXPORT_MESSAGE);
    });

    searchInput?.addEventListener('input', refresh);
    barangaySelect?.addEventListener('change', refresh);
    yearSelect?.addEventListener('change', refresh);

    refresh();
}

function initNrForms(root) {
    const createForm = root.querySelector('[data-hr-fp-nr-create-form]');
    const visitForm = root.querySelector('[data-hr-fp-nr-visit-form]');
    const deleteBtn = root.querySelector('[data-hr-fp-nr-delete-visit]');

    createForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        showNrToast(root, PREVIEW_SAVE_MESSAGE);
    });

    visitForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        showNrToast(root, PREVIEW_SAVE_MESSAGE);
    });

    deleteBtn?.addEventListener('click', () => {
        showNrToast(root, PREVIEW_DELETE_MESSAGE);
    });

    initNrCommodities(root);
}

function initNonResidentFamilyPlanning(root) {
    const mode = root.getAttribute('data-lml-hr-fp-nr-mode');

    if (mode === 'listing') {
        initNrListing(root);
        return;
    }

    if (mode === 'create-client' || mode === 'add-visit' || mode === 'edit-visit') {
        initNrForms(root);
    }
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-lml-hr-fp-nr]').forEach((root) => {
            initNonResidentFamilyPlanning(root);
        });
    });
}

export {
    applyNrListingFilters,
    initNonResidentFamilyPlanning,
    PREVIEW_DELETE_MESSAGE,
    PREVIEW_EXPORT_MESSAGE,
    PREVIEW_SAVE_MESSAGE,
};
