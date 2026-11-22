<?php
/**
 * Blad serwera 500 (ekran 18, wariant). Nie korzysta z bazy ani z danych uzytkownika:
 * ErrorController::handleServerError() renderuje go z layoutem goscia (api.md 2.8).
 * Opcjonalne zmienne: $t, $e. Zmienna $errorMessage jest pomijana (tresc wyjatku nie moze trafic do strony).
 */
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errorCode = '500';
$errorIcon = 'alert';
$errorLabel = $t('error.500.label', 'Błąd serwera • kod błędu 500');
$errorTitle = $t('error.500.title', 'Coś poszło nie tak');
$errorDescription = $t('error.500.description', 'Spróbuj ponownie za chwilę.');
require __DIR__ . '/../partials/error-card.html.php';
