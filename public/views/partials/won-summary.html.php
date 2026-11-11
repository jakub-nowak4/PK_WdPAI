<?php
/**
 * Podsumowanie wygranej aukcji (ekran 08), wstawiane nad szczegolami aukcji.
 * @var callable $t
 * @var callable $e
 * @var callable $money
 * @var array<string,mixed> $auction price, endsAtLabel, seller (name, phone?, email)
 */
$seller = $auction['seller'];
$steps = [
    ['title' => $t('won.step1_title', 'Kontakt ze sprzedającym'), 'text' => $t('won.step1_text', 'Skontaktuj się z firmą, aby uzgodnić szczegóły.')],
    ['title' => $t('won.step2_title', 'Umowa i płatność'), 'text' => $t('won.step2_text', 'Podpisz umowę i opłać pojazd zgodnie z ustaleniami.')],
    ['title' => $t('won.step3_title', 'Odbiór pojazdu'), 'text' => $t('won.step3_text', 'Odbierz auto osobiście albo zamów transport.')],
];
?>
<section class="alert alert--success won-summary" aria-labelledby="won-title">
    <p class="status-label status-label--won"><?= $e($t('won.label', 'Aukcja zakończona pomyślnie')) ?></p>
    <h2 class="won-summary__title" id="won-title"><?= $e($t('won.title', 'Gratulacje! Wygrałeś aukcję')) ?></h2>
    <p class="won-summary__text"><?= $e($t('won.text', 'Twoja oferta okazała się najwyższa. Poniżej znajdziesz dane sprzedającego i kolejne kroki.')) ?></p>

    <dl class="won-summary__facts">
        <div class="won-summary__fact">
            <dt><?= $e($t('won.final_price', 'Cena końcowa')) ?></dt>
            <dd class="won-summary__price"><?= $e($money($auction['price'])) ?></dd>
        </div>
        <div class="won-summary__fact">
            <dt><?= $e($t('won.end_date', 'Data zakończenia')) ?></dt>
            <dd><?= $e($auction['endsAtLabel']) ?></dd>
        </div>
    </dl>

    <article class="won-summary__seller" aria-labelledby="won-seller-title">
        <h3 id="won-seller-title"><?= $e($seller['name']) ?></h3>
        <p class="status-label status-label--verified"><?= $e($t('auction.verified', 'Zweryfikowana firma')) ?></p>
        <ul class="won-summary__contacts">
            <?php if (!empty($seller['phone'])): ?>
                <li><a href="tel:<?= $e(preg_replace('/[^0-9+]/', '', (string) $seller['phone'])) ?>"><?= $e($seller['phone']) ?></a></li>
            <?php endif; ?>
            <li><a href="mailto:<?= $e($seller['email']) ?>"><?= $e($seller['email']) ?></a></li>
        </ul>
        <p><?= $e($t('won.contact_note', 'Firma skontaktuje się z Tobą w ciągu 48 godzin.')) ?></p>
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
