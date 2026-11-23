<?php
/**
 * Panel licytacji (prawa kolumna) + dolny pasek bid-bar (mobile). Stan wybierany po $currentUser i statusie aukcji:
 * ended | scheduled | guest | cannot (firma, administrator, wlasna aukcja) | buyer (moze licytowac).
 * Atrybuty data-* dla przyszlego public/js/bidding.js (T-034).
 * @var callable $t
 * @var callable $e
 * @var callable $money
 * @var array<string,mixed> $auction
 * @var array<string,mixed>|null $currentUser
 * @var string $csrfToken
 */
$role = is_array($currentUser) ? (string) ($currentUser['role'] ?? '') : '';
$isOwn = is_array($currentUser) && (int) ($currentUser['id'] ?? 0) === (int) ($auction['sellerId'] ?? -1);
$viewerStatus = (string) ($auction['viewerStatus'] ?? 'none');
$auctionId = (int) $auction['id'];

$panelState = match (true) {
    $status === 'ended' => 'ended',
    $status === 'scheduled' => 'scheduled',
    !is_array($currentUser) => 'guest',
    $role !== 'BUYER' || $isOwn => 'cannot',
    default => 'buyer',
};
$modifier = match (true) {
    $panelState === 'ended' => ' bid-panel--ended',
    $panelState === 'guest' => ' bid-panel--guest',
    $viewerStatus === 'leading' => ' bid-panel--leading',
    $viewerStatus === 'outbid' => ' bid-panel--outbid',
    default => '',
};
// MAKIETA (ekran 06): $bidError ustawia show.html.php; pole kwoty dostaje aria-invalid i komunikat.
$bidError = $bidError ?? null;
$hasError = $bidError !== null && $panelState === 'buyer';
$isLeading = $viewerStatus === 'leading' && $panelState === 'buyer';
// Ekran 07: przebity i znana wlasna oferta -> etykieta, kwota i przycisk "Przebij ofertę".
$isOutbid = $viewerStatus === 'outbid' && $panelState === 'buyer' && !$hasError && isset($auction['viewerBid']);
$placeLabel = $isOutbid ? $t('bids.outbid_cta', 'Przebij ofertę') : $t('bids.place', 'Złóż ofertę');
if ($hasError) {
    $modifier .= ' bid-panel--error';
}
$cannotText = $isOwn
    ? $t('bids.own_auction', 'Nie możesz licytować własnej aukcji.')
    : $t('bids.cannot_bid', 'Nie możesz licytować tej aukcji.');
