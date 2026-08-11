/**
 * Household Profiling — Risk Assessment (UI preview).
 *
 * - History date filter (GET form / client auto-submit)
 * - Five-step wizard with preserved field state
 * - "None" exclusive checkbox groups
 * - History section edit exclusive groups
 * - Preview-only Save for create wizard (no server persistence)
 * - History section Save posts to server (session-backed update)
 */

const PREVIEW_SAVE_MESSAGE =
    'Preview only: Risk Assessment was kept in this browser session and was not permanently saved.';

const STORAGE_PREFIX = 'lml.riskAssessment.preview.';

export function applyNoneExclusiveLogic(groupEl, changedInput) {
    if (!groupEl || typeof groupEl.querySelectorAll !== 'function') {
        return;
    }

    if (!changedInput || changedInput.type !== 'checkbox') {
        return;
    }

    const noneKey = groupEl.getAttribute?.('data-none-key') || 'none';
    const checkboxes = Array.from(
        groupEl.querySelectorAll('input[type="checkbox"]')
    ).filter((el) => el && el.type === 'checkbox' && !el.disabled);

    const noneInputs = checkboxes.filter(
        (el) => el.value === noneKey || (typeof el.hasAttribute === 'function' && el.hasAttribute('data-risk-assess-none'))
    );
    const otherInputs = checkboxes.filter((el) => !noneInputs.includes(el));
    const isNone = noneInputs.includes(changedInput);

    if (isNone && changedInput.checked) {
        otherInputs.forEach((el) => {
            el.checked = false;
        });
        return;
    }

    if (!isNone && changedInput.checked) {
        noneInputs.forEach((el) => {
            el.checked = false;
        });
    }
}

/**
 * Pure Date Conducted filter for Risk Assessment History.
 * Mirrors App\Support\DemoRiskAssessment::filterByDate (inclusive bounds).
 *
 * @param {Array<{conducted_at?: string, conductedAt?: string}>} rows
 * @param {string|null|undefined} filterValue
 * @param {{ from?: string|null, to?: string|null, today?: string|Date|null }} [options]
 */
export function filterHistoryByDate(rows, filterValue, options = {}) {
    const list = Array.isArray(rows) ? rows : [];
    const filter = typeof filterValue === 'string' ? filterValue.trim() : '';
    if (!filter || filter === 'all') {
        return list.slice();
    }

    const today = resolveToday(options?.today);
    const conductedOf = (row) =>
        String(row?.conductedAt || row?.conducted_at || '').trim();

    if (filter === 'this_month') {
        const start = `${today.getFullYear()}-${pad2(today.getMonth() + 1)}-01`;
        const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        const end = formatYmd(endDate);
        return list.filter((row) => inInclusiveRange(conductedOf(row), start, end));
    }

    if (filter === 'last_3_months') {
        const start = formatYmd(subMonthsNoOverflow(today, 3));
        const end = formatYmd(today);
        return list.filter((row) => inInclusiveRange(conductedOf(row), start, end));
    }

    if (filter === 'this_year') {
        const start = `${today.getFullYear()}-01-01`;
        const end = `${today.getFullYear()}-12-31`;
        return list.filter((row) => inInclusiveRange(conductedOf(row), start, end));
    }

    if (filter === 'custom') {
        const from = typeof options?.from === 'string' ? options.from.trim() : '';
        const to = typeof options?.to === 'string' ? options.to.trim() : '';
        if (!from || !to || from > to) {
            return list.slice();
        }
        return list.filter((row) => inInclusiveRange(conductedOf(row), from, to));
    }

    return list.slice();
}

function pad2(n) {
    return String(n).padStart(2, '0');
}

function formatYmd(date) {
    return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;
}

/** Match Carbon::subMonthsNoOverflow for stable PHP/JS filter bounds. */
function subMonthsNoOverflow(date, months) {
    const year = date.getFullYear();
    const monthIndex = date.getMonth() - months;
    const day = date.getDate();
    const anchor = new Date(year, monthIndex, 1);
    const lastDay = new Date(anchor.getFullYear(), anchor.getMonth() + 1, 0).getDate();
    anchor.setDate(Math.min(day, lastDay));
    return anchor;
}

function resolveToday(value) {
    if (value instanceof Date && !Number.isNaN(value.getTime())) {
        return new Date(value.getFullYear(), value.getMonth(), value.getDate());
    }
    if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [y, m, d] = value.split('-').map(Number);
        return new Date(y, m - 1, d);
    }
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

function inInclusiveRange(conducted, from, to) {
    return conducted !== '' && conducted >= from && conducted <= to;
}

export function isCustomRangeReady(fromValue, toValue) {
    const from = typeof fromValue === 'string' ? fromValue.trim() : '';
    const to = typeof toValue === 'string' ? toValue.trim() : '';
    return Boolean(from && to && from <= to);
}

function showToast(root, message) {
    const toast = root.querySelector('[data-risk-assess-toast]');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.hidden = false;

    window.clearTimeout(showToast._timer);
    showToast._timer = window.setTimeout(() => {
        toast.hidden = true;
        toast.textContent = '';
    }, 4200);
}

function storageKey(root) {
    const hh = root.getAttribute('data-household-no') || '';
    const mb = root.getAttribute('data-member-id') || '';
    return `${STORAGE_PREFIX}${hh}.${mb}`;
}

