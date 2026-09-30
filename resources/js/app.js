

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Guard every form in the app against double submission — e.g. double-clicking
// "Save" on an invoice or "Record Payment" firing the same POST twice on a slow
// connection. Runs after any onsubmit="return confirm(...)" or Alpine @submit
// validation has already had a chance to cancel the event (bubble phase on
// document fires after the form's own target-phase handlers), so a cancelled
// confirm() or failed client-side validation never disables the button.
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;

    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
        if (button.disabled) return;
        button.disabled = true;
        button.dataset.doubleSubmitGuard = '1';
        button.style.opacity = '0.6';
        button.style.cursor = 'not-allowed';
    });
});

// Safety net: if the page is restored (back/forward navigation, including
// bfcache) with a guarded button still disabled — e.g. the submit never
// actually left the page — re-enable it so the form stays usable.
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-double-submit-guard="1"]').forEach((button) => {
        button.disabled = false;
        button.style.opacity = '';
        button.style.cursor = '';
        delete button.dataset.doubleSubmitGuard;
    });
});
