<?php
/**
 * Podsumowanie wygranej aukcji (ekran 08), renderowane na stronie auctions/won.html.php.
 * Dane z bazy: App\Model\WonAuction (aukcja, firma, cena koncowa, data zamkniecia). Bez danych innych licytujacych.
 * @var callable $t
 * @var callable $e
 * @var \App\Model\WonAuction $wonAuction
 */
$summary = $wonAuction->getSummary();
$auction = $summary->getAuction();
$cover = $summary->getCoverPath();
$closedAt = $wonAuction->getClosedAt();
$country = $auction->getOriginCountry();
$steps = [
    ['title' => $t('won.step1_title', 'Kontakt ze sprzedającym'), 'text' => $t('won.step1_text', 'Skontaktuj się z firmą, aby uzgodnić szczegóły.')],
    ['title' => $t('won.step2_title', 'Umowa i płatność'), 'text' => $t('won.step2_text', 'Podpisz umowę i opłać pojazd zgodnie z ustaleniami.')],
    ['title' => $t('won.step3_title', 'Odbiór pojazdu'), 'text' => $t('won.step3_text', 'Odbierz auto osobiście albo zamów transport.')],
];
?>
<section class="alert alert--success won-summary" aria-labelledby="won-title">
    <span class="won-summary__icon" aria-hidden="true">✓</span>
    <p class="status-label status-label--won"><?= $e($t('won.label', 'Aukcja zakończona pomyślnie')) ?></p>
    <h2 class="won-summary__title" id="won-title"><?= $e($t('won.title', 'Gratulacje! Wygrałeś aukcję')) ?></h2>
    <p class="won-summary__text"><?= $e($t('won.text', 'Twoja oferta okazała się najwyższa. Poniżej znajdziesz dane sprzedającego i kolejne kroki.')) ?></p>

    <article class="won-summary__car" aria-labelledby="won-car-title">
        <?php if ($cover !== null && $cover !== ''): ?>
            <img class="won-summary__image" src="<?= $e($cover) ?>" alt="<?= $e($auction->getTitle()) ?>">
        <?php else: ?>
            <p class="won-summary__image-placeholder"><?= $e($t('auction.no_photo', 'Brak zdjęcia')) ?></p>
        <?php endif; ?>
        <h3 id="won-car-title"><?= $e($auction->getTitle()) ?></h3>
        <?php if ($auction->getVin() !== null && $auction->getVin() !== ''): ?>
            <p><?= $e($t('auction.vin', 'VIN')) ?>: <?= $e($auction->getVin()) ?></p>
        <?php endif; ?>
        <p><?= $e($t('auction.origin', 'Kraj pochodzenia')) ?>: <?= $e($t('country.' . $country, $country)) ?></p>
    </article>

    <dl class="won-summary__facts">
        <div class="won-summary__fact">
            <dt><?= $e($t('won.final_price', 'Cena końcowa')) ?></dt>
            <dd class="won-summary__price"><?= $e($wonAuction->getFinalPrice()->format()) ?></dd>
        </div>
        <?php if ($closedAt !== null): ?>
            <div class="won-summary__fact">
                <dt><?= $e($t('won.end_date', 'Data zakończenia')) ?></dt>
                <dd><time datetime="<?= $e(\App\Support\LocalTime::iso($closedAt)) ?>"><?= $e(\App\Support\LocalTime::format($closedAt)) ?></time></dd>
            </div>
        <?php endif; ?>
    </dl>

    <article class="won-summary__seller" aria-labelledby="won-seller-title">
        <h3 id="won-seller-title"><?= $e($summary->getCompanyName()) ?></h3>
        <p class="status-label status-label--verified"><?= $e($t('auction.verified', 'Zweryfikowana firma')) ?></p>
    </article>

    <section aria-labelledby="won-steps-title">
        <h3 id="won-steps-title"><?= $e($t('won.next_steps', 'Następne kroki')) ?></h3>
        <ol class="won-summary__steps">
            <?php foreach ($steps as $step): ?>
                <li class="won-summary__step">
                    <strong><?= $e($step['title']) ?></strong>
                    <span><?= $e($step['text']) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <p class="won-summary__actions">
        <a class="button button--accent" href="/my-bids?status=won"><?= $e($t('nav.my_bids', 'Moje oferty')) ?></a>
        <a class="button button--secondary" href="/auctions"><?= $e($t('won.back', 'Wróć do aukcji')) ?></a>
    </p>
</section>
