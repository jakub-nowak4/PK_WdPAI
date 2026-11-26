// Reguly pol formularza aukcji (tylko UX; serwer waliduje zawsze, docs/api.md 2.5).
// Zwracaja {key, params} albo null. Teksty to klucze z tlumaczen (#validation-messages).
import { validateField } from '../validation.js';

const HOUR_MS = 60 * 60 * 1000;
const MAX_DAYS_MS = 30 * 24 * HOUR_MS;

function validateNumber(input) {
    const value = input.value.trim();
    if (value === '') {
        return null;
    }
    const min = input.min === '' ? -Infinity : Number(input.min);
    const max = input.max === '' ? Infinity : Number(input.max);
    if (!/^\d+$/.test(value) || Number(value) < min || Number(value) > max) {
        return { key: 'validation.number_range', params: { min, max } };
    }
    return null;
}

function parseLocal(value) {
    const time = Date.parse(value);
    return Number.isNaN(time) ? null : time;
}

function validateDates(input, form) {
    if (input.value === '' || parseLocal(input.value) === null) {
        return input.value === '' ? null : { key: 'validation.invalid_format', params: {} };
    }
    if (input.name !== 'endsAt') {
        return null;
    }
    const startsValue = form.elements.namedItem('startsAt')?.value ?? '';
    const start = startsValue === '' ? Date.now() : parseLocal(startsValue);
    const end = parseLocal(input.value);
    if (start !== null && end - start < HOUR_MS) {
        return { key: 'validation.ends_too_soon', params: {} };
    }
    if (start !== null && end - start > MAX_DAYS_MS) {
        return { key: 'validation.ends_too_late', params: {} };
    }
    return null;
}

/**
 * @param {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} field
 * @param {HTMLFormElement} form
 */
export function validateAuctionField(field, form) {
    if (field instanceof HTMLSelectElement) {
        return field.required && field.value === '' ? { key: 'validation.required', params: {} } : null;
    }
    if (field.type === 'number') {
        return validateField(field, form) ?? validateNumber(field);
    }
    if (field.type === 'datetime-local') {
        return validateField(field, form) ?? validateDates(field, form);
    }
    return validateField(field, form);
}
