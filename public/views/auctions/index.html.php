<?php
/**
 * Lista aukcji (GET /auctions?brand=&priceMax=&sort=&page=&q=): ten sam blok listy co na stronie glownej, bez banera.
 * Oczekiwane zmienne od kontrolera: $filters (q, brand, priceMax, sort), $page;
 * reszta (MAKIETA) z partials/mock-auctions.php do czasu repozytorium.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;

require __DIR__ . '/../partials/auction-list-defaults.php';
?>
<main class="page page--auctions" id="main-content">
    <header class="page__header">
        <div>
            <p class="page__eyebrow"><?= $e($t('auctions.eyebrow', 'Giełda licytacyjna')) ?></p>
            <h1 class="page__title"><?= $e($t('auctions.page_title', 'Aukcje')) ?></h1>
            <p class="page__lead"><?= $e($t('auctions.page_lead', 'Przeglądaj aktywne aukcje i filtruj je według marki, ceny i czasu zakończenia.')) ?></p>
        </div>
    </header>

    <?php require __DIR__ . '/../partials/auction-list-block.html.php'; ?>
</main>
