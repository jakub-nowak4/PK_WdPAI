// Male pomocnicze funkcje DOM dla modulu licytacji. Tekst zawsze przez textContent (bez innerHTML).

export const one = (selector, root = document) => root.querySelector(selector);
export const all = (selector, root = document) => Array.from(root.querySelectorAll(selector));

// `hidden` samo nie wystarcza, gdy klasa ustawia display (np. flex), wiec dodatkowo style.display.
export function setHidden(element, hidden) {
    if (!element) {
        return;
    }
    element.hidden = hidden;
    element.style.display = hidden ? 'none' : '';
}

// Elementy oznaczone w HTML atrybutem hidden maja zniknac takze wtedy, gdy CSS nadpisuje display.
export function syncHidden(root) {
    all('[hidden]', root).forEach((element) => {
        element.style.display = 'none';
    });
}

export function setText(elements, text) {
    elements.forEach((element) => {
        if (element.textContent !== text) {
            element.textContent = text;
        }
    });
}
