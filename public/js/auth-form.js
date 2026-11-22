// Walidacja formularzy logowania i rejestracji po stronie klienta (poprawia UX, nie zastepuje serwera).
// Komunikaty pochodza z tlumaczen: JSON w #validation-messages (partial validation-messages.html.php).
import { validateField } from './validation.js';

const messages = readMessages();

function readMessages() {
    try {
        return JSON.parse(document.getElementById('validation-messages')?.textContent ?? '{}');
    } catch {
        return {};
    }
}

function format(result) {
    let text = messages[result.key] ?? '';
    for (const [name, value] of Object.entries(result.params)) {
        text = text.replace(`{${name}}`, String(value));
    }
    return text;
}

function errorElement(input) {
    const id = `${input.id}-error`;
    let element = document.getElementById(id);
    if (!element) {
        element = document.createElement('p');
        element.className = 'form-field__error';
        element.id = id;
        element.setAttribute('role', 'alert');
        input.closest('.form-field')?.append(element);
    }
    return element;
}

function setDescribedBy(input, errorId, on) {
    const ids = (input.getAttribute('aria-describedby') ?? '').split(' ').filter((id) => id && id !== errorId);
    if (on) {
        ids.push(errorId);
    }
    if (ids.length > 0) {
        input.setAttribute('aria-describedby', ids.join(' '));
    } else {
        input.removeAttribute('aria-describedby');
    }
}

function showError(input, text) {
    const element = errorElement(input);
    element.textContent = text;
    element.hidden = false;
    input.setAttribute('aria-invalid', 'true');
    input.closest('.form-field')?.classList.add('form-field--error');
    setDescribedBy(input, element.id, true);
}

function clearError(input) {
    const element = document.getElementById(`${input.id}-error`);
    if (element) {
        element.textContent = '';
        element.hidden = true;
    }
    input.removeAttribute('aria-invalid');
    input.closest('.form-field')?.classList.remove('form-field--error');
    setDescribedBy(input, `${input.id}-error`, false);
}

function check(input, form) {
    const result = validateField(input, form);
    if (result) {
        showError(input, format(result));
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
