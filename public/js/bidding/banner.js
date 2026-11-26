// Banery wyniku oferty nad ukladem strony: sukces (ekran 05, role="status") i blad (ekran 06, role="alert").
// Tekst zawsze przez textContent; tresc bledu pochodzi z odpowiedzi serwera (error.message), nie z kodu JS.
import { one, setHidden } from './dom.js';
import { fillTemplate } from './format.js';

export function createBanner(i18n, focusTarget) {
    const success = one('[data-bid-banner="success"]');
    const failure = one('[data-bid-banner="error"]');

    function hideAll() {
        setHidden(success, true);
        setHidden(failure, true);
    }

    one('[data-banner-dismiss]', failure ?? document)?.addEventListener('click', () => {
        setHidden(failure, true);
        focusTarget()?.focus();
    });

    return {
        hide: hideAll,
        hideSuccess() {
            setHidden(success, true);
        },
        // amount: sformatowana kwota oferty, extended: czy anti-sniping przedluzyl aukcje.
        showSuccess(amount, extended) {
            if (!success) {
                return;
            }
            hideAll();
            one('[data-banner-title]', success).textContent = fillTemplate(i18n.successTitle, { amount });
            setHidden(one('[data-banner-extended]', success), !extended);
            setHidden(success, false);
        },
        // announce=false dla bledow pola: komunikat czyta juz alert przy polu, baner nie dubluje.
        showError(text, { announce = true, extra = null } = {}) {
            if (!failure) {
                return;
            }
            hideAll();
            if (announce) {
                failure.setAttribute('role', 'alert');
            } else {
                failure.removeAttribute('role');
            }
            const textBox = one('[data-banner-text]', failure);
            textBox.replaceChildren(document.createTextNode(text));
            if (extra) {
                textBox.append(' ', extra);
            }
            setHidden(failure, false);
        },
    };
}
