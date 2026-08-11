/**
 * Household Profiling — Death Information (Phase 1 UI).
 * Session/preview only. Updates selected certificate filename display.
 */

function basenameOnly(value) {
    const raw = String(value || '');
    const parts = raw.split(/[/\\]/);
    return parts[parts.length - 1] || '';
}

function initDeath(root) {
    const input = root.querySelector('[data-death-certificate-input]');
    const status = root.querySelector('[data-death-file-status]');
    const nameEl = root.querySelector('[data-death-file-name]');
    if (!input || !status) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files && input.files[0] ? input.files[0] : null;
        if (!file) {
            if (!nameEl || !nameEl.textContent.trim()) {
                status.hidden = true;
            }
            return;
        }

        const safeName = basenameOnly(file.name);
        status.hidden = false;
        status.replaceChildren();
        status.append(
            document.createTextNode('Selected for this session (preview only): ')
        );
        const strong = document.createElement('span');
        strong.setAttribute('data-death-file-name', '');
        strong.textContent = safeName;
        status.append(strong);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-lml-death]').forEach((root) => {
        initDeath(root);
    });
});
