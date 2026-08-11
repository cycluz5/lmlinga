/**
 * Health Records → Child Care summary — client-side filters (demo catalog).
 */

function showToast(root, message) {
    const toast = root.querySelector('[data-hr-cc-toast]');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.hidden = false;

    window.clearTimeout(showToast._timer);
    showToast._timer = window.setTimeout(() => {
        toast.hidden = true;
        toast.textContent = '';
    }, 3600);
}

function matchesAgeBand(ageMonths, band) {
    const months = Number(ageMonths);
    if (Number.isNaN(months)) {
        return true;
    }

    switch (band) {
        case '0-5':
            return months >= 0 && months <= 5;
        case '6-11':
            return months >= 6 && months <= 11;
        case '12-23':
            return months >= 12 && months <= 23;
        case '24-59':
            return months >= 24 && months <= 59;
        default:
            return true;
    }
}

function applyFilters(root) {
    const tbody = root.querySelector('[data-hr-cc-tbody]');
    const empty = root.querySelector('[data-hr-cc-empty]');
    const results = root.querySelector('[data-hr-cc-results]');
    const tableScroll = root.querySelector('.lml-hr-child-care__table-scroll');
    const searchInput = root.querySelector('[data-hr-cc-search]');
    const zoneSelect = root.querySelector('[data-hr-cc-zone]');
    const ageSelect = root.querySelector('[data-hr-cc-age]');
    const sexSelect = root.querySelector('[data-hr-cc-sex]');

    if (!tbody) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('[data-hr-cc-row]'));
    const total = Number(root.dataset.total || rows.length);
    const query = (searchInput?.value || '').trim().toLowerCase();
    const zone = zoneSelect?.value || 'all';
    const ageBand = ageSelect?.value || 'all';
    const sex = sexSelect?.value || 'all';

    let visible = 0;

    rows.forEach((row) => {
        const name = row.dataset.name || '';
        const rowZone = row.dataset.zone || '';
        const rowSex = row.dataset.sex || '';
        const ageMonths = row.dataset.ageMonths || '';

        const matchesSearch = !query || name.includes(query);
        const matchesZone = zone === 'all' || rowZone === zone;
        const matchesSex = sex === 'all' || rowSex === sex;
        const matchesAge = matchesAgeBand(ageMonths, ageBand);
        const show = matchesSearch && matchesZone && matchesSex && matchesAge;

        row.hidden = !show;
        if (show) {
            visible += 1;
        }
    });

    if (results) {
        results.textContent = `Showing ${visible} of ${total} infants`;
    }

    if (empty) {
        empty.hidden = visible > 0 || rows.length === 0;
    }

    if (tableScroll) {
        tableScroll.hidden = rows.length > 0 && visible === 0;
    }
}

function initHealthRecordsChildCare(root) {
    const exportBtn = root.querySelector('[data-hr-cc-export]');
    const addBtn = root.querySelector('[data-hr-cc-add]');
    const searchInput = root.querySelector('[data-hr-cc-search]');
    const zoneSelect = root.querySelector('[data-hr-cc-zone]');
    const ageSelect = root.querySelector('[data-hr-cc-age]');
    const sexSelect = root.querySelector('[data-hr-cc-sex]');

    const refresh = () => applyFilters(root);

    addBtn?.addEventListener('click', () => {
        showToast(
            root,
            'Add requires a household context. Use Household Profiling → Add Member (no barangay-level create route).'
        );
    });

    exportBtn?.addEventListener('click', () => {
        showToast(root, 'Export is not available during the UI phase.');
    });

    searchInput?.addEventListener('input', refresh);
    zoneSelect?.addEventListener('change', refresh);
    ageSelect?.addEventListener('change', refresh);
    sexSelect?.addEventListener('change', refresh);

    refresh();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-hr-child-care]').forEach((root) => {
        initHealthRecordsChildCare(root);
    });
});
