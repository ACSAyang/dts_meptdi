/** Small form helpers used across the system. */
export function initForms() {
    // Show / hide password: <button data-password-toggle="input-id">
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-password-toggle]');
        if (!btn) return;
        const input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });

    // Show/hide blocks driven by a checkbox: <input data-toggle="#target">
    document.querySelectorAll('[data-toggle]').forEach((input) => {
        const target = document.querySelector(input.dataset.toggle);
        const sync = () => target?.classList.toggle('hidden', !input.checked);
        input.addEventListener('change', sync);
        sync();
    });

    // Confirmation prompts on irreversible actions: <form data-confirm="...">
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Prevent double submission: the submit button shows "Saving…" until the page changes.
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented || e.target.method?.toLowerCase() === 'get') return;
        const button = e.submitter || e.target.querySelector('button[type=submit], button:not([type])');
        if (!button) return;
        setTimeout(() => {
            button.disabled = true;
            button.dataset.label = button.textContent;
            button.textContent = 'Saving…';
        });
    });

    // Live preview of how a person's name is printed, e.g. "ASN Ian Russel U Pascual PCG".
    document.querySelectorAll('[data-name-preview]').forEach((preview) => {
        const form = preview.closest('form');
        if (!form) return;
        const read = (name) => (form.querySelector(`[name="${name}"]`)?.value || '').trim();
        const update = () => {
            const rank = read('rank');
            const mi = read('middle_initial').replace(/\.$/, '').toUpperCase();
            const name = [read('first_name'), mi, read('last_name')].filter(Boolean).join(' ');
            if (name) preview.textContent = [rank, name, rank ? 'PCG' : ''].filter(Boolean).join(' ');
        };
        ['rank', 'first_name', 'middle_initial', 'last_name'].forEach((n) => form.querySelector(`[name="${n}"]`)?.addEventListener('input', update));
        form.querySelector('[name="rank"]')?.addEventListener('change', update);
    });

    // Print button
    document.querySelectorAll('[data-print]').forEach((btn) => btn.addEventListener('click', () => window.print()));
}
