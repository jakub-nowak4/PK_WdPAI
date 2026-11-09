<?php
/**
 * Karta bledu (ekrany 18 i 19): kod, etykieta, tytul, opis, przyciski.
 * @var callable $t
 * @var callable $e
 * @var string $errorCode kod HTTP, np. "403"
 * @var string $errorLabel etykieta nad tytulem
 * @var string $errorTitle tytul H1
 * @var string $errorDescription opis
 * @var string $errorIcon "lock" (403), "alert" (400) lub "search" (404)
 * @var array|null $currentUser zalogowany uzytkownik; brak = gosc
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errorIcon = (string) ($errorIcon ?? 'alert');
$isGuest = empty($currentUser);
$iconPaths = [
    'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
    'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/>',
    'search' => '<circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/>',
];
?>
<main class="page error-page" id="main-content">
    <p class="error-page__code" aria-hidden="true"><?= $e($errorCode) ?></p>
    <section class="error-page__card" aria-labelledby="error-title">
        <svg class="error-page__icon" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $iconPaths[$errorIcon] ?? $iconPaths['alert'] ?></svg>
        <p class="error-page__label"><?= $e($errorLabel) ?></p>
        <h1 class="error-page__title" id="error-title"><?= $e($errorTitle) ?></h1>
        <p class="error-page__description"><?= $e($errorDescription) ?></p>
        <p class="error-page__actions">
            <a class="button button--accent" href="/"><?= $e($t('error.back_to_auctions', 'Wróć do aukcji')) ?></a>
            <?php if ($isGuest): ?>
                <a class="button button--secondary" href="/login"><?= $e($t('nav.login', 'Zaloguj się')) ?></a>
            <?php endif; ?>
        </p>
    </section>
</main>
