/**
 * Health Records → Child Care → Operation Timbang monitoring summary.
 * Month/year session switching updates the summary label only (UI-phase).
 * Filters operate on displayed UI-phase preview rows only.
 * Export matches Vitamin A / Deworming UI-phase toast (no downloadable file).
 */

const MONTH_NAMES = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

function showOperationTimbangToast(root, message) {
    const toast = root.querySelector('[data-hr-ot-toast]');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.hidden = false;

    window.clearTimeout(showOperationTimbangToast._timer);
    showOperationTimbangToast._timer = window.setTimeout(() => {
        toast.hidden = true;
        toast.textContent = '';
    }, 3600);
}

function applyOperationTimbangFilters(root) {
    const tbody = root.querySelector('[data-hr-ot-tbody]');
    const empty = root.querySelector('[data-hr-ot-empty]');
    const results = root.querySelector('[data-hr-ot-results]');
    const tableScroll = root.querySelector(
        '.lml-hr-child-care__table-scroll--operation-timbang'
    );
    const searchInput = root.querySelector('[data-hr-ot-search]');
    const zoneSelect = root.querySelector('[data-hr-ot-zone]');
    const sexSelect = root.querySelector('[data-hr-ot-sex]');
    const statusSelect = root.querySelector('[data-hr-ot-status]');

    if (!tbody) {
        return;
    }

    const rows = Array.from(tbody.querySelectorAll('[data-hr-ot-row]'));
    const total = Number(root.dataset.total || rows.length);
    const query = (searchInput?.value || '').trim().toLowerCase();
    const zone = zoneSelect?.value || 'all';
    const sex = sexSelect?.value || 'all';
    const status = statusSelect?.value || 'all';

    let visible = 0;

    rows.forEach((row) => {
        const name = row.dataset.name || '';
        const rowZone = row.dataset.zone || '';
        const rowSex = row.dataset.sex || '';
        const rowStatus = row.dataset.status || '';

        const matchesSearch = !query || name.includes(query);
        const matchesZone = zone === 'all' || rowZone === zone;
        const matchesSex = sex === 'all' || rowSex === sex;
        const matchesStatus = status === 'all' || rowStatus === status;
        const show = matchesSearch && matchesZone && matchesSex && matchesStatus;

        row.hidden = !show;
        if (show) {
            visible += 1;
        }
    });

    if (results) {
        results.textContent = `Showing ${visible} of ${total} children`;
    }

    if (empty) {
        empty.hidden = visible > 0 || rows.length === 0;
    }

    if (tableScroll) {
        tableScroll.hidden = rows.length > 0 && visible === 0;
    }
}

function monthLabel(month, year) {
    const name = MONTH_NAMES[Math.max(0, Math.min(11, Number(month) - 1))] || 'January';
    return `${name} ${year}`;
}

function rebuildMonthPills(root, year) {
    const list = root.querySelector('[data-hr-ot-month-list]');
    if (!list) {
        return;
    }

    const selectedMonth = Number(root.dataset.selectedMonth || 1);
    list.replaceChildren();

    for (let month = 1; month <= 12; month += 1) {
        const key = `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}`;
        const label = monthLabel(month, year);
        const isSelected = month === selectedMonth;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = `lml-hr-ot-month-pill lml-focus-ring${isSelected ? ' lml-hr-ot-month-pill--active' : ''}`;
        button.setAttribute('role', 'tab');
        button.id = `lml-hr-ot-month-${key}`;
        button.dataset.hrOtMonth = '';
        button.dataset.month = String(month);
        button.dataset.year = String(year);
        button.dataset.key = key;
        button.dataset.label = label;
        button.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        if (isSelected) {
            button.setAttribute('aria-current', 'true');
        }

        const text = document.createElement('span');
        text.className = 'lml-hr-ot-month-pill__text';
        text.textContent = label;
        button.appendChild(text);

        if (isSelected) {
            const sr = document.createElement('span');
            sr.className = 'visually-hidden';
            sr.textContent = '(selected session)';
            button.appendChild(sr);
        }

        list.appendChild(button);
    }
}

function selectMonthSession(root, button) {
    const month = Number(button.dataset.month || 1);
    const year = Number(button.dataset.year || root.dataset.selectedYear || 2026);
    const label = button.dataset.label || monthLabel(month, year);

    root.dataset.selectedMonth = String(month);
    root.dataset.selectedYear = String(year);

    root.querySelectorAll('[data-hr-ot-month]').forEach((pill) => {
        const active = pill === button;
        pill.classList.toggle('lml-hr-ot-month-pill--active', active);
        pill.setAttribute('aria-selected', active ? 'true' : 'false');
        if (active) {
            pill.setAttribute('aria-current', 'true');
            if (!pill.querySelector('.visually-hidden')) {
                const sr = document.createElement('span');
                sr.className = 'visually-hidden';
                sr.textContent = '(selected session)';
                pill.appendChild(sr);
            }
        } else {
            pill.removeAttribute('aria-current');
            pill.querySelectorAll('.visually-hidden').forEach((node) => node.remove());
        }
    });

    const summaryLabel = root.querySelector('[data-hr-ot-summary-label]');
    if (summaryLabel) {
        summaryLabel.textContent = label;
    }
}

function initHealthRecordsOperationTimbang(root) {
    const exportBtn = root.querySelector('[data-hr-ot-export]');
    const searchInput = root.querySelector('[data-hr-ot-search]');
    const zoneSelect = root.querySelector('[data-hr-ot-zone]');
    const sexSelect = root.querySelector('[data-hr-ot-sex]');
    const statusSelect = root.querySelector('[data-hr-ot-status]');
    const yearSelect = root.querySelector('[data-hr-ot-year]');
    const monthList = root.querySelector('[data-hr-ot-month-list]');

    const refresh = () => applyOperationTimbangFilters(root);

    exportBtn?.addEventListener('click', () => {
        showOperationTimbangToast(root, 'Export is not available during the UI phase.');
    });

    searchInput?.addEventListener('input', refresh);
    zoneSelect?.addEventListener('change', refresh);
    sexSelect?.addEventListener('change', refresh);
    statusSelect?.addEventListener('change', refresh);

    monthList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-hr-ot-month]');
        if (!button || !monthList.contains(button)) {
            return;
        }
        selectMonthSession(root, button);
    });

    yearSelect?.addEventListener('change', () => {
        const year = Number(yearSelect.value || 2026);
        root.dataset.selectedYear = String(year);
        rebuildMonthPills(root, year);

        const active = root.querySelector('[data-hr-ot-month].lml-hr-ot-month-pill--active');
        if (active) {
            selectMonthSession(root, active);
        }
    });

    refresh();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-hr-operation-timbang]').forEach((root) => {
        initHealthRecordsOperationTimbang(root);
    });
});
