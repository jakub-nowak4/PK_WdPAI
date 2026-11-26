// Formularz "Dodaj aukcje": walidacja kliencka pol i zdjec, podglad miniatur, licznik opisu.
// Poprawia UX; serwer i tak waliduje wszystko (422 z komunikatami przy polach).
import { clearError, formatMessage, readMessages, showError } from '../field-errors.js';
import { validateAuctionField } from './auction-rules.js';
import { validatePhotos } from './photo-rules.js';
import { renderPreview } from './photo-preview.js';

const messages = readMessages();

function check(field, form) {
    const result = validateAuctionField(field, form);
    if (result) {
        showError(field, formatMessage(messages, result));
    } else {
        clearError(field);
    }
    return result === null;
}

function checkPhotos(input, list) {
    const files = [...input.files];
    const result = validatePhotos(files, input.required);
    if (result) {
        showError(input, formatMessage(messages, result));
    } else {
        clearError(input);
    }
    // Podglad tylko dla poprawnego zestawu; przy bledzie lista jest pusta.
    renderPreview(list, result ? [] : files, messages['photo.cover'] ?? '');
    return result === null;
}

function initCounter(textarea) {
    const counter = document.getElementById(textarea.dataset.counterTarget ?? '');
    if (!counter) {
        return;
    }
    const max = textarea.maxLength;
    const update = () => {
        counter.textContent = `${[...textarea.value].length} / ${max}`;
    };
    textarea.addEventListener('input', update);
    update();
}

function init(form) {
    const fields = [...form.querySelectorAll('.form-field__control:not([type="file"])')];
    const photos = form.querySelector('input[type="file"][name="photos[]"]');
    const preview = form.querySelector('[data-photo-preview]');

    for (const field of fields) {
        const eventName = field instanceof HTMLSelectElement ? 'change' : 'blur';
        field.addEventListener(eventName, () => {
            if (field.value !== '' || field.hasAttribute('aria-invalid')) {
                check(field, form);
            }
        });
        field.addEventListener('input', () => {
            if (field.hasAttribute('aria-invalid')) {
                check(field, form);
            }
        });
    }

    // Zmiana startu zmienia dozwolony zakres konca.
    form.elements.namedItem('startsAt')?.addEventListener('change', () => {
        const ends = form.elements.namedItem('endsAt');
        if (ends && ends.value !== '') {
            check(ends, form);
        }
    });

    const vin = form.elements.namedItem('vin');
    vin?.addEventListener('blur', () => {
        vin.value = vin.value.trim().toUpperCase();
    });

    const description = form.elements.namedItem('description');
    if (description) {
        initCounter(description);
    }

    if (photos && preview) {
        photos.addEventListener('change', () => checkPhotos(photos, preview));
    }

    form.addEventListener('submit', (event) => {
        if (vin) {
            vin.value = vin.value.trim().toUpperCase();
        }
        const invalid = fields.filter((field) => !check(field, form));
        if (photos && preview && !checkPhotos(photos, preview)) {
            invalid.push(photos);
        }
        if (invalid.length > 0) {
            event.preventDefault();
            // Fokus na pierwszym blednym polu w kolejnosci dokumentu.
            invalid.sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
            invalid[0].focus();
        }
    });
}

document.querySelectorAll('form[data-auction-form]').forEach(init);
