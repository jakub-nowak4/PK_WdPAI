// Formularz oferty: walidacja UX, okno potwierdzenia, POST przez Fetch API, stany ladowania/sukcesu/bledu.
// Serwer waliduje zawsze; walidacja tutaj tylko oszczedza zbedne zapytanie.
import { NetworkError, postBid } from './api.js';
import { all, one, setHidden, setText } from './dom.js';
import { fillTemplate, money } from './format.js';

export function createBidForm(root, i18n, messages, banner, { view, applyState, claimSequence }) {
    const form = one('[data-bid-form]', root);
    if (!form) {
        return;
    }
    const input = one('#bid-amount', form);
    const fieldError = one('[data-amount-error]', form);
    const submitButtons = [one('[data-bid-open]', form), one('[data-bid-open-bar]')].filter(Boolean);
    const dialog = one('[data-bid-dialog]');
    const confirmCheck = dialog ? one('[data-confirm-check]', dialog) : null;
    const confirmSubmit = dialog ? one('[data-confirm-submit]', dialog) : null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const describedBy = input.getAttribute('aria-describedby') ?? '';
    let busy = false;
    let lastAmount = null;

    function clearFieldError() {
        setHidden(fieldError, true);
        input.removeAttribute('aria-invalid');
        input.setAttribute('aria-describedby', describedBy);
    }

    function showFieldError(text) {
        fieldError.textContent = text;
        setHidden(fieldError, false);
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', (fieldError.id + ' ' + describedBy).trim());
    }

    function setBusy(value) {
        busy = value;
        root.setAttribute('aria-busy', String(value));
        submitButtons.forEach((button) => {
            button.disabled = value;
        });
        if (value) {
            setText(all('[data-place-label]'), i18n.sending);
        } else {
            setText(all('[data-place-label]'), view.viewerStatus === 'outbid' ? i18n.outbidCta : i18n.place);
        }
    }

    function readAmount() {
        const raw = input.value.trim();
        if (!/^\d{1,12}$/.test(raw) || Number(raw) <= 0) {
            return { error: i18n.amountInvalid };
        }
        const grosze = Number(raw) * 100;
        if (grosze < view.minNextBid) {
            return { error: fillTemplate(i18n.tooLow, { min: money(view.minNextBid) }) };
        }

        return { grosze };
    }

    function openConfirm(trigger) {
        if (busy) {
            return;
        }
        messages.clearError();
        clearFieldError();
        const parsed = readAmount();
        if (parsed.error) {
            showFieldError(parsed.error);
            input.focus();
            return;
        }
        lastAmount = parsed.grosze;
        if (!dialog || typeof dialog.showModal !== 'function') {
            submit(parsed.grosze);
            return;
        }
        setText(all('[data-confirm-amount]', dialog), money(parsed.grosze));
        if (confirmCheck) {
            confirmCheck.checked = false;
        }
        if (confirmSubmit) {
            confirmSubmit.disabled = true;
        }
        dialog.dataset.trigger = trigger === 'bar' ? 'bar' : 'form';
        dialog.showModal();
        confirmCheck?.focus();
    }

    async function submit(grosze) {
        if (busy) {
            return;
        }
        setBusy(true);
        messages.clearError();
        messages.clearStatus();
        banner.hide();
        clearFieldError();
        let result;
        try {
            result = await postBid(root.dataset.bidUrl, grosze, csrfToken);
        } catch (error) {
            if (!(error instanceof NetworkError)) {
                throw error;
            }
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'button button--link';
            retry.textContent = i18n.retry;
            retry.addEventListener('click', () => submit(grosze));
            banner.showError(i18n.networkError, { extra: retry });
            setBusy(false);
            return;
        }
        handleResult(result, grosze);
        setBusy(false);
    }

    function handleResult({ status, ok, data }, grosze) {
        const state = data?.state;
        if (state?.auction) {
            claimSequence();
            applyState(state, { fromBid: true });
        }
        if (ok) {
            // Sukces ogłasza baner (role="status"); drugi komunikat w panelu podwoiłby odczyt czytnika ekranu.
            banner.showSuccess(data?.bid?.amountFormatted ?? money(grosze), data?.extended === true);
            const focusTarget = one('[data-leading-block]', root);
            if (focusTarget && !focusTarget.hidden) {
                focusTarget.focus();
            }
            return;
        }
        const error = data?.error;
        const text = error?.message || i18n.serverError;
        if (error?.code === 'bid_too_low' && Number.isInteger(error.details?.minNextBid)) {
            input.value = String(Math.ceil(error.details.minNextBid / 100)); // podpowiedz poprawnej kwoty
        }
        if (error?.field === 'amount' || error?.code === 'bid_too_low' || error?.code === 'amount_invalid') {
            showFieldError(text);
            banner.showError(i18n.errorTitle, { announce: false });
            input.focus();
        } else if (status === 401) {
            const link = document.createElement('a');
            link.href = '/login?next=' + encodeURIComponent('/auctions/' + root.dataset.auctionId);
            link.textContent = i18n.loginToBid;
            banner.showError(text, { extra: link });
        } else {
            banner.showError(text);
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        openConfirm('form');
    });
    one('[data-bid-open-bar]')?.addEventListener('click', () => openConfirm('bar'));
    input.addEventListener('input', () => {
        clearFieldError();
    });

    all('[data-quick-add]', form).forEach((button) => {
        button.addEventListener('click', () => {
            input.value = String(Math.round(view.price / 100) + Number(button.dataset.quickAdd));
            clearFieldError();
        });
    });
    all('[data-step]', form).forEach((button) => {
        button.addEventListener('click', () => {
            const minZloty = Math.ceil(view.minNextBid / 100);
            const stepZloty = Math.max(1, Math.round(view.minIncrement / 100));
            const base = /^\d+$/.test(input.value.trim()) ? Number(input.value) : minZloty;
            input.value = String(Math.max(minZloty, base + Number(button.dataset.step) * stepZloty));
            clearFieldError();
        });
    });

    confirmCheck?.addEventListener('change', () => {
        confirmSubmit.disabled = !confirmCheck.checked;
    });
    confirmSubmit?.addEventListener('click', () => {
        if (!confirmCheck.checked || lastAmount === null) {
            return;
        }
        // Kwote sprawdzamy ponownie: stan mogl sie zmienic, gdy okno bylo otwarte.
        const parsed = readAmount();
        dialog.close('confirm');
        if (parsed.error) {
            showFieldError(parsed.error);
            input.focus();
            return;
        }
        submit(parsed.grosze);
    });
    dialog?.addEventListener('close', () => {
        if (confirmCheck) {
            confirmCheck.checked = false;
        }
        if (confirmSubmit) {
            confirmSubmit.disabled = true;
        }
    });

}
