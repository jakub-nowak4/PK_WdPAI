<?php
/**
 * Karta aukcji na liscie.
 * @var callable $t
 * @var callable $e
 * @var array<string,mixed> $auction id, title, seller, origin, condition, image|null, mileage, gearbox,
 *                                    fuel, drive, price (pelne zl), bids, timeLeft, urgent, status
 */
$auctionUrl = '/auctions/' . (int) $auction['id'];
$isScheduled = ($auction['status'] ?? 'active') === 'scheduled';
?>
<article class="auction-card">
    <div class="auction-card__media">
        <?php if (!empty($auction['image'])): ?>
            <img src="<?= $e($auction['image']) ?>" alt="<?= $e($auction['title']) ?>" loading="lazy">
        <?php else: ?>
            <span class="auction-card__placeholder" role="img" aria-label="<?= $e($t('auction.no_photo', 'Brak zdjęcia')) ?>">🚗</span>
        <?php endif; ?>
        <span class="auction-card__origin"><?= $e($auction['origin']) ?></span>
        <span class="auction-card__badge"><?= $e($auction['condition']) ?></span>
    </div>

    <div class="auction-card__body">
        <h3 class="auction-card__title">
            <a href="<?= $e($auctionUrl) ?>"><?= $e($auction['title']) ?></a>
        </h3>
        <p class="auction-card__seller">
            <?= $e($t('auction.seller', 'Sprzedaje')) ?>: <strong><?= $e($auction['seller']) ?></strong>
        </p>
        <p class="status-label status-label--verified"><?= $e($t('auction.verified', 'Zweryfikowana firma')) ?></p>

        <dl class="auction-card__specs">
            <div><dt class="visually-hidden"><?= $e($t('auction.mileage', 'Przebieg')) ?></dt><dd><?= $e($auction['mileage']) ?></dd></div>
            <div><dt class="visually-hidden"><?= $e($t('auction.gearbox', 'Skrzynia biegów')) ?></dt><dd><?= $e($auction['gearbox']) ?></dd></div>
            <div><dt class="visually-hidden"><?= $e($t('auction.fuel', 'Paliwo i silnik')) ?></dt><dd><?= $e($auction['fuel']) ?></dd></div>
            <div><dt class="visually-hidden"><?= $e($t('auction.drive', 'Napęd')) ?></dt><dd><?= $e($auction['drive']) ?></dd></div>
        </dl>

        <div class="auction-card__footer">
            <p class="auction-card__price-label"><?= $e($t('auction.current_bid', 'Aktualna oferta')) ?></p>
            <p class="auction-card__price"><?= $e(number_format((int) $auction['price'], 0, ',', "\u{00A0}")) ?>&nbsp;zł</p>
            <p class="auction-card__bids"><?= $e((string) $auction['bids']) ?> <?= $e($t('auction.bids', 'ofert')) ?></p>
            <p class="auction-card__time<?= !empty($auction['urgent']) ? ' auction-card__time--urgent' : '' ?>">
                <span class="visually-hidden"><?= $e($t('auction.time_left', 'Do końca')) ?>:</span>
                <?= $e($auction['timeLeft']) ?>
            </p>
            <?php if ($isScheduled): ?>
                <span class="status-label status-label--scheduled"><?= $e($t('auction.soon', 'Wkrótce')) ?></span>
            <?php else: ?>
                <a class="button button--accent button--block auction-card__cta" href="<?= $e($auctionUrl) ?>"
                   aria-label="<?= $e($t('auction.bid', 'Licytuj')) ?>: <?= $e($auction['title']) ?>">
                    <?= $e($t('auction.bid', 'Licytuj')) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</article>
