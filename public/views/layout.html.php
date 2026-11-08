<?php
/**
 * Wspolny dokument HTML. Opcjonalne zmienne od kontrolera (kazda ma bezpieczna wartosc domyslna):
 * $pageTitle, $t (callable tlumaczen: fn(string $key, string $default): string), $lang,
 * $currentUser (['name', 'role', 'approved'] albo null), $currentPath, $csrfToken, $unreadCount.
 */
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$lang = (string) ($lang ?? 'pl');
$lang = in_array($lang, ['pl', 'en'], true) ? $lang : 'pl';
$currentUser = $currentUser ?? null;
$currentPath = (string) ($currentPath ?? '/');
$csrfToken = (string) ($csrfToken ?? '');
$unreadCount = (int) ($unreadCount ?? 0);
$navItems = require __DIR__ . '/partials/nav-items.php';
?>
<!doctype html>
<html lang="<?= $e($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($pageTitle ?? 'MotoBid') ?></title>
    <meta name="csrf-token" content="<?= $e($csrfToken) ?>">
    <script>
        (function () {
            const storageKey = 'theme';
            let savedTheme = null;

            try {
                savedTheme = localStorage.getItem(storageKey);
            } catch (error) {
                savedTheme = null;
            }

            const hasSavedTheme = savedTheme === 'light' || savedTheme === 'dark';
            const systemPrefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)'
            ).matches;

            document.documentElement.dataset.theme = hasSavedTheme
                ? savedTheme
                : (systemPrefersDark ? 'dark' : 'light');
        })();
    </script>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <a class="skip-link" href="#main-content"><?= $e($t('nav.skip', 'Przejdź do treści')) ?></a>
    <?php require __DIR__ . '/partials/header.html.php'; ?>

    <?= $content ?>

    <?php require __DIR__ . '/partials/footer.html.php'; ?>
    <?php require __DIR__ . '/partials/bottom-nav.html.php'; ?>
    <script src="/js/theme.js" defer></script>
</body>
</html>
