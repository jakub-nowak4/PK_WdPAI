// Walidacja formularzy logowania i rejestracji po stronie klienta (poprawia UX, nie zastepuje serwera).
// Komunikaty pochodza z tlumaczen: JSON w #validation-messages (partial validation-messages.html.php).
import { validateField } from './validation.js';
import { clearError, formatMessage, readMessages, showError } from './field-errors.js';

const messages = readMessages();

function check(input, form) {
    const result = validateField(input, form);
    if (result) {
        showError(input, formatMessage(messages, result));
    } else {
        clearError(input);
    }
    return result === null;
}

function init(form) {
    const inputs = [...form.querySelectorAll('input.form-field__control')];

    for (const input of inputs) {
        input.addEventListener('blur', () => {
            if (input.value !== '' || input.hasAttribute('aria-invalid')) {
                check(input, form);
            }
        });
        input.addEventListener('input', () => {
            if (input.hasAttribute('aria-invalid')) {
                check(input, form);
            }
            // Zmiana hasla uniewaznia lub naprawia zgodnosc z powtorzeniem.
            if (input.name === 'password') {
                const repeat = form.elements.namedItem('passwordRepeat');
                if (repeat && repeat.value !== '') {
                    check(repeat, form);
                }
            }
        });
    }

    form.addEventListener('submit', (event) => {
        const invalid = inputs.filter((input) => !check(input, form));
        if (invalid.length > 0) {
            event.preventDefault();
            invalid[0].focus();
        }
    });
}

document.querySelectorAll('form[data-validate]').forEach(init);
