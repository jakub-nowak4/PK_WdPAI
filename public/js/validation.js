// Reguly walidacji pol formularzy (tylko UX: serwer waliduje zawsze, docs/api.md 1.7).
// Funkcje czyste, bez DOM poza odczytem atrybutow pola. Zwracaja klucz komunikatu albo null.

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const encoder = new TextEncoder();

export function byteLength(value) {
    return encoder.encode(value).length;
}

/**
 * @param {HTMLInputElement} input
 * @param {HTMLFormElement} form
 * @returns {{key: string, params: Record<string, number>} | null}
 */
export function validateField(input, form) {
    const value = input.value;
    const trimmed = input.type === 'password' ? value : value.trim();
    const min = Number(input.getAttribute('minlength')) || 0;
    const max = Number(input.getAttribute('maxlength')) || 0;

    if (input.required && trimmed === '') {
        return { key: 'validation.required', params: {} };
    }
    if (trimmed === '') {
        return null;
    }
    if (input.type === 'email' && !EMAIL_PATTERN.test(trimmed)) {
        return { key: 'validation.email', params: {} };
    }
    if (min > 0 && [...trimmed].length < min) {
        return { key: 'validation.min_length', params: { min } };
    }
    if (input.type === 'password' && max > 0 && byteLength(value) > max) {
        return { key: 'validation.password_max_bytes', params: { max } };
    }
    if (input.type !== 'password' && max > 0 && [...trimmed].length > max) {
        return { key: 'validation.max_length', params: { max } };
    }
    const pattern = input.getAttribute('pattern');
    if (pattern && !new RegExp(`^(?:${pattern})$`).test(trimmed)) {
        return { key: 'validation.invalid_format', params: {} };
    }
    if (input.name === 'passwordRepeat') {
        const password = form.elements.namedItem('password');
        if (password && password.value !== value) {
            return { key: 'validation.password_mismatch', params: {} };
        }
    }
    return null;
}
