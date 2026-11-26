// Licytacja na zywo na /auctions/{id}: polling stanu (GET), odliczanie wg czasu serwera, skladanie oferty (POST).
// Moduly: bidding/api.js, poller.js, panel.js, countdown.js, history.js, bid-form.js, messages.js, format.js, dom.js.
import { createBanner } from './bidding/banner.js';
import { createBidForm } from './bidding/bid-form.js';
import { syncHidden } from './bidding/dom.js';
import { createMessages } from './bidding/messages.js';
import { createPanel } from './bidding/panel.js';
import { createPoller } from './bidding/poller.js';

const root = document.querySelector('[data-state-url]');
const i18nElement = document.getElementById('bid-i18n');

if (root && i18nElement) {
    const i18n = JSON.parse(i18nElement.textContent);
    syncHidden(document);

    const messages = createMessages(root);
    const poller = createPoller(root.dataset.stateUrl, {
        onState: (state) => handleState(state, { fromBid: false }),
        onOnline: () => {
            messages.clearStatus('offline');
            banner.hideError('network'); // siec wrocila: stary baner "oferta nie zostala wyslana" nie jest juz aktualny
        },
        onOffline: () => messages.showStatus(i18n.offline, 'offline'),
    });
    const panel = createPanel(root, i18n, { onNeedSync: () => poller.pollNow() });

    function handleState(state, { fromBid }) {
        const wasLeading = panel.view.viewerStatus === 'leading';
        const outcome = panel.apply(state);
        if (wasLeading && panel.view.viewerStatus !== 'leading') {
            // przebicie: stary sukces ("Oferta przyjeta", "Prowadzisz") przestaje byc prawdziwy
            messages.clearStatus('success');
            banner.hideSuccess();
        }
        if (outcome.ended) {
            poller.stop();
            messages.clearStatus('offline');
        } else if (outcome.extended && !fromBid) {
            messages.showStatus(i18n.extended, 'info');
        }
    }

    const banner = createBanner(i18n, () => document.getElementById('bid-amount'));

    createBidForm(root, i18n, messages, banner, {
        view: panel.view,
        applyState: handleState,
        claimSequence: () => poller.claimSequence(),
    });

    poller.start();
}
