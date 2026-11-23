// Aktualizacja panelu licytacji, historii i odliczania na podstawie obiektu stanu (docs/api.md 3.1).
import { all, one, setHidden, setText } from './dom.js';
import { createCountdown } from './countdown.js';
import { renderHistory } from './history.js';
import { money, serverDate } from './format.js';

export function createPanel(root, i18n, { onNeedSync }) {
    const isBuyer = root.dataset.viewerState === 'buyer';
    const history = one('[data-bid-history]');
    const bar = one('[data-bid-bar]');
    const input = one('#bid-amount');
    const view = {
        status: root.dataset.status,
        viewerStatus: root.dataset.viewerStatus || 'none',
        price: Number(root.dataset.priceGrosze),
        minIncrement: Number(root.dataset.minIncrementGrosze),
        minNextBid: Number(root.dataset.minNextBidGrosze),
        endsAt: root.dataset.endsAt,
    };
    const countdown = createCountdown(all('[data-countdown]'), i18n, onNeedSync);
    countdown.sync(root.dataset.endsAt, root.dataset.serverTime);

    const placeLabel = () => (view.viewerStatus === 'outbid' ? i18n.outbidCta : i18n.place);

    function renderViewer(state) {
        const previous = view.viewerStatus;
        view.viewerStatus = state.viewer.status;
        root.classList.toggle('bid-panel--leading', view.viewerStatus === 'leading');
        root.classList.toggle('bid-panel--outbid', view.viewerStatus === 'outbid');
        if (!isBuyer) {
            return;
        }
        const leading = view.viewerStatus === 'leading';
        const outbid = view.viewerStatus === 'outbid';
        setHidden(one('[data-leading-block]', root), !leading);
        setHidden(one('[data-leading-wait]', root), !leading);
        setHidden(one('[data-bid-controls]', root), leading);
        setHidden(one('[data-outbid-block]', root), !outbid || state.viewer.myHighestBid === null);
        setText(all('[data-outbid-amount]', root), state.viewer.myHighestBid === null ? '' : money(state.viewer.myHighestBid));
        setHidden(one('[data-bar-leading]', bar || document), !leading);
        setHidden(one('[data-bid-open-bar]', bar || document), leading);
        setText(all('[data-place-label]'), placeLabel());
        const alertBox = one('[data-outbid-alert]', root);
        if (previous === 'leading' && outbid) {
            setHidden(alertBox, false); // role="alert" ogłasza komunikat po jego pokazaniu
        } else if (!outbid) {
            setHidden(alertBox, true);
        }
    }

    function renderBidInput() {
        if (!input) {
            return;
        }
        const minZloty = Math.ceil(view.minNextBid / 100);
        input.min = String(minZloty);
        input.step = String(Math.max(1, Math.round(view.minIncrement / 100)));
        const current = input.value.trim() === '' ? NaN : Number(input.value);
        if (Number.isNaN(current) || current < minZloty) {
            input.value = String(minZloty);
        }
        all('[data-quick-add]').forEach((button) => {
            const result = one('.bid-panel__quick-result', button);
            if (result) {
                result.textContent = '(' + money(view.price + Number(button.dataset.quickAdd) * 100) + ')';
            }
        });
    }

    function renderEnded(state) {
        countdown.stop();
        root.dataset.status = 'ended';
        view.status = 'ended';
        const timer = one('.bid-panel__timer', root);
        if (timer) {
            timer.textContent = i18n.ended;
        }
        setHidden(one('.bid-panel__end', root), true);
        setText(all('[data-price-label]', root), i18n.finalPrice);
        setHidden(one('[data-anti-sniping-rule]', root), true);
        setHidden(one('[data-bid-controls]', root), true);
        setHidden(one('[data-leading-wait]', root), true);
        setHidden(bar, true);
        if (state.viewer.status === 'won' || state.viewer.status === 'leading') {
            window.location.reload(); // serwer wyrenderuje panel wygranej z linkiem
        }
    }

    return {
        view,
        apply(state) {
            const auction = state.auction;
            const previousEndsAt = view.endsAt;
            view.price = auction.currentPrice;
            view.minIncrement = auction.minIncrement;
            view.minNextBid = auction.minNextBid;

            setText(all('[data-current-price]'), auction.currentPriceFormatted);
            setText(all('[data-min-next-bid]'), auction.minNextBidFormatted);
            setText(all('[data-min-increment]'), money(auction.minIncrement));
            setText(all('[data-bid-count]'), String(auction.bidCount));
            setText(all('[data-confirm-current]'), auction.currentPriceFormatted);
            if (history) {
                renderHistory(history, state, i18n);
            }

            view.endsAt = auction.endsAt;
            setText(all('[data-ends-at-label]'), serverDate(auction.endsAt, ', '));
            setHidden(one('[data-extended-tag]'), !auction.extended);
            countdown.sync(auction.endsAt, state.serverTime);

            renderViewer(state);
            renderBidInput();

            if (auction.status === 'ended') {
                renderEnded(state);
            } else if (root.dataset.status === 'scheduled' && auction.status === 'active') {
                window.location.reload(); // inny uklad panelu po starcie aukcji
            }

            return {
                extended: previousEndsAt !== auction.endsAt && Date.parse(auction.endsAt) > Date.parse(previousEndsAt),
                ended: auction.status === 'ended',
            };
        },
    };
}
