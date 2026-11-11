<?php
/**
 * Blok listy aukcji: filtry, siatka kart i paginacja (wspolny dla / i /auctions).
 * @var callable $t
 * @var callable $e
 * @var bool $isMock
 * @var array<int,array<string,mixed>> $auctions
 * @var array<string,string> $brands
 * @var array<string,string> $filters
 * @var int $page
 * @var int $pages
 * @var int $total
 */
?>
<?php if ($isMock): ?>
    <p class="alert alert--info" role="note">MAKIETA: dane przykładowe, nie pochodzą z bazy.</p>
<?php endif; ?>

<div class="auction-list-layout">
    <aside aria-label="<?= $e($t('filters.title', 'Filtry')) ?>">
        <?php require __DIR__ . '/auction-filters.html.php'; ?>
    </aside>

    <section class="auction-list" aria-labelledby="auction-list-title">
        <header class="auction-list__header">
            <h2 id="auction-list-title"><?= $e($t('auction.found', 'Znaleziono')) ?>: <?= $e((string) $total) ?> <?= $e($t('auction.active', 'aktywnych aukcji')) ?></h2>
            <span class="status-label status-label--active"><span class="status-label__dot" aria-hidden="true"></span>LIVE 24/7</span>
        </header>

        <?php if ($auctions === []): ?>
            <div class="empty-state">
                <h3 class="empty-state__title"><?= $e($t('auction.empty', 'Brak aukcji spełniających kryteria')) ?></h3>
                <a class="button button--secondary" href="/auctions"><?= $e($t('filters.clear', 'Wyczyść filtry')) ?></a>
            </div>
        <?php else: ?>
            <div class="auction-grid">
                <?php foreach ($auctions as $auction): ?>
                    <?php require __DIR__ . '/auction-card.html.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php require __DIR__ . '/pagination.html.php'; ?>
        <?php endif; ?>
    </section>
</div>
