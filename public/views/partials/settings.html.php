<?php
/**
 * Przelacznik jezyka i przycisk motywu (naglowek i stopka).
 * @var callable $t
 * @var callable $e
 * @var string $lang
 * @var string $csrfToken
 * @var string $currentPath
 */
?>
<form class="segmented" method="post" action="/language" aria-label="<?= $e($t('nav.language', 'Język')) ?>">
    <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
    <input type="hidden" name="back" value="<?= $e($currentPath) ?>">
    <?php foreach (['pl' => 'PL', 'en' => 'EN'] as $code => $label): ?>
        <button class="segmented__item<?= $lang === $code ? ' segmented__item--active' : '' ?>"
                type="submit" name="lang" value="<?= $e($code) ?>"
                lang="<?= $e($code) ?>" aria-pressed="<?= $lang === $code ? 'true' : 'false' ?>">
            <?= $e($label) ?>
        </button>
    <?php endforeach; ?>
</form>

<button class="theme-toggle" type="button" data-theme-toggle
        aria-label="<?= $e($t('theme.to_dark', 'Włącz ciemny motyw')) ?>"
        aria-pressed="false" title="<?= $e($t('theme.toggle', 'Zmień motyw')) ?>">
    <span class="theme-toggle__icon" aria-hidden="true">🌙</span>
</button>
