<?php
/**
 * Wspolny dokument HTML. Opcjonalne zmienne od kontrolera (kazda ma bezpieczna wartosc domyslna):
 * $pageTitle, $t (callable tlumaczen: fn(string $key, string $default): string), $lang,
 * $currentUser albo null (gosc). Struktura (przyklad, ustawia kontroler/router):
 *   ['id' => 7, 'name' => 'Jan Kowalski', 'initials' => 'JK', 'role' => 'BUYER' (BUYER|DEALER|ADMIN),
 *    'unreadCount' => 3, 'approved' => true (tylko DEALER, opcjonalnie)]
 * $currentPath, $csrfToken; $unreadCount (zgodnosc wsteczna, gdy brak currentUser['unreadCount']).
 */
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$lang = (string) ($lang ?? 'pl');
$lang = in_array($lang, ['pl', 'en'], true) ? $lang : 'pl';
$currentUser = $currentUser ?? null;
// Gdy kontroler nie przekazal $currentPath, bierzemy biezaca sciezke zadania (nie zakladamy '/').
$currentPath = (string) ($currentPath ?? ($_SERVER['REQUEST_URI'] ?? '/'));
$csrfToken = (string) ($csrfToken ?? '');
$unreadCount = (int) ($unreadCount ?? 0);
$accountHref = '/login';

if (is_array($currentUser)) {
    $role = (string) ($currentUser['role'] ?? '');
    $name = trim((string) ($currentUser['name'] ?? ''));
    $unreadCount = max(0, (int) ($currentUser['unreadCount'] ?? $unreadCount));
    $currentUser['initials'] = mb_strtoupper(mb_substr((string) ($currentUser['initials'] ?? $name), 0, 2));
    $accountHref = match ($role) {
        'ADMIN' => '/admin',
        'DEALER' => ($currentUser['approved'] ?? true) === false ? '/account/pending' : '/company',
        default => '/my-bids',
    };
}
$navItems = require __DIR__ . '/partials/nav-items.php';

// Aktywna pozycja nawigacji: tylko jedna, o najdluzszym dopasowaniu sciezki (bez query).
$pathOnly = (string) strtok($currentPath, '?');
$activeHref = null;
$bestLength = -1;
foreach ($navItems as $navItem) {
    foreach ([(string) strtok($navItem['href'], '?'), ...($navItem['match'] ?? [])] as $candidate) {
        $matches = $pathOnly === $candidate || ($candidate !== '/' && str_starts_with($pathOnly, $candidate . '/'));
        if ($matches && strlen($candidate) > $bestLength) {
            $bestLength = strlen($candidate);
            $activeHref = $navItem['href'];
        }
    }
}
// aria-current="page" tylko przy rownosci ze sciezka pozycji; dopasowanie sekcji to "true".
$activeExact = $activeHref !== null && $pathOnly === strtok($activeHref, '?');
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
