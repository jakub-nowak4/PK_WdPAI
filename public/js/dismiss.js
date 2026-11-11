// Zamykanie alertow przyciskiem "x": ukrywa element [data-dismissible] i oddaje fokus polu kwoty.
document.querySelectorAll('[data-dismissible]').forEach((alertBox) => {
    alertBox.querySelector('[data-dismiss]')?.addEventListener('click', () => {
        alertBox.hidden = true;
        document.getElementById('bid-amount')?.focus();
    });
});
