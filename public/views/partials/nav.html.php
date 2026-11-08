<?php
/**
 * Nawigacja glowna w naglowku.
 * @var callable $t
 * @var callable $e
 * @var string $currentPath
 * @var list<array{href:string,label:string,icon:string}> $navItems
 */
?>
<nav class="site-header__nav" aria-label="<?= $e($t('nav.main', 'Nawigacja główna')) ?>">
    <ul class="site-header__nav-list">
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['bottomOnly'])) { continue; } ?>
            <?php $isActive = $currentPath === $item['href'] || str_starts_with($currentPath, $item['href'] . '/'); ?>
            <li>
                <a class="nav-link<?= $isActive ? ' nav-link--active' : '' ?>"
                   href="<?= $e($item['href']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <?= $e($item['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
