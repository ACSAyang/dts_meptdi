/**
 * Pop-up windows (<dialog data-modal>).
 *   <a href="/fallback" data-modal-open="dialog-id">  opens the dialog (the link still works without JavaScript)
 *   <button data-modal-close>                       closes the dialog it is in
 *   <dialog data-auto-open>                         opens on page load (used to show validation errors)
 */
export function initModals() {
    const open = (dialog) => {
        if (!dialog || dialog.open) return;
        dialog.showModal();
        dialog.querySelector('[autofocus], input:not([type=hidden]):not([readonly]), select, textarea')?.focus();
    };

    document.addEventListener('click', (e) => {
        const opener = e.target.closest('[data-modal-open]');
        if (opener) {
            const dialog = document.getElementById(opener.dataset.modalOpen);
            if (dialog) {
                e.preventDefault();
                open(dialog);
            }
            return;
        }

        e.target.closest('[data-modal-close]')?.closest('dialog')?.close();
    });

    document.querySelectorAll('dialog[data-modal]').forEach((dialog) => {
        // Close when the dark area outside the window is clicked (not when a drag ends there).
        let downOnBackdrop = false;
        dialog.addEventListener('mousedown', (e) => { downOnBackdrop = e.target === dialog; });
        dialog.addEventListener('click', (e) => {
            if (downOnBackdrop && e.target === dialog) dialog.close();
        });

        if (dialog.hasAttribute('data-auto-open')) open(dialog);
    });
}
