// Historia licytacji: przebudowa listy z recentBids (najnowsza oferta pierwsza), tylko przez textContent.
import { money, serverDate } from './format.js';
import { setHidden } from './dom.js';

function span(className, text) {
    const element = document.createElement('span');
    element.className = className;
    element.textContent = text;

    return element;
}

export function renderHistory(section, state, i18n) {
    const list = section.querySelector('[data-bid-list]');
    const empty = section.querySelector('[data-bid-empty]');
    if (!list || !empty) {
        return;
    }
    const bids = state.recentBids;
    const startPrice = Number(section.dataset.startPrice);
    const totalCount = state.auction.bidCount;
    const signature = JSON.stringify(bids.map((bid) => [bid.amount, bid.bidder, bid.isYou, bid.placedAt]));
    if (list.dataset.signature === signature) {
        return;
    }
    list.dataset.signature = signature;

    setHidden(empty, bids.length > 0);
    setHidden(list, bids.length === 0);
    list.replaceChildren();

    bids.forEach((bid, index) => {
        const older = bids[index + 1];
        let diff = null;
        if (older) {
            diff = bid.amount - older.amount;
        } else if (totalCount === bids.length && Number.isFinite(startPrice)) {
            diff = bid.amount - startPrice;
        }
        const item = document.createElement('li');
        item.className = 'bid-history__item'
            + (index === 0 ? ' bid-history__item--leader' : '')
            + (bid.isYou ? ' bid-history__item--you' : '');
        item.append(span('bid-history__bidder', bid.isYou ? i18n.you : bid.bidder));
        item.append(span('bid-history__when', serverDate(bid.placedAt)));
        const amount = document.createElement('strong');
        amount.className = 'bid-history__amount';
        amount.textContent = bid.amountFormatted || money(bid.amount);
        item.append(amount);
        if (diff !== null && diff > 0) {
            item.append(span('bid-history__diff', '+' + money(diff)));
        }
        if (index === 0) {
            item.append(span('status-label status-label--leading', i18n.leader));
        } else if (bid.isYou) {
            item.append(span('status-label status-label--outbid', i18n.outbid));
        }
        list.append(item);
    });
}
