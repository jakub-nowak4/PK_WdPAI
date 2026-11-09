<?php
/**
 * Nie znaleziono 404 (ekran 19). Nazwa pliku zostaje, bo uzywa jej ErrorController.
 * Opcjonalne zmienne: $currentUser, $t, $e. Zmienna $errorMessage jest pomijana, bo
 * ErrorController przekazuje ogolne "Nie znaleziono strony." (powielaloby tytul).
 */
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errorCode = '404';
$errorIcon = 'search';
$errorLabel = $t('error.404.label', 'Błąd HTTP 404 • zasób niedostępny');
$errorTitle = $t('error.404.title', 'Nie znaleziono strony');
$errorDescription = $t('error.404.description', 'Aukcja mogła zostać zakończona lub usunięta.');
require __DIR__ . '/../partials/error-card.html.php';
