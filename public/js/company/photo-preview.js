// Podglad wybranych zdjec: miniatury z URL.createObjectURL, nazwy pliku przez textContent.

/**
 * @param {HTMLElement} list lista <ul> na miniatury
 * @param {File[]} files
 * @param {string} coverLabel tekst przy pierwszym zdjeciu (okladka)
 */
export function renderPreview(list, files, coverLabel) {
    for (const image of list.querySelectorAll('img')) {
        URL.revokeObjectURL(image.src);
    }
    list.replaceChildren();

    files.forEach((file, index) => {
        if (!file.type.startsWith('image/')) {
            return;
        }
        const item = document.createElement('li');
        item.className = 'photo-preview__item';

        const image = document.createElement('img');
        image.className = 'photo-preview__image';
        image.src = URL.createObjectURL(file);
        image.alt = file.name;
        image.width = 160;
        image.height = 107;

        const name = document.createElement('span');
        name.className = 'photo-preview__name';
        name.textContent = index === 0 && coverLabel ? `${file.name} (${coverLabel})` : file.name;

        item.append(image, name);
        list.append(item);
    });
}
