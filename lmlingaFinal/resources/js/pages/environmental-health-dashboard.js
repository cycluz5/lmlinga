/**
 * Environmental Health Dashboard — live filters, stats, export menu.
 */

function readFilters(root) {
    const form = root.querySelector('[data-eh-filters]');
    if (!form) {
        return {};
    }

    const data = new FormData(form);
    return {
        household_no: String(data.get('household_no') || '').trim().toLowerCase(),
        zone: String(data.get('zone') || 'all'),
        street: String(data.get('street') || 'all'),
    };
}

function rowMatches(row, filters) {
    const hh = (row.dataset.householdNo || '').toLowerCase();

    if (filters.household_no && !hh.includes(filters.household_no)) {
        return false;
    }
    if (filters.zone !== 'all' && (row.dataset.zone || '') !== filters.zone) {
        return false;
    }
    if (filters.street !== 'all' && (row.dataset.street || '') !== filters.street) {
        return false;
    }

    return true;
}

function setStat(root, key, value) {
    const el = root.querySelector(`[data-stat="${key}"]`);
    if (el) {
        el.textContent = String(value);
    }
}

function recalculateStats(root, visibleRows) {
    const water = { level_i: 0, level_ii: 0, level_iii: 0, others: 0 };
    const sanitation = { sanitary: 0, unsanitary: 0, pending: 0 };
    const presence = { with_toilet: 0, without_toilet: 0, unknown: 0 };
    let completed = 0;
    let pending = 0;
    let validated = 0;
    let waste = 0;

    visibleRows.forEach((row) => {
        const level = row.dataset.waterSupply || '';
        if (Object.prototype.hasOwnProperty.call(water, level)) {
            water[level] += 1;
        }

        const toilet = row.dataset.toiletStatus || 'not_yet_determined';
        if (toilet === 'sanitary') {
            sanitation.sanitary += 1;
        } else if (toilet === 'unsanitary') {
            sanitation.unsanitary += 1;
        } else {
            sanitation.pending += 1;
        }

        const toiletPresence = row.dataset.toiletPresence || 'unknown';
        if (Object.prototype.hasOwnProperty.call(presence, toiletPresence)) {
            presence[toiletPresence] += 1;
        } else {
            presence.unknown += 1;
        }

        if ((row.dataset.recordStatus || '') === 'completed') {
            completed += 1;
        } else {
            pending += 1;
        }

        if ((row.dataset.solidWaste || '') === 'good_practice') {
            waste += 1;
        }
    });

    setStat(root, 'water-level_i', water.level_i);
    setStat(root, 'water-level_ii', water.level_ii);
    setStat(root, 'water-level_iii', water.level_iii);
    setStat(root, 'water-others', water.others);
    setStat(root, 'sanitation-with', presence.with_toilet);
    setStat(root, 'sanitation-without', presence.without_toilet);
    setStat(root, 'sanitation-sanitary', sanitation.sanitary);
    setStat(root, 'sanitation-unsanitary', sanitation.unsanitary);
    setStat(root, 'sanitation-pending', sanitation.pending);
    setStat(root, 'overview-total', visibleRows.length);
    setStat(root, 'overview-completed', completed);
    setStat(root, 'overview-pending', pending);
    setStat(root, 'overview-validated', validated);
    setStat(root, 'overview-waste', waste);
}

function syncExportLinks(root, filters) {
    const base = root.dataset.exportBase || '';
    if (!base) {
        return;
    }

    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value && value !== 'all' && value !== '') {
            params.set(key, value);
        }
    });

    root.querySelectorAll('[data-eh-export]').forEach((link) => {
        const format = link.getAttribute('data-eh-export') || 'csv';
        const next = new URLSearchParams(params);
        next.set('format', format);
        link.setAttribute('href', `${base}?${next.toString()}`);
    });
}

function applyFilters(root) {
    const tbody = root.querySelector('[data-eh-tbody]');
    const empty = root.querySelector('[data-eh-empty]');
    const results = root.querySelector('[data-eh-results]');
    const tableScroll = root.querySelector('.lml-eh-dashboard__table-scroll');
    const total = Number(root.dataset.total || 0);

    if (!tbody) {
        return;
    }

    const filters = readFilters(root);
    const rows = Array.from(tbody.querySelectorAll('[data-eh-row]'));
    const visible = [];

    rows.forEach((row) => {
        const show = rowMatches(row, filters);
        row.hidden = !show;
        if (show) {
            visible.push(row);
        }
    });

    recalculateStats(root, visible);
    syncExportLinks(root, filters);

    if (results) {
        results.textContent = `Showing ${visible.length} of ${total} household amenities records`;
    }

    if (empty) {
        empty.hidden = visible.length > 0;
    }

    if (tableScroll) {
        tableScroll.hidden = visible.length === 0;
    }

    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value && value !== 'all' && value !== '') {
            params.set(key, value);
        }
    });
    const query = params.toString();
    const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname;
    window.history.replaceState({}, '', nextUrl);
}

function closeExportMenu(root) {
    const toggle = root.querySelector('[data-eh-export-toggle]');
    const menu = root.querySelector('#lml-eh-export-menu');
    if (menu) {
        menu.hidden = true;
    }
    if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
    }
}

function initDashboard(root) {
    const form = root.querySelector('[data-eh-filters]');
    let debounceTimer = null;

    const run = () => applyFilters(root);

    form?.querySelectorAll('select[data-eh-filter]').forEach((el) => {
        el.addEventListener('change', run);
    });

    form?.querySelectorAll('input[data-eh-filter]').forEach((el) => {
        el.addEventListener('input', () => {
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(run, 160);
        });
    });

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        run();
    });

    const toggle = root.querySelector('[data-eh-export-toggle]');
    const menu = root.querySelector('#lml-eh-export-menu');

    toggle?.addEventListener('click', () => {
        if (!menu) {
            return;
        }
        const open = menu.hidden;
        menu.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    menu?.querySelectorAll('[data-eh-export]').forEach((link) => {
        link.addEventListener('click', () => closeExportMenu(root));
    });

    document.addEventListener('click', (event) => {
        const wrap = root.querySelector('[data-eh-export-menu]');
        if (wrap && !wrap.contains(event.target)) {
            closeExportMenu(root);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeExportMenu(root);
        }
    });

    run();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-eh-dashboard]').forEach((root) => {
        initDashboard(root);
    });
});
