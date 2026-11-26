<?php
/**
 * Karta aukcji na liscie. $auction to App\Model\AuctionSummary (dane z kontrolera, docs/api.md 1.8)
 * albo tablica z partials/mock-auctions.php (MAKIETA, do czasu kontrolera T-028).
 * @var callable $t
 * @var callable $e
 * @var \App\Model\AuctionSummary|array<string,mixed> $auction
 * @var \DateTimeImmutable|null $now czas serwera (opcjonalnie; domyslnie biezacy)
 */
require_once __DIR__ . '/auction-card-data.php';
$card = auctionCardData($auction, $t, $now ?? new \DateTimeImmutable());
$auctionUrl = '/auctions/' . $card['id'];
$isScheduled = $card['status'] === 'scheduled';
// Parametry bez danych (np. naped, stan pojazdu) nie sa pokazywane.
$specs = array_filter([
    [$t('auction.mileage', 'Przebieg'), $card['mileage']],
    [$t('auction.gearbox', 'Skrzynia biegów'), $card['gearbox']],
    [$t('auction.fuel', 'Paliwo i silnik'), $card['fuel']],
    [$t('auction.drive', 'Napęd'), $card['drive']],
], static fn (array $spec): bool => $spec[1] !== null);
?>
<article class="auction-card">
    <div class="auction-card__media">
        <?php if ($card['image'] !== null): ?>
            <img src="<?= $e($card['image']) ?>" alt="<?= $e($card['title']) ?>" loading="lazy">
        <?php else: ?>
            <span class="auction-card__placeholder" role="img" aria-label="<?= $e($t('auction.no_photo', 'Brak zdjęcia')) ?>">🚗</span>
        <?php endif; ?>
        <span class="auction-card__origin"><?= $e($card['origin']) ?></span>
        <?php if ($card['condition'] !== null): ?>
            <span class="auction-card__badge"><?= $e($card['condition']) ?></span>
        <?php endif; ?>
    </div>

    <div class="auction-card__body">
        <h3 class="auction-card__title">
            <a href="<?= $e($auctionUrl) ?>"><?= $e($card['title']) ?></a>
        </h3>
        <p class="auction-card__seller">
            <?= $e($t('auction.seller', 'Sprzedaje')) ?>: <strong><?= $e($card['seller']) ?></strong>
        </p>
        <p class="status-label status-label--verified"><?= $e($t('auction.verified', 'Zweryfikowana firma')) ?></p>

        <dl class="auction-card__specs">
            <?php foreach ($specs as [$label, $value]): ?>
                <div><dt class="visually-hidden"><?= $e($label) ?></dt><dd><?= $e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl>

        <div class="auction-card__footer">
            <p class="auction-card__price-label"><?= $e($t('auction.current_bid', 'Aktualna oferta')) ?></p>
            <p class="auction-card__price"><?= $e($card['price']) ?>&nbsp;zł</p>
            <p class="auction-card__bids"><?= $e((string) $card['bids']) ?> <?= $e($t('auction.bids', 'ofert')) ?></p>
            <p class="auction-card__time<?= $card['urgent'] ? ' auction-card__time--urgent' : '' ?>">
                <span class="visually-hidden"><?= $e($isScheduled ? $t('auction.starts_label', 'Początek aukcji') : $t('auction.time_left', 'Do końca')) ?>:</span>
                <?= $e($card['timeLeft']) ?>
            </p>
            <?php if ($isScheduled): ?>
                <span class="status-label status-label--scheduled"><?= $e($t('auction.soon', 'Wkrótce')) ?></span>
            <?php else: ?>
                <a class="button button--accent button--block auction-card__cta" href="<?= $e($auctionUrl) ?>"
                   aria-label="<?= $e($t('auction.bid', 'Licytuj')) ?>: <?= $e($card['title']) ?>">
                    <?= $e($t('auction.bid', 'Licytuj')) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</article>
