// Reguly zdjec aukcji po stronie klienta (tylko UX; serwer sprawdza rzeczywisty MIME, getimagesize i limity).
// Wartosci zgodne z docs/api.md 2.5: 1-8 plikow, do 5 MB, JPG/PNG/WebP.

export const MAX_PHOTOS = 8;
export const MAX_BYTES = 5 * 1024 * 1024;
export const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

function hasAllowedExtension(name) {
    const extension = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
    return ALLOWED_EXTENSIONS.includes(extension);
}

/**
 * @param {File[]} files
 * @param {boolean} required
 * @returns {{key: string, params: Record<string, string|number>} | null}
 */
export function validatePhotos(files, required) {
    if (files.length === 0) {
        return required ? { key: 'photo.required', params: {} } : null;
    }
    if (files.length > MAX_PHOTOS) {
        return { key: 'photo.too_many', params: { max: MAX_PHOTOS } };
    }
    for (const file of files) {
        if (!ALLOWED_TYPES.includes(file.type) || !hasAllowedExtension(file.name)) {
            return { key: 'photo.bad_type', params: { name: file.name } };
        }
        if (file.size > MAX_BYTES) {
            return { key: 'photo.too_big', params: { name: file.name, max: MAX_BYTES / (1024 * 1024) } };
        }
    }
    return null;
}
