<?php
/**
 * Naglowek serwisu: logo, nawigacja, przelacznik jezyka, motyw, konto.
 * @var callable $t
 * @var callable $e
 * @var array{name?:string,role?:string,approved?:bool}|null $currentUser
 * @var string $currentPath
 * @var string $lang
 * @var string $csrfToken
 * @var int $unreadCount
 */
$isLoggedIn = is_array($currentUser);
$userName = $isLoggedIn ? (string) ($currentUser['name'] ?? '') : '';
$initials = $userName !== '' ? mb_strtoupper(mb_substr($userName, 0, 1)) : '';
?>
<header class="site-header">
    <a class="site-header__logo" href="/" aria-label="<?= $e($t('nav.home', 'MotoBid, strona główna')) ?>">
        <span class="site-header__logo-mark" aria-hidden="true">M</span>
        <span class="site-header__logo-text">MotoBid</span>
    </a>

    <?php require __DIR__ . '/nav.html.php'; ?>

    <div class="site-header__actions">
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

        <button class="theme-toggle" type="button"
                aria-label="<?= $e($t('theme.to_dark', 'Włącz ciemny motyw')) ?>"
                aria-pressed="false" title="<?= $e($t('theme.toggle', 'Zmień motyw')) ?>">
            <span class="theme-toggle__icon" aria-hidden="true">🌙</span>
        </button>

        <?php if ($isLoggedIn): ?>
            <a class="notification-bell" href="/notifications"
               aria-label="<?= $e($t('nav.notifications', 'Powiadomienia')) ?>">
                <span aria-hidden="true">🔔</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-bell__count"><?= $e((string) $unreadCount) ?></span>
                <?php endif; ?>
            </a>
            <span class="avatar" aria-hidden="true"><?= $e($initials) ?></span>
            <span class="site-header__user"><?= $e($userName) ?></span>
            <form method="post" action="/logout">
                <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
                <button class="button button--ghost" type="submit"><?= $e($t('nav.logout', 'Wyloguj się')) ?></button>
            </form>
        <?php else: ?>
            <?php /* Link logowania gościa jest tylko w nav-items.php (nawigacja główna i dolna), żeby go nie dublować. */ ?>
            <a class="button button--primary" href="/register"><?= $e($t('nav.register', 'Zarejestruj się')) ?></a>
        <?php endif; ?>
    </div>
</header>
