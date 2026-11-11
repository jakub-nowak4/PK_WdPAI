<?php
/**
 * Aukcja wygrana (ekran 08), osobna strona GET /auctions/{id}/won.
 * Oczekiwane zmienne od kontrolera (na razie brak, uzywana jest MAKIETA): $auction; opcjonalnie $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;

$isMock = false;
if (!isset($auction)) {
    $mock = require __DIR__ . '/../partials/mock-auction.php';
    $auction = $mock['auction'];
    $isMock = true;
    // MAKIETA (ekran 08): aukcja zakonczona, kupujacy wygral za 95 500 zł.
    $auction['status'] = 'ended';
    $auction['price'] = 95500;
    $auction['viewerStatus'] = 'won';
    $auction['endsAtLabel'] = '24.03.2026, 18:30';
}
$money = static fn (int|float $amount): string => number_format((int) $amount, 0, ',', "\u{00A0}") . "\u{00A0}zł";
?>
<main class="page page--won" id="main-content">
    <nav class="breadcrumbs" aria-label="<?= $e($t('nav.breadcrumbs', 'Okruszki')) ?>">
        <ol class="breadcrumbs__list">
            <li><a href="/my-bids?status=won"><?= $e($t('nav.my_bids', 'Moje oferty')) ?></a></li>
            <li aria-current="page"><?= $e($auction['title']) ?></li>
        </ol>
    </nav>

    <?php if ($isMock): ?>
        <p class="alert alert--info" role="note">MAKIETA: dane przykładowe, nie pochodzą z bazy.</p>
    <?php endif; ?>

    <?php require __DIR__ . '/../partials/won-summary.html.php'; ?>
</main>
