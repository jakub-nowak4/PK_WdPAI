// Komunikaty panelu: status (role="status", sukces/informacja) i blad (role="alert", ewentualnie z linkiem lub przyciskiem).
import { one, setHidden } from './dom.js';

export function createMessages(root) {
    const statusBox = one('[data-bid-message]', root);
    const errorBox = one('[data-bid-error]', root);
    let statusKind = '';

    return {
        // kind pozwala skasowac tylko wlasny komunikat (np. "offline" nie zmazuje potwierdzenia oferty).
        showStatus(text, kind = 'info') {
            statusKind = kind;
            statusBox.replaceChildren(document.createTextNode(text));
            statusBox.classList.toggle('bid-panel__message--success', kind === 'success');
            setHidden(statusBox, false);
        },
        clearStatus(kind) {
            if (kind && statusKind !== kind) {
                return;
            }
            statusKind = '';
            statusBox.replaceChildren();
            setHidden(statusBox, true);
        },
        showError(text, extra) {
            errorBox.replaceChildren(document.createTextNode(text));
            if (extra) {
                errorBox.append(' ', extra);
            }
            setHidden(errorBox, false);
        },
        clearError() {
            errorBox.replaceChildren();
            setHidden(errorBox, true);
        },
    };
}
