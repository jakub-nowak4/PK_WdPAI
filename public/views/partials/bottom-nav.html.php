<?php
/**
 * Dolny pasek nawigacji na telefonach (widocznosc ustala CSS, < 768 px).
 * @var callable $t
 * @var callable $e
 * @var string $currentPath
 * @var list<array{href:string,label:string,icon:string}> $navItems
 */
?>
<nav class="bottom-nav" aria-label="<?= $e($t('nav.mobile', 'Nawigacja mobilna')) ?>">
    <ul class="bottom-nav__list">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = $currentPath === $item['href'] || str_starts_with($currentPath, $item['href'] . '/'); ?>
            <li>
                <a class="bottom-nav__link<?= $isActive ? ' bottom-nav__link--active' : '' ?>"
                   href="<?= $e($item['href']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <span class="bottom-nav__icon" aria-hidden="true"><?= $e($item['icon']) ?></span>
                    <span class="bottom-nav__label"><?= $e($item['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
