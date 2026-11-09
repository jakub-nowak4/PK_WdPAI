<?php
/**
 * Nawigacja glowna w naglowku.
 * @var callable $t
 * @var callable $e
 * @var string|null $activeHref href aktywnej pozycji (liczy layout.html.php)
 * @var bool $activeExact czy sciezka rowna sie href (aria-current="page"), inaczej "true"
 * @var list<array{href:string,label:string,icon:string}> $navItems
 */
?>
<nav class="site-header__nav" aria-label="<?= $e($t('nav.main', 'Nawigacja główna')) ?>">
    <ul class="site-header__nav-list">
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['bottomOnly'])) { continue; } ?>
            <?php $isActive = $activeHref === $item['href']; ?>
            <li>
                <a class="nav-link<?= $isActive ? ' nav-link--active' : '' ?>"
                   href="<?= $e($item['href']) ?>"<?= $isActive ? ' aria-current="' . ($activeExact ? 'page' : 'true') . '"' : '' ?>>
                    <?= $e($item['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
