<?php
/**
 * Strona glowna: baner + lista aukcji z filtrami (ekran 01).
 * Oczekiwane zmienne od kontrolera (na razie brak, uzywana jest MAKIETA z partials/mock-auctions.php):
 * $auctions, $brands, $filters, $page, $pages, $total; opcjonalnie $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;

require __DIR__ . '/partials/auction-list-defaults.php';
?>
<main class="page page--home" id="main-content">
    <section class="hero" aria-labelledby="hero-title">
        <p class="hero__label"><?= $e($t('home.label', 'Giełda licytacyjna pojazdów online na żywo')) ?></p>
        <h1 class="hero__title" id="hero-title"><?= $e($t('home.title', 'Aukcje na żywo – auta z USA i Europy')) ?></h1>
        <p class="hero__lead"><?= $e($t('home.lead', 'Transparentna platforma licytacji pojazdów z USA i Europy z bezpośrednim udziałem zweryfikowanych sprzedawców.')) ?></p>

        <form class="hero__search" method="get" action="/auctions" role="search">
            <label class="visually-hidden" for="hero-q"><?= $e($t('home.search_label', 'Szukaj pojazdu')) ?></label>
            <input class="form-field__control" id="hero-q" name="q" type="search" maxlength="100"
                   autocomplete="off" value="<?= $e($filters['q'] ?? '') ?>"
                   placeholder="<?= $e($t('home.search_placeholder', 'Marka, model, rocznik…')) ?>">
            <button class="button button--primary" type="submit"><?= $e($t('home.search', 'Szukaj pojazdu')) ?></button>
        </form>

        <ul class="trust-badges">
            <li class="trust-badges__item">
                <strong><?= $e($t('home.trust1.title', 'Zweryfikowane firmy')) ?></strong>
                <span><?= $e($t('home.trust1.text', 'Sprawdzona reputacja sprzedających')) ?></span>
            </li>
            <li class="trust-badges__item">
                <strong><?= $e($t('home.trust2.title', 'Licytacja na żywo')) ?></strong>
                <span><?= $e($t('home.trust2.text', 'Aukcje na żywo')) ?></span>
            </li>
            <li class="trust-badges__item">
                <strong><?= $e($t('home.trust3.title', 'Przejrzyste oferty')) ?></strong>
                <span><?= $e($t('home.trust3.text', 'Jasne zasady i dokumentacja')) ?></span>
            </li>
        </ul>
    </section>

    <?php require __DIR__ . '/partials/auction-list-block.html.php'; ?>
</main>
