// Wspolne funkcje bledow pol formularza (aria-invalid, role="alert", aria-describedby).
// Komunikaty pochodza z tlumaczen: JSON w #validation-messages.

export function readMessages() {
    try {
        return JSON.parse(document.getElementById('validation-messages')?.textContent ?? '{}');
    } catch {
        return {};
    }
}

export function formatMessage(messages, result) {
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

export function showError(input, text) {
    const element = errorElement(input);
    element.textContent = text;
    element.hidden = false;
    input.setAttribute('aria-invalid', 'true');
    input.closest('.form-field')?.classList.add('form-field--error');
    setDescribedBy(input, element.id, true);
}

export function clearError(input) {
    const element = document.getElementById(`${input.id}-error`);
    if (element) {
        element.textContent = '';
        element.hidden = true;
    }
    input.removeAttribute('aria-invalid');
    input.closest('.form-field')?.classList.remove('form-field--error');
    setDescribedBy(input, `${input.id}-error`, false);
}
