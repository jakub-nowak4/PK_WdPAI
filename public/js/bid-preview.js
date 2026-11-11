// MAKIETA (T-009, ekran 04): otwiera okno potwierdzenia od razu po wejsciu na ?state=confirm.
// Tylko podglad; prawdziwa obsluga licytacji trafi do osobnego modulu (T-034). Do usuniecia razem z makieta.
const dialog = document.querySelector('[data-bid-dialog]');
const checkbox = dialog?.querySelector('[data-confirm-check]');
const submit = dialog?.querySelector('[data-confirm-submit]');

if (dialog && typeof dialog.showModal === 'function' && !dialog.open) {
    dialog.showModal();
}

checkbox?.addEventListener('change', () => {
    if (submit) {
        submit.disabled = !checkbox.checked;
    }
});
