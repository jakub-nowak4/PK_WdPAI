<?php
/**
 * Brak dostepu 403 (ekran 18).
 * Opcjonalne zmienne: $errorMessage (komunikat z kontrolera nadpisuje domyslny opis), $currentUser, $t, $e.
 */
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errorCode = '403';
$errorIcon = 'lock';
$errorLabel = $t('error.403.label', 'Autoryzacja • kod błędu 403');
$errorTitle = $t('error.403.title', 'Brak dostępu');
$errorDescription = (string) ($errorMessage ?? '') !== ''
    ? (string) $errorMessage
    : $t('error.403.description', 'Nie masz uprawnień do tej strony.');
require __DIR__ . '/../partials/error-card.html.php';
