<?php
/**
 * Szczegoly aukcji (ekran 03).
 * Oczekiwane zmienne od kontrolera (na razie brak, uzywana jest MAKIETA z partials/mock-auction.php):
 * $auction (tablica jak w mocku), $bids (maks. 10 ostatnich ofert); opcjonalnie $currentUser, $csrfToken, $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$currentUser = $currentUser ?? null;
$csrfToken = (string) ($csrfToken ?? '');

$isMock = false;
if (!isset($auction)) {
    $mock = require __DIR__ . '/../partials/mock-auction.php';
    $auction = $mock['auction'];
    $bids = $mock['bids'];
    $isMock = true;
}
$bids = $bids ?? [];
$money = static fn (int|float $amount): string => number_format((int) $amount, 0, ',', "\u{00A0}") . "\u{00A0}zł";
$status = (string) ($auction['status'] ?? 'active');
$specLabels = [
    'year' => $t('auction.year', 'Rok produkcji'),
    'mileage' => $t('auction.mileage', 'Przebieg'),
    'fuel' => $t('auction.fuel', 'Paliwo i silnik'),
    'gearbox' => $t('auction.gearbox', 'Skrzynia biegów'),
    'origin' => $t('auction.origin', 'Kraj pochodzenia'),
];
?>
<main class="page page--auction" id="main-content">
    <nav class="breadcrumbs" aria-label="<?= $e($t('nav.breadcrumbs', 'Okruszki')) ?>">
        <ol class="breadcrumbs__list">
            <li><a href="/auctions"><?= $e($t('nav.auctions', 'Aukcje')) ?></a></li>
            <li><a href="/auctions?brand=<?= $e(rawurlencode(strtolower((string) $auction['brand']))) ?>"><?= $e($auction['brand']) ?></a></li>
            <li aria-current="page"><?= $e($auction['title']) ?></li>
        </ol>
    </nav>

    <?php if ($isMock): ?>
        <p class="alert alert--info" role="note">MAKIETA: dane przykładowe, nie pochodzą z bazy.</p>
    <?php endif; ?>

    <div class="auction-detail">
        <div class="auction-detail__main">
            <header class="auction-detail__header">
                <?php if ($status === 'active'): ?>
                    <p class="status-label status-label--active"><span class="status-label__dot" aria-hidden="true"></span><?= $e($t('auction.live', 'Licytacja na żywo')) ?></p>
                <?php elseif ($status === 'scheduled'): ?>
                    <p class="status-label status-label--scheduled"><?= $e($t('auction.soon', 'Wkrótce')) ?></p>
                <?php else: ?>
                    <p class="status-label status-label--ended"><?= $e($t('auction.ended', 'Aukcja zakończona')) ?></p>
                <?php endif; ?>
                <p class="auction-detail__lot"><?= $e($t('auction.number', 'Aukcja')) ?> #<?= $e((string) $auction['id']) ?></p>
                <h1 class="auction-detail__title"><?= $e($auction['title']) ?></h1>
                <p class="auction-detail__vin">
                    <?= $e($t('auction.vin', 'VIN')) ?>: <code id="vin-value"><?= $e($auction['vin']) ?></code>
                    <button class="button button--ghost" type="button" data-copy-target="vin-value"><?= $e($t('auction.copy_vin', 'Kopiuj VIN')) ?></button>
                </p>
                <ul class="auction-detail__tags">
                    <li class="status-label"><?= $e($auction['origin']) ?></li>
                    <li class="status-label"><?= $e($auction['condition']) ?></li>
                </ul>
            </header>

            <section class="gallery" aria-label="<?= $e($t('auction.gallery', 'Zdjęcia pojazdu')) ?>">
                <ul class="gallery__list">
                    <?php foreach ($auction['images'] as $image): ?>
                        <li class="gallery__item">
                            <figure>
                                <?php if (!empty($image['src'])): ?>
                                    <img src="<?= $e($image['src']) ?>" alt="<?= $e($image['alt']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="gallery__placeholder" role="img" aria-label="<?= $e($image['alt']) ?>">🚗</span>
                                <?php endif; ?>
                                <figcaption><?= $e($image['caption']) ?></figcaption>
                            </figure>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="seller-card" aria-labelledby="seller-title">
                <span class="seller-card__avatar" aria-hidden="true"><?= $e($auction['seller']['initials']) ?></span>
                <div class="seller-card__body">
                    <h2 class="seller-card__name" id="seller-title"><?= $e($auction['seller']['name']) ?></h2>
                    <p class="status-label status-label--verified"><?= $e($t('auction.verified', 'Zweryfikowana firma')) ?></p>
                    <p class="seller-card__meta"><?= $e($t('auction.member_since', 'Na platformie od')) ?> <?= $e($auction['seller']['since']) ?> r. • <?= $e((string) $auction['seller']['finished']) ?> <?= $e($t('auction.finished', 'sfinalizowanych aukcji')) ?></p>
                </div>
            </section>

            <section class="auction-detail__section" aria-labelledby="specs-title">
                <h2 id="specs-title"><?= $e($t('auction.specs', 'Dane techniczne')) ?></h2>
                <dl class="spec-grid">
                    <?php foreach ($specLabels as $key => $label): ?>
                        <?php if (!empty($auction['specs'][$key])): ?>
                            <div class="spec-grid__item">
                                <dt class="spec-grid__label"><?= $e($label) ?></dt>
                                <dd class="spec-grid__value"><?= $e($auction['specs'][$key]) ?></dd>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </dl>
            </section>

            <section class="auction-detail__section" aria-labelledby="desc-title">
                <h2 id="desc-title"><?= $e($t('auction.description', 'Opis pojazdu')) ?></h2>
                <?php foreach ($auction['description'] as $paragraph): ?>
                    <p><?= $e($paragraph) ?></p>
                <?php endforeach; ?>
            </section>
        </div>

        <aside class="auction-detail__aside" aria-label="<?= $e($t('auction.bidding', 'Licytacja')) ?>">
            <?php require __DIR__ . '/../partials/bid-panel.html.php'; ?>
            <?php require __DIR__ . '/../partials/bid-history.html.php'; ?>
        </aside>
    </div>

    <?php require __DIR__ . '/../partials/bid-confirm-dialog.html.php'; ?>
</main>
