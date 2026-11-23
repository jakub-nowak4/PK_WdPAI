<?php
/**
 * Szczegoly aukcji (ekran 03), dane z bazy (T-029/T-030).
 * Zmienne od kontrolera: $auctionId, $auctionSummary (App\Model\AuctionSummary), $images (list<AuctionImage>),
 * $recentBids (list<PublicBid>), $auctionStatus (App\Model\AuctionStatus), $secondsLeft (int), $serverTime
 * (DateTimeImmutable); opcjonalnie $previewState (MAKIETA ekranow 04-07, ?state=), $currentUser, $csrfToken, $t, $e.
 * Z obiektow powstaje tablica $auction w ksztalcie oczekiwanym przez partiale (kwoty w pelnych zlotych).
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$currentUser = $currentUser ?? null;
$csrfToken = (string) ($csrfToken ?? '');

$zloty = static fn (\App\Model\Money $amount): int => intdiv($amount->grosze(), 100);
$record = $auctionSummary->getAuction();
$status = $auctionStatus->value;
$viewerId = is_array($currentUser) ? (int) ($currentUser['id'] ?? 0) : 0;
$viewerStatus = 'none';
if ($viewerId > 0 && $auctionSummary->getLeaderUserId() === $viewerId) {
    $viewerStatus = $status === 'ended' ? 'won' : 'leading';
}

$timeLeft = static function (int $seconds) use ($t): string {
    $seconds = max(0, $seconds);
    $parts = [];
    if ($seconds >= 86400) {
        $parts[] = intdiv($seconds, 86400) . $t('time.day_short', 'd');
    }
    if ($seconds >= 3600) {
        $parts[] = intdiv($seconds % 86400, 3600) . $t('time.hour_short', 'h');
    }
    if ($seconds >= 60) {
        $parts[] = intdiv($seconds % 3600, 60) . $t('time.minute_short', 'min');
    }
    $parts[] = ($seconds % 60) . $t('time.second_short', 's');

    return implode(' ', $parts);
};

$country = $record->getOriginCountry();
$countryLabel = $t('country.' . $country, $country);
$fuel = $record->getFuel()->value;
$gearbox = $record->getTransmission()->value;
$companyName = $auctionSummary->getCompanyName();

$bids = [];
$recentList = array_values($recentBids);
foreach ($recentList as $index => $bid) {
    $older = $recentList[$index + 1] ?? null;
    $diff = null;
    if ($older !== null) {
        $diff = $zloty($bid->getAmount()) - $zloty($older->getAmount());
    } elseif ($auctionSummary->getBidCount() === count($recentList)) {
        $diff = $zloty($bid->getAmount()) - $zloty($record->getStartingPrice());
    }
    $bids[] = [
        'bidder' => $t('bids.bidder', 'Licytujący') . ' ' . $bid->getBidderNumber(),
        'isYou' => $bid->isYou(),
        'amount' => $zloty($bid->getAmount()),
        'diff' => $diff,
        'when' => $bid->getPlacedAt()->format('d.m.Y H:i'),
    ];
}

$auction = [
    'id' => $record->getId(),
    'title' => $record->getTitle(),
    'brand' => $auctionSummary->getBrandName(),
    'brandSlug' => $auctionSummary->getBrandSlug(),
    'vin' => $record->getVin(),
    'origin' => $countryLabel,
    'status' => $status,
    'sellerId' => -1, // wlasciciela sprawdza serwer (T-032); konto firmy i tak nie licytuje
    'price' => $zloty($auctionSummary->getCurrentPrice()),
    'minIncrement' => $zloty($record->getMinIncrement()),
    'minNextBid' => $zloty($auctionSummary->getMinNextBid()),
    'startPrice' => $zloty($record->getStartingPrice()),
    'bidCount' => $auctionSummary->getBidCount(),
    'endsAt' => $record->getEndsAt()->format(DATE_ATOM),
    'endsAtLabel' => $record->getEndsAt()->format('d.m.Y, H:i'),
    'startsAtLabel' => $record->getStartsAt()->format('d.m.Y, H:i'),
    'serverTime' => $serverTime->format(DATE_ATOM),
    'secondsLeft' => $secondsLeft,
    'timeLeft' => $timeLeft($secondsLeft),
    'seller' => ['name' => $companyName, 'initials' => mb_strtoupper(mb_substr($companyName, 0, 2))],
    'specs' => [
        'year' => (string) $record->getYear(),
        'mileage' => number_format($record->getMileageKm(), 0, ',', "\u{00A0}") . "\u{00A0}km",
        'fuel' => $t('fuel.' . $fuel, $fuel),
        'gearbox' => $t('transmission.' . $gearbox, $gearbox),
        'origin' => $countryLabel,
    ],
    'description' => array_values(array_filter(
        array_map('trim', preg_split('/\R{2,}/', $record->getDescription()) ?: []),
        static fn (string $paragraph): bool => $paragraph !== '',
    )),
    'viewerStatus' => $viewerStatus,
];
$isPreview = isset($previewState) && $previewState !== null;
$previewState = $previewState ?? null;
$bidError = null;
if ($isPreview && $previewState === 'leading') {
    // MAKIETA (ekran 05): kupujacy prowadzi po zlozeniu oferty 90 000 zł.
    $auction['price'] = 90000;
    $auction['minNextBid'] = 90500;
    $auction['bidCount'] = 15;
    $auction['viewerStatus'] = 'leading';
    array_unshift($bids, ['bidder' => '', 'isYou' => true, 'amount' => 90000, 'diff' => 500, 'when' => 'przed chwilą', 'badge' => 'leader']);
} elseif ($isPreview && $previewState === 'outbid') {
    // MAKIETA (ekran 07): kupujacy zostal przebity; jego najwyzsza oferta to 90 000 zł.
    $auction['price'] = 92000;
    $auction['minNextBid'] = 92500;
    $auction['bidCount'] = 16;
    $auction['viewerStatus'] = 'outbid';
    $auction['viewerBid'] = 90000;
    $bids = [
        ['bidder' => 'Licytujący ***482', 'isYou' => false, 'amount' => 92000, 'diff' => 2000, 'when' => 'przed chwilą', 'badge' => 'leader'],
        ['bidder' => '', 'isYou' => true, 'amount' => 90000, 'diff' => 500, 'when' => '10 min temu', 'badge' => 'outbid'],
        ['bidder' => 'Licytujący 3', 'isYou' => false, 'amount' => 89500, 'diff' => 500, 'when' => '12 min temu'],
    ];
} elseif ($isPreview && $previewState === 'error') {
    // MAKIETA (ekran 06): oferta za niska, kupujacy zostal przebity.
    $auction['price'] = 92000;
    $auction['minNextBid'] = 92500;
    $auction['bidCount'] = 16;
    $auction['viewerStatus'] = 'outbid';
    $bids = [
        ['bidder' => 'Licytujący ***482', 'isYou' => false, 'amount' => 92000, 'diff' => 2000, 'when' => 'przed chwilą', 'badge' => 'leader'],
        ['bidder' => '', 'isYou' => true, 'amount' => 90000, 'diff' => 500, 'when' => '10 min temu', 'badge' => 'outbid'],
    ];
    $bidError = [
        'title' => $t('bids.error_title', 'Nie udało się złożyć oferty'),
        'text' => $t('bids.error_too_low', 'Wprowadzona kwota oferty jest zbyt niska w stosunku do aktualnej najwyższej licytacji.'),
    ];
}
$money = static fn (int|float $amount): string => number_format((int) $amount, 0, ",", "\u{00A0}") . "\u{00A0}zł";
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
            <li><a href="/auctions?brand=<?= $e(rawurlencode($auction['brandSlug'])) ?>"><?= $e($auction['brand']) ?></a></li>
            <li aria-current="page"><?= $e($auction['title']) ?></li>
        </ol>
    </nav>

    <?php if ($isPreview): ?>
        <p class="alert alert--info" role="note"><?= $e($t('common.mock_notice', 'MAKIETA: komunikat podglądu, nie pochodzi z oferty.')) ?></p>
    <?php endif; ?>

    <?php if ($isPreview && $previewState === 'leading'): ?>
        <section class="alert alert--success bid-banner" role="status" aria-labelledby="bid-banner-title">
            <h2 class="bid-banner__title" id="bid-banner-title"><?= $e($t('bids.success_title', 'Twoja oferta')) ?> <?= $e($money($auction['price'])) ?> <?= $e($t('bids.success_is_highest', 'jest najwyższa!')) ?></h2>
            <p class="bid-banner__text"><?= $e($t('bids.success_text', 'Jesteś obecnie liderem licytacji. Otrzymasz powiadomienie w razie przebicia.')) ?></p>
            <p class="status-label status-label--leading"><?= $e($t('bids.leading_label', 'Prowadzisz')) ?></p>
        </section>
    <?php elseif ($isPreview && $previewState === 'outbid'): ?>
        <section class="alert alert--danger bid-banner" role="alert" aria-labelledby="bid-banner-title">
            <h2 class="bid-banner__title" id="bid-banner-title"><?= $e($t('bids.outbid_title', 'Zostałeś przebity')) ?></h2>
            <p class="bid-banner__text"><?= $e($t('bids.current_offer', 'Aktualna oferta')) ?> <?= $e($money($auction['price'])) ?>, <?= $e($t('bids.your_offer', 'Twoja oferta')) ?> <?= $e($money($auction['viewerBid'])) ?>.</p>
            <p class="bid-banner__text"><?= $e($t('bids.outbid_text', 'Inny licytant złożył wyższą ofertę. Podbij, aby odzyskać prowadzenie!')) ?></p>
            <a class="button button--accent" href="#bid-amount"><?= $e($t('bids.outbid_cta', 'Przebij ofertę')) ?></a>
        </section>
    <?php elseif ($bidError !== null): ?>
        <section class="alert alert--danger bid-banner" role="alert" data-dismissible>
            <h2 class="bid-banner__title"><?= $e($bidError['title']) ?></h2>
            <p class="bid-banner__text"><?= $e($bidError['text']) ?></p>
            <button class="button button--icon" type="button" data-dismiss aria-label="<?= $e($t('common.close', 'Zamknij')) ?>">×</button>
        </section>
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
                <?php if ($auction['vin'] !== null && $auction['vin'] !== ''): ?>
                    <p class="auction-detail__vin">
                        <?= $e($t('auction.vin', 'VIN')) ?>: <code id="vin-value"><?= $e($auction['vin']) ?></code>
                        <button class="button button--ghost" type="button" data-copy-target="vin-value"><?= $e($t('auction.copy_vin', 'Kopiuj VIN')) ?></button>
                    </p>
                <?php endif; ?>
                <ul class="auction-detail__tags">
                    <li class="status-label"><?= $e($auction['origin']) ?></li>
                    <?php if ($record->isExtended()): ?>
                        <li class="status-label"><?= $e($t('auction.extended', 'Aukcja przedłużona')) ?></li>
                    <?php endif; ?>
                </ul>
            </header>

            <section class="gallery" aria-label="<?= $e($t('auction.gallery', 'Zdjęcia pojazdu')) ?>">
                <ul class="gallery__list">
                    <?php foreach ($images as $position => $image): ?>
                        <?php $imageAlt = $auction['title'] . ' (' . ($position + 1) . '/' . count($images) . ')'; ?>
                        <li class="gallery__item">
                            <figure>
                                <img src="<?= $e($image->getPath()) ?>" alt="<?= $e($imageAlt) ?>" loading="<?= $position === 0 ? 'eager' : 'lazy' ?>">
                            </figure>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($images === []): ?>
                        <li class="gallery__item">
                            <figure>
                                <span class="gallery__placeholder" role="img" aria-label="<?= $e($t('auction.no_photo', 'Brak zdjęcia')) ?>">🚗</span>
                            </figure>
                        </li>
                    <?php endif; ?>
                </ul>
            </section>

            <section class="seller-card" aria-labelledby="seller-title">
                <span class="seller-card__avatar" aria-hidden="true"><?= $e($auction['seller']['initials']) ?></span>
                <div class="seller-card__body">
                    <h2 class="seller-card__name" id="seller-title"><?= $e($auction['seller']['name']) ?></h2>
                    <p class="status-label status-label--verified"><?= $e($t('auction.verified', 'Zweryfikowana firma')) ?></p>
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
    <?php if ($isPreview && $previewState === 'confirm'): ?>
        <script type="module" src="/js/bid-preview.js"></script>
    <?php endif; ?>
    <?php if ($bidError !== null): ?>
        <script type="module" src="/js/dismiss.js"></script>
        <script type="module" src="/js/clear-amount.js"></script>
    <?php endif; ?>
</main>
