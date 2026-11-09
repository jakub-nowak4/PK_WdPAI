<?php
/**
 * Okno potwierdzenia oferty (ekran 04). Domyslnie zamkniete (<dialog> bez atrybutu open);
 * otwiera je public/js/bidding.js (T-034) przez showModal(), Esc zamyka, fokus wraca na "Zlóz oferte".
 * @var callable $t
 * @var callable $e
 * @var callable $money
 * @var array<string,mixed> $auction
 */
?>
<dialog class="dialog dialog--sheet" id="bid-confirm-dialog" aria-labelledby="bid-confirm-title" aria-describedby="bid-confirm-desc" data-bid-dialog>
    <form method="dialog" class="dialog__form" data-confirm-form>
        <header class="dialog__header">
            <h2 id="bid-confirm-title"><?= $e($t('bids.confirm_title', 'Potwierdź ofertę')) ?></h2>
            <button class="button button--icon" type="submit" value="cancel" formnovalidate aria-label="<?= $e($t('common.close', 'Zamknij')) ?>">×</button>
        </header>
        <p id="bid-confirm-desc"><?= $e($t('bids.confirm_desc', 'Sprawdź kwotę. Złożenie oferty jest wiążące.')) ?></p>

        <section class="dialog__vehicle" aria-label="<?= $e($t('bids.vehicle', 'Pojazd')) ?>">
            <p><strong><?= $e($auction['title']) ?></strong></p>
            <p><?= $e($t('auction.vin', 'VIN')) ?>: <code><?= $e($auction['vin']) ?></code></p>
        </section>

        <dl class="dialog__amounts">
            <div class="dialog__amount dialog__amount--yours">
                <dt><?= $e($t('bids.your_bid', 'Twoja oferta')) ?></dt>
                <dd data-confirm-amount><?= $e($money($auction['minNextBid'])) ?></dd>
            </div>
            <div><dt><?= $e($t('auction.current_price', 'Aktualna cena')) ?></dt><dd><?= $e($money($auction['price'])) ?></dd></div>
            <div><dt><?= $e($t('bids.min_step', 'Minimalny krok')) ?></dt><dd>+<?= $e($money($auction['minIncrement'])) ?></dd></div>
        </dl>

        <p class="alert alert--warning"><?= $e($t('bids.anti_sniping', 'Oferta złożona w ostatnich 30 sekundach przedłuża aukcję o 30 sekund.')) ?></p>

        <div class="form-field form-field--check">
            <input id="bid-confirm-binding" type="checkbox" required data-confirm-check>
            <label for="bid-confirm-binding"><?= $e($t('bids.binding', 'Rozumiem, że oferta jest wiążąca i nie może zostać anulowana po złożeniu.')) ?></label>
        </div>

        <footer class="dialog__footer">
            <button class="button button--secondary" type="submit" value="cancel" formnovalidate><?= $e($t('common.cancel', 'Anuluj')) ?></button>
            <button class="button button--accent" type="button" disabled data-confirm-submit><?= $e($t('bids.confirm', 'Potwierdź ofertę')) ?></button>
        </footer>
    </form>
</dialog>
