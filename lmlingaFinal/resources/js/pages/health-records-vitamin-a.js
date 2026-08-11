/**
 * Health Records → Child Care → Vitamin A monitoring summary.
 * Zone filter preserves selection (UI-only until backend aggregates exist).
 * Export matches Child Care UI-phase toast (no downloadable file).
 */

function showVitaminAToast(root, message) {
    const toast = root.querySelector('[data-hr-va-toast]');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.hidden = false;

    window.clearTimeout(showVitaminAToast._timer);
    showVitaminAToast._timer = window.setTimeout(() => {
        toast.hidden = true;
        toast.textContent = '';
    }, 3600);
}

function updateZoneStatus(root) {
    const zoneSelect = root.querySelector('[data-hr-va-zone]');
    const status = root.querySelector('[data-hr-va-zone-status]');
    if (!zoneSelect || !status) {
        return;
    }

    const value = zoneSelect.value || 'all';
    const label =
        value === 'all'
            ? 'All Zones'
            : zoneSelect.options[zoneSelect.selectedIndex]?.text || value;

    status.textContent = `Selected zone: ${label}. Filtered aggregates await backend integration.`;
}

function initHealthRecordsVitaminA(root) {
    const exportBtn = root.querySelector('[data-hr-va-export]');
    const zoneSelect = root.querySelector('[data-hr-va-zone]');

    exportBtn?.addEventListener('click', () => {
        showVitaminAToast(root, 'Export is not available during the UI phase.');
    });

    zoneSelect?.addEventListener('change', () => {
        updateZoneStatus(root);
    });

    updateZoneStatus(root);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-hr-vitamin-a]').forEach((root) => {
        initHealthRecordsVitaminA(root);
    });
});