$antiSniping = $t('bids.anti_sniping', 'Oferta złożona w ostatnich 30 sekundach przedłuża aukcję o 30 sekund.');
?>
<section class="bid-panel<?= $modifier ?>" aria-labelledby="bid-panel-title"
         data-auction-id="<?= $e((string) $auctionId) ?>"
         data-state-url="/api/auctions/<?= $e((string) $auctionId) ?>/state"
         data-bid-url="/api/auctions/<?= $e((string) $auctionId) ?>/bids"
         data-ends-at="<?= $e($auction['endsAt']) ?>"
         data-server-time="<?= $e($auction['serverTime']) ?>"
         data-seconds-left="<?= $e((string) $auction['secondsLeft']) ?>"
         data-status="<?= $e($status) ?>"
         data-viewer-state="<?= $e($panelState) ?>"
         data-viewer-status="<?= $e($viewerStatus) ?>"
         data-price="<?= $e((string) ((int) $auction['price'] * 100)) ?>"
         data-min-increment="<?= $e((string) ((int) $auction['minIncrement'] * 100)) ?>"
         data-min-next-bid="<?= $e((string) ((int) $auction['minNextBid'] * 100)) ?>">
    <h2 class="visually-hidden" id="bid-panel-title"><?= $e($t('bids.panel', 'Panel licytacji')) ?></h2>

    <?php if ($panelState === 'ended'): ?>
        <p class="bid-panel__timer"><?= $e($t('auction.ended', 'Aukcja zakończona')) ?></p>
    <?php elseif ($panelState === 'scheduled'): ?>
        <p class="bid-panel__timer"><?= $e($t('auction.starts', 'Start')) ?>: <?= $e($auction['startsAtLabel'] ?? '') ?></p>
    <?php else: ?>
        <p class="bid-panel__timer">
            <?= $e($t('auction.remaining', 'Pozostało')) ?>:
            <time data-countdown aria-live="off"><?= $e($auction['timeLeft']) ?></time>
        </p>
        <p class="bid-panel__end"><?= $e($t('auction.ends', 'Koniec')) ?>: <span data-ends-at-label><?= $e($auction['endsAtLabel']) ?></span></p>
    <?php endif; ?>

    <p class="bid-panel__label" data-price-label>
        <?= $e($panelState === 'ended' ? $t('auction.final_price', 'Cena końcowa') : $t('auction.highest_bid', 'Aktualnie najwyższa oferta')) ?>
    </p>
    <p class="bid-panel__price" aria-live="polite" data-current-price><?= $e($money($auction['price'])) ?></p>

    <?php if ($panelState === 'buyer' || $panelState === 'ended'): ?>
        <div data-leading-block tabindex="-1"<?= $viewerStatus === 'leading' ? '' : ' hidden' ?>>
            <p class="bid-panel__leader status-label status-label--leading"><?= $e($t('bids.you_lead', 'Prowadzisz w licytacji')) ?></p>
            <p class="bid-panel__leader-name"><?= $e($t('bids.leader_is', 'Lider licytacji')) ?>: <?= $e($t('bids.you', 'Ty')) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($panelState === 'buyer'): ?>
        <div data-outbid-block<?= $isOutbid ? '' : ' hidden' ?>>
            <p class="bid-panel__leader status-label status-label--outbid"><?= $e($t('bids.outbid', 'Przebita')) ?></p>
            <p class="bid-panel__leader-name"><?= $e($t('bids.outbid_yours', 'Przebito Twoje')) ?> <span data-outbid-amount><?= $isOutbid ? $e($money($auction['viewerBid'])) : '' ?></span></p>
        </div>

        <div data-leading-wait<?= $isLeading ? '' : ' hidden' ?>>
            <p class="bid-panel__step"><?= $e($t('bids.next_min', 'Kolejne min')) ?>: <span data-min-next-bid><?= $e($money($auction['minNextBid'])) ?></span></p>
            <button class="button button--accent button--block bid-panel__submit" type="button" disabled>
                <?= $e($t('bids.leading_wait', 'Prowadzisz – poczekaj na przebicie')) ?>
            </button>
        </div>

        <div data-bid-controls<?= $isLeading ? ' hidden' : '' ?>>
        <p class="bid-panel__step"><?= $e($t('bids.min_step', 'Minimalny krok')) ?>: +<span data-min-increment><?= $e($money($auction['minIncrement'])) ?></span></p>

        <form class="bid-panel__form" method="post" action="/auctions/<?= $e((string) $auctionId) ?>" data-bid-form novalidate>
            <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
            <div class="bid-panel__quick" role="group" aria-label="<?= $e($t('bids.quick', 'Szybkie zwiększenie oferty')) ?>">
                <?php foreach ([1, 2, 4] as $multiplier): ?>
                    <button class="button button--ghost" type="button" data-quick-add="<?= $e((string) ($auction['minIncrement'] * $multiplier)) ?>">
                        +<?= $e($money($auction['minIncrement'] * $multiplier)) ?>
                        <span class="bid-panel__quick-result">(<?= $e($money($auction['price'] + $auction['minIncrement'] * $multiplier)) ?>)</span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="form-field">
                <div class="form-field__row">
                    <label class="form-field__label" for="bid-amount"><?= $e($t('bids.amount', 'Twoja oferta (zł)')) ?></label>
                    <?php if ($hasError): ?>
                        <span class="form-field__badge"><?= $e($t('bids.amount_error', 'Błąd kwoty')) ?></span>
                    <?php endif; ?>
                </div>
                <div class="input-group">
                    <button class="button button--icon" type="button" data-step="-1" aria-label="<?= $e($t('bids.decrease', 'Zmniejsz kwotę')) ?>">−</button>
                    <input class="form-field__control" id="bid-amount" name="amount" type="number" inputmode="numeric" required
                           min="<?= $e((string) $auction['minNextBid']) ?>" step="<?= $e((string) $auction['minIncrement']) ?>"
                           value="<?= $e((string) $auction['minNextBid']) ?>"
                           <?php if ($hasError): ?>aria-invalid="true" <?php endif; ?>aria-describedby="<?= $hasError ? 'bid-amount-error ' : '' ?>bid-amount-hint bid-message">
                    <?php if ($hasError): ?>
                        <button class="button button--link input-group__clear" type="button" data-clear-amount><?= $e($t('common.clear', 'Wyczyść')) ?></button>
                    <?php endif; ?>
                    <button class="button button--icon" type="button" data-step="1" aria-label="<?= $e($t('bids.increase', 'Zwiększ kwotę')) ?>">+</button>
                </div>
                <?php if ($hasError): ?>
                    <p class="form-field__error" id="bid-amount-error"><?= $e($t('bids.must_exceed', 'Oferta musi być wyższa niż')) ?> <?= $e($money($auction['price'])) ?> (<?= $e($t('bids.min_step_lc', 'minimalny krok')) ?> <?= $e($money($auction['minIncrement'])) ?>)</p>
                    <p class="bid-panel__suggested"><?= $e($t('bids.suggested', 'Sugerowana minimalna oferta')) ?>: <strong><?= $e($money($auction['minNextBid'])) ?></strong></p>
                <?php endif; ?>
                <p class="form-field__error" id="bid-amount-js-error" role="alert" data-amount-error hidden></p>
                <p class="form-field__hint" id="bid-amount-hint"><?= $e($t('bids.min', 'Minimum')) ?>: <span data-min-next-bid><?= $e($money($auction['minNextBid'])) ?></span></p>
            </div>
            <button class="button button--accent button--block bid-panel__submit" type="submit" data-bid-open data-place-label>
                <?= $e($placeLabel) ?>
            </button>
            <noscript><p class="alert alert--warning"><?= $e($t('bids.noscript', 'Licytacja wymaga JavaScript.')) ?></p></noscript>
        </form>
        </div>
    <?php elseif ($panelState === 'guest'): ?>
        <a class="button button--accent button--block bid-panel__submit" href="/login?next=<?= $e(rawurlencode('/auctions/' . $auctionId)) ?>">
            <?= $e($t('bids.login_to_bid', 'Zaloguj się, aby licytować')) ?>
        </a>
    <?php elseif ($panelState === 'cannot'): ?>
        <p class="alert alert--info" role="note"><?= $e($cannotText) ?></p>
    <?php elseif ($panelState === 'ended' && $viewerStatus === 'won'): ?>
        <a class="button button--accent button--block" href="/auctions/<?= $e((string) $auctionId) ?>/won"><?= $e($t('bids.see_win', 'Zobacz wygraną')) ?></a>
    <?php endif; ?>

    <?php if ($panelState === 'buyer'): ?>
        <p class="alert alert--danger" role="alert" data-outbid-alert hidden><?= $e($t('bids.outbid_title', 'Zostałeś przebity')) ?></p>
    <?php endif; ?>
    <div class="bid-panel__message" id="bid-message" role="status" data-bid-message hidden></div>

    <p class="alert alert--warning bid-panel__rule" data-anti-sniping-rule<?= $panelState === 'ended' ? ' hidden' : '' ?>><?= $e($antiSniping) ?></p>

    <?php
    // Teksty dla public/js/live-bidding.js (kontrakt: klucz => tekst w biezacym jezyku, bez kodowania w JS).
    $bidI18n = [
        'you' => $t('bids.you', 'Ty'),
        'bidder' => $t('bids.bidder', 'Licytujący'),
        'leader' => $t('bids.leader', 'LIDER'),
        'outbid' => $t('bids.outbid', 'Przebita'),
        'place' => $t('bids.place', 'Złóż ofertę'),
        'outbidCta' => $t('bids.outbid_cta', 'Przebij ofertę'),
        'leadingWait' => $t('bids.leading_wait', 'Prowadzisz – poczekaj na przebicie'),
        'sending' => $t('bids.sending', 'Wysyłanie oferty…'),
        'retry' => $t('bids.retry', 'Spróbuj ponownie'),
        'networkError' => $t('bids.network_error', 'Brak połączenia. Oferta nie została wysłana.'),
        'offline' => $t('bids.offline', 'Brak połączenia. Ponawiam próbę…'),
        'loginToBid' => $t('bids.login_to_bid', 'Zaloguj się, aby licytować'),
        'outbidTitle' => $t('bids.outbid_title', 'Zostałeś przebity'),
        'ended' => $t('auction.ended', 'Aukcja zakończona'),
        'finalPrice' => $t('auction.final_price', 'Cena końcowa'),
        'extended' => $t('bids.extended', 'Aukcja przedłużona o 30 s'),
        'amountInvalid' => $t('bid.error.amount_invalid', 'Podaj poprawną kwotę oferty.'),
        'tooLow' => $t('bid.error.too_low', 'Oferta jest za niska. Minimalna oferta: {min}.'),
        'serverError' => $t('error.server', 'Wystąpił nieoczekiwany błąd. Spróbuj ponownie za chwilę.'),
        'day' => $t('time.day_short', 'd'),
        'hour' => $t('time.hour_short', 'h'),
        'minute' => $t('time.minute_short', 'min'),
        'second' => $t('time.second_short', 's'),
    ];
    ?>
    <script type="application/json" id="bid-i18n"><?= json_encode($bidI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR) ?></script>
</section>

<?php if ($panelState === 'buyer'): ?>
    <div class="bid-bar" data-bid-bar>
        <p class="bid-bar__info">
            <span><?= $e($t('auction.remaining', 'Pozostało')) ?>: <time data-countdown aria-live="off"><?= $e($auction['timeLeft']) ?></time></span>
            <span><?= $e($t('auction.current', 'Aktualna')) ?>: <strong data-current-price><?= $e($money($auction['price'])) ?></strong></span>
        </p>
        <button class="button button--accent button--block bid-bar__submit" type="button" disabled data-bar-leading<?= $isLeading ? '' : ' hidden' ?>>
            <?= $e($t('bids.leading_wait', 'Prowadzisz – poczekaj na przebicie')) ?>
        </button>
        <button class="button button--accent button--block bid-bar__submit" type="button" data-bid-open-bar<?= $isLeading ? ' hidden' : '' ?>>
            <span data-place-label><?= $e($placeLabel) ?></span> (<span data-min-next-bid><?= $e($money($auction['minNextBid'])) ?></span>)
        </button>
        <p class="bid-bar__rule"><?= $e($antiSniping) ?></p>
    </div>
<?php endif; ?>
