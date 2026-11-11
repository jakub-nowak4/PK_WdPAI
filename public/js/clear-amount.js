// Przycisk "Wyczysc" przy polu kwoty (stan bledu oferty): czysci pole i oddaje mu fokus.
const input = document.getElementById('bid-amount');

document.querySelectorAll('[data-clear-amount]').forEach((button) => {
    button.addEventListener('click', () => {
        if (input) {
            input.value = '';
            input.focus();
        }
    });
});
