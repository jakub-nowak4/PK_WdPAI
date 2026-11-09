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
         data-viewer-state="<?= $e($panelState) ?>">
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
        <p class="bid-panel__end"><?= $e($t('auction.ends', 'Koniec')) ?>: <?= $e($auction['endsAtLabel']) ?></p>
    <?php endif; ?>

    <p class="bid-panel__label">
        <?= $e($panelState === 'ended' ? $t('auction.final_price', 'Cena końcowa') : $t('auction.highest_bid', 'Aktualnie najwyższa oferta')) ?>
    </p>
    <p class="bid-panel__price" aria-live="polite" data-current-price><?= $e($money($auction['price'])) ?></p>
    <?php if ($viewerStatus === 'leading'): ?>
        <p class="bid-panel__leader"><?= $e($t('bids.you_lead', 'Prowadzisz w licytacji')) ?></p>
    <?php endif; ?>

    <?php if ($panelState === 'buyer'): ?>
        <p class="bid-panel__step"><?= $e($t('bids.min_step', 'Minimalny krok')) ?>: +<?= $e($money($auction['minIncrement'])) ?></p>

        <form class="bid-panel__form" method="post" action="/auctions/<?= $e((string) $auctionId) ?>" data-bid-form novalidate>
            <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
            <div class="bid-panel__quick" role="group" aria-label="<?= $e($t('bids.quick', 'Szybkie zwiększenie oferty')) ?>">
                <?php foreach ([1, 2, 4] as $multiplier): ?>
                    <button class="button button--ghost" type="button" data-quick-add="<?= $e((string) ($auction['minIncrement'] * $multiplier)) ?>">
                        +<?= $e($money($auction['minIncrement'] * $multiplier)) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="form-field">
                <label class="form-field__label" for="bid-amount"><?= $e($t('bids.amount', 'Twoja oferta (zł)')) ?></label>
                <div class="input-group">
                    <button class="button button--icon" type="button" data-step="-1" aria-label="<?= $e($t('bids.decrease', 'Zmniejsz kwotę')) ?>">−</button>
                    <input class="form-field__control" id="bid-amount" name="amount" type="number" inputmode="numeric" required
                           min="<?= $e((string) $auction['minNextBid']) ?>" step="<?= $e((string) $auction['minIncrement']) ?>"
                           value="<?= $e((string) $auction['minNextBid']) ?>" aria-describedby="bid-amount-hint bid-message">
                    <button class="button button--icon" type="button" data-step="1" aria-label="<?= $e($t('bids.increase', 'Zwiększ kwotę')) ?>">+</button>
                </div>
                <p class="form-field__hint" id="bid-amount-hint"><?= $e($t('bids.min', 'Minimum')) ?>: <span data-min-next-bid><?= $e($money($auction['minNextBid'])) ?></span></p>
            </div>
            <button class="button button--accent button--block bid-panel__submit" type="submit" data-bid-open>
                <?= $e($t('bids.place', 'Złóż ofertę')) ?>
            </button>
            <noscript><p class="alert alert--warning"><?= $e($t('bids.noscript', 'Licytacja wymaga JavaScript.')) ?></p></noscript>
        </form>
    <?php elseif ($panelState === 'guest'): ?>
        <a class="button button--accent button--block bid-panel__submit" href="/login?next=<?= $e(rawurlencode('/auctions/' . $auctionId)) ?>">
            <?= $e($t('bids.login_to_bid', 'Zaloguj się, aby licytować')) ?>
        </a>
    <?php elseif ($panelState === 'cannot'): ?>
        <p class="alert alert--info" role="note"><?= $e($cannotText) ?></p>
    <?php elseif ($panelState === 'ended' && $viewerStatus === 'won'): ?>
        <a class="button button--accent button--block" href="/auctions/<?= $e((string) $auctionId) ?>/won"><?= $e($t('bids.see_win', 'Zobacz wygraną')) ?></a>
    <?php endif; ?>

    <div class="bid-panel__message" id="bid-message" role="status" data-bid-message hidden></div>

    <?php if ($panelState !== 'ended'): ?>
        <p class="alert alert--warning bid-panel__rule"><?= $e($antiSniping) ?></p>
    <?php endif; ?>
</section>

<?php if ($panelState === 'buyer'): ?>
    <div class="bid-bar" data-bid-bar>
        <p class="bid-bar__info">
            <span><?= $e($t('auction.remaining', 'Pozostało')) ?>: <time data-countdown aria-live="off"><?= $e($auction['timeLeft']) ?></time></span>
            <span><?= $e($t('auction.current', 'Aktualna')) ?>: <strong data-current-price><?= $e($money($auction['price'])) ?></strong></span>
        </p>
        <button class="button button--accent button--block bid-bar__submit" type="button" data-bid-open-bar>
            <?= $e($t('bids.place', 'Złóż ofertę')) ?> (<?= $e($money($auction['minNextBid'])) ?>)
        </button>
        <p class="bid-bar__rule"><?= $e($antiSniping) ?></p>
    </div>
<?php endif; ?>
