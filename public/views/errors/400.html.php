<?php
/**
 * Nieprawidlowe zadanie 400 (ekran 18, wariant).
 * Opcjonalne zmienne: $currentUser, $t, $e. Zmienna $errorMessage jest pomijana, bo
 * ErrorController przekazuje ogolne "Nieprawidlowe zadanie." (powielaloby tytul).
 */
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errorCode = '400';
$errorIcon = 'alert';
$errorLabel = $t('error.400.label', 'Błąd żądania • kod błędu 400');
$errorTitle = $t('error.400.title', 'Nieprawidłowe żądanie');
$errorDescription = $t('error.400.description', 'Adres zawiera nieprawidłowy parametr.');
require __DIR__ . '/../partials/error-card.html.php';
