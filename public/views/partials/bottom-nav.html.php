<?php
/**
 * Dolny pasek nawigacji na telefonach (widocznosc ustala CSS, ukryty od 768 px).
 * @var callable $t
 * @var callable $e
 * @var string|null $activeHref href aktywnej pozycji (liczy layout.html.php)
 * @var bool $activeExact czy sciezka rowna sie href (aria-current="page"), inaczej "true"
 * @var array|null $currentUser
 * @var string $csrfToken
 * @var list<array{href:string,label:string,icon:string}> $navItems
 */
?>
<nav class="bottom-nav" aria-label="<?= $e($t('nav.mobile', 'Nawigacja mobilna')) ?>">
    <ul class="bottom-nav__list">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = $activeHref === $item['href']; ?>
            <li>
                <a class="bottom-nav__link<?= $isActive ? ' bottom-nav__link--active' : '' ?>"
                   href="<?= $e($item['href']) ?>"<?= $isActive ? ' aria-current="' . ($activeExact ? 'page' : 'true') . '"' : '' ?>>
                    <span class="bottom-nav__icon" aria-hidden="true"><?= $e($item['icon']) ?></span>
                    <span class="bottom-nav__label"><?= $e($item['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
        <?php if (is_array($currentUser ?? null)): ?>
            <?php /* Mobile: wylogowanie (formularz z naglowka jest ukryty od 768 px). */ ?>
            <li class="bottom-nav__logout">
                <form method="post" action="/logout">
                    <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
                    <button class="bottom-nav__link bottom-nav__button" type="submit">
                        <svg class="bottom-nav__svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span class="bottom-nav__label"><?= $e($t('nav.logout', 'Wyloguj się')) ?></span>
                    </button>
                </form>
            </li>
        <?php endif; ?>
    </ul>
</nav>
