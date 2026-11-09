<?php
/**
 * Przelacznik rodzaju konta (kupujacy / firma) w rejestracji. Zwykle linki, dzialaja bez JS.
 * @var callable $t
 * @var callable $e
 * @var string $accountType 'buyer' albo 'company'
 */
$items = [
    'buyer' => ['href' => '/register', 'label' => $t('register.type_buyer', 'Kupujący')],
    'company' => ['href' => '/register/company', 'label' => $t('register.type_company', 'Firma')],
];
?>
<nav class="segmented account-type-switch" aria-label="<?= $e($t('register.type_label', 'Rodzaj konta')) ?>">
    <?php foreach ($items as $key => $item): ?>
        <a class="segmented__item<?= $accountType === $key ? ' segmented__item--active' : '' ?>"
           href="<?= $e($item['href']) ?>"<?= $accountType === $key ? ' aria-current="page"' : '' ?>><?= $e($item['label']) ?></a>
    <?php endforeach; ?>
</nav>
