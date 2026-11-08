<?php
/**
 * Naglowek serwisu: logo, nawigacja, przelacznik jezyka, motyw, konto.
 * @var callable $t
 * @var callable $e
 * @var array{id?:int,name?:string,initials?:string,role?:string,unreadCount?:int,approved?:bool}|null $currentUser
 * @var string $accountHref (liczone w layout.html.php wg roli)
 * @var string $currentPath
 * @var string $lang
 * @var string $csrfToken
 * @var int $unreadCount
 */
$isLoggedIn = is_array($currentUser);
$userName = $isLoggedIn ? (string) ($currentUser['name'] ?? '') : '';
$initials = $isLoggedIn ? (string) ($currentUser['initials'] ?? '') : '';
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
               aria-label="<?= $e($t('nav.notifications', 'Powiadomienia')) ?><?= $unreadCount > 0 ? ' (' . $unreadCount . ')' : '' ?>">
                <span aria-hidden="true">🔔</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="notification-bell__count" aria-hidden="true"><?= $e($unreadCount > 99 ? '99+' : (string) $unreadCount) ?></span>
                <?php endif; ?>
            </a>
            <a class="user-tile" href="<?= $e($accountHref) ?>">
                <span class="avatar" aria-hidden="true"><?= $e($initials) ?></span>
                <span class="user-tile__text">
                    <span class="user-tile__name"><?= $e($userName) ?></span>
                    <span class="user-tile__hint"><?= $e($t('nav.my_account', 'Moje konto')) ?></span>
                </span>
            </a>
            <form class="logout-form" method="post" action="/logout">
                <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
                <button class="icon-button" type="submit" aria-label="<?= $e($t('nav.logout', 'Wyloguj się')) ?>"
                        title="<?= $e($t('nav.logout', 'Wyloguj się')) ?>">
                    <span aria-hidden="true">⎋</span>
                </button>
            </form>
        <?php else: ?>
            <?php /* Desktop: logowanie tutaj. Mobile: CSS ukrywa .site-header__login, link jest w dolnej nawigacji (nav-items.php, bottomOnly). */ ?>
            <a class="button button--secondary site-header__login" href="/login"><?= $e($t('nav.login', 'Zaloguj się')) ?></a>
            <a class="button button--primary" href="/register"><?= $e($t('nav.register', 'Zarejestruj się')) ?></a>
        <?php endif; ?>
    </div>
</header>
