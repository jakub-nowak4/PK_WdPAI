<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) ($pageTitle ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
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
    <button
        class="theme-toggle"
        type="button"
        aria-label="Włącz ciemny motyw"
        aria-pressed="false"
        title="Zmień motyw"
    >
        <span class="theme-toggle__icon" aria-hidden="true">🌙</span>
    </button>

    <?= $content ?>
    <script src="/js/theme.js" defer></script>
</body>
</html>
