<?php
/**
 * Aukcja wygrana (ekran 08), osobna strona GET /auctions/{id}/won.
 * Zmienne od kontrolera: $wonAuction (App\Model\WonAuction), $auctionId; opcjonalnie $t, $e.
 *
 * @var \App\Model\WonAuction $wonAuction
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;

$auction = $wonAuction->getSummary()->getAuction();
?>
<main class="page page--won" id="main-content">
    <nav class="breadcrumbs" aria-label="<?= $e($t('nav.breadcrumbs', 'Okruszki')) ?>">
        <ol class="breadcrumbs__list">
            <li><a href="/my-bids?status=won"><?= $e($t('nav.my_bids', 'Moje oferty')) ?></a></li>
            <li aria-current="page"><?= $e($auction->getTitle()) ?></li>
        </ol>
    </nav>

    <?php require __DIR__ . '/../partials/won-summary.html.php'; ?>
</main>