function collectFormState(form) {
    const data = new FormData(form);
    const payload = {};

    for (const [key, value] of data.entries()) {
        if (key.endsWith('[]')) {
            const clean = key.slice(0, -2);
            if (!Array.isArray(payload[clean])) {
                payload[clean] = [];
            }
            payload[clean].push(String(value));
        } else if (Object.prototype.hasOwnProperty.call(payload, key)) {
            if (!Array.isArray(payload[key])) {
                payload[key] = [payload[key]];
            }
            payload[key].push(String(value));
        } else {
            payload[key] = String(value);
        }
    }

    return payload;
}

function setStep(root, step) {
    const total = 5;
    const current = Math.min(Math.max(Number(step) || 1, 1), total);

    root.querySelectorAll('[data-risk-assess-step]').forEach((panel) => {
        const n = Number(panel.getAttribute('data-risk-assess-step'));
        panel.hidden = n !== current;
    });

    root.querySelectorAll('[data-risk-assess-step-indicator]').forEach((item) => {
        const n = Number(item.getAttribute('data-risk-assess-step-indicator'));
        item.classList.toggle('is-current', n === current);
        item.classList.toggle('is-complete', n < current);
        if (n === current) {
            item.setAttribute('aria-current', 'step');
        } else {
            item.removeAttribute('aria-current');
        }
    });

    const backBtn = root.querySelector('[data-risk-assess-back]');
    const nextBtn = root.querySelector('[data-risk-assess-next]');
    const saveBtn = root.querySelector('[data-risk-assess-save]');

    if (backBtn instanceof HTMLElement) {
        backBtn.hidden = current === 1;
    }
    if (nextBtn instanceof HTMLElement) {
        nextBtn.hidden = current === total;
    }
    if (saveBtn instanceof HTMLElement) {
        saveBtn.hidden = current !== total;
    }

    root.setAttribute('data-current-step', String(current));
}

function initExclusiveGroups(root) {
    root.querySelectorAll('[data-risk-assess-exclusive-group]').forEach((group) => {
        group.addEventListener('change', (event) => {
            const target = event.target;
            if (target instanceof HTMLInputElement) {
                applyNoneExclusiveLogic(group, target);
            }
        });
    });
}

function initWizard(root) {
    const form = root.querySelector('[data-risk-assess-form]');
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    if (form.getAttribute('data-risk-assess-readonly') === 'true') {
        return;
    }

    setStep(root, 1);
    initExclusiveGroups(root);

    root.querySelector('[data-risk-assess-next]')?.addEventListener('click', () => {
        const current = Number(root.getAttribute('data-current-step') || '1');
        setStep(root, current + 1);
    });

    root.querySelector('[data-risk-assess-back]')?.addEventListener('click', () => {
        const current = Number(root.getAttribute('data-current-step') || '1');
        setStep(root, current - 1);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        try {
            const payload = collectFormState(form);
            payload.savedAt = new Date().toISOString();
            window.sessionStorage.setItem(storageKey(root), JSON.stringify(payload));
        } catch {
            // sessionStorage may be unavailable; still show preview toast.
        }
        showToast(root, PREVIEW_SAVE_MESSAGE);
    });
}

function setCustomRangeVisible(root, visible) {
    const panel = root.querySelector('[data-risk-assess-custom-range]');
    const fromInput = root.querySelector('[data-risk-assess-date-from]');
    const toInput = root.querySelector('[data-risk-assess-date-to]');
    const funnel = root.querySelector('[data-risk-assess-date-icon-funnel]');
    const calendar = root.querySelector('[data-risk-assess-date-icon-calendar]');

    if (panel instanceof HTMLElement) {
        panel.hidden = !visible;
    }
    if (fromInput instanceof HTMLInputElement) {
        fromInput.disabled = !visible;
        if (!visible) {
            fromInput.value = '';
        }
    }
    if (toInput instanceof HTMLInputElement) {
        toInput.disabled = !visible;
        if (!visible) {
            toInput.value = '';
        }
    }
    if (funnel instanceof HTMLElement) {
        funnel.hidden = visible;
    }
    if (calendar instanceof HTMLElement) {
        calendar.hidden = !visible;
    }
}

function initHistory(root) {
    const select = root.querySelector('[data-risk-assess-date-select]');
    const form = root.querySelector('[data-risk-assess-date-filter]');
    const fromInput = root.querySelector('[data-risk-assess-date-from]');
    const toInput = root.querySelector('[data-risk-assess-date-to]');
    if (!(select instanceof HTMLSelectElement) || !(form instanceof HTMLFormElement)) {
        return;
    }

    const submitIfCustomReady = () => {
        if (select.value !== 'custom') {
            return;
        }
        const from =
            fromInput instanceof HTMLInputElement ? fromInput.value : '';
        const to = toInput instanceof HTMLInputElement ? toInput.value : '';
        if (!isCustomRangeReady(from, to)) {
            return;
        }
        form.requestSubmit();
    };

    select.addEventListener('change', () => {
        if (select.value === 'custom') {
            setCustomRangeVisible(root, true);
            return;
        }
        setCustomRangeVisible(root, false);
        form.requestSubmit();
    });

    fromInput?.addEventListener('change', submitIfCustomReady);
    toInput?.addEventListener('change', submitIfCustomReady);
}

function initHistorySection(root) {
    if (root.getAttribute('data-history-editing') !== 'true') {
        return;
    }

    initExclusiveGroups(root);
}

function initRiskAssessment(root) {
    const mode = root.getAttribute('data-lml-risk-assess-mode');
    if (mode === 'history') {
        initHistory(root);
        return;
    }

    if (mode === 'history-show') {
        return;
    }

    if (mode === 'history-section') {
        initHistorySection(root);
        return;
    }

    initWizard(root);
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-lml-risk-assess]').forEach((root) => {
            initRiskAssessment(root);
        });
    });
}