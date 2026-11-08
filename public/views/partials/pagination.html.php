<?php
/**
 * Paginacja listy (12 na strone). Zachowuje biezace filtry w linkach.
 * @var callable $t
 * @var callable $e
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var array<string,string> $filters
 */
$perPage = 12;
$from = ($page - 1) * $perPage + 1;
$to = min($page * $perPage, $total);
$pageUrl = static function (int $n) use ($filters): string {
    $query = array_filter($filters, static fn (string $v): bool => $v !== '');
    $query['page'] = $n;

    return '/auctions?' . http_build_query($query);
};
// Numery: pierwsza, sasiednie, ostatnia (z wielokropkiem).
$numbers = array_values(array_unique(array_filter(
    [1, $page - 1, $page, $page + 1, $pages],
    static fn (int $n): bool => $n >= 1 && $n <= $pages
)));
sort($numbers);
?>
<nav class="pagination" aria-label="<?= $e($t('pagination.label', 'Strony wyników')) ?>">
    <p class="pagination__summary">
        <?= $e($t('pagination.shown', 'Pokazano')) ?> <?= $e((string) $from) ?>–<?= $e((string) $to) ?>
        <?= $e($t('pagination.of', 'z')) ?> <?= $e((string) $total) ?> <?= $e($t('auction.bids', 'ofert')) ?>
    </p>
    <ul class="pagination__list">
        <li>
            <?php if ($page > 1): ?>
                <a class="pagination__link" href="<?= $e($pageUrl($page - 1)) ?>" rel="prev"><?= $e($t('pagination.prev', 'Poprzednia')) ?></a>
            <?php else: ?>
                <span class="pagination__link pagination__link--disabled" aria-disabled="true"><?= $e($t('pagination.prev', 'Poprzednia')) ?></span>
            <?php endif; ?>
        </li>
        <li class="pagination__current-mobile"><?= $e($t('pagination.page', 'Strona')) ?> <?= $e((string) $page) ?> <?= $e($t('pagination.of', 'z')) ?> <?= $e((string) $pages) ?></li>
        <?php $previous = 0; foreach ($numbers as $n): ?>
            <?php if ($n - $previous > 1): ?><li class="pagination__gap" aria-hidden="true">…</li><?php endif; ?>
            <li class="pagination__number">
                <a class="pagination__link<?= $n === $page ? ' pagination__link--active' : '' ?>"
                   href="<?= $e($pageUrl($n)) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= $e((string) $n) ?></a>
            </li>
            <?php $previous = $n; ?>
        <?php endforeach; ?>
        <li>
            <?php if ($page < $pages): ?>
                <a class="pagination__link" href="<?= $e($pageUrl($page + 1)) ?>" rel="next"><?= $e($t('pagination.next', 'Następna')) ?></a>
            <?php else: ?>
                <span class="pagination__link pagination__link--disabled" aria-disabled="true"><?= $e($t('pagination.next', 'Następna')) ?></span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
