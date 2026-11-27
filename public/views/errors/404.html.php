<?php
/**
 * Nie znaleziono 404 (ekran 19). Wlasciwy widok; not-found.html.php tylko go dolacza (ErrorController).
 * Opcjonalne zmienne: $currentUser, $t, $e. Zmienna $errorMessage jest pomijana, bo
 * ErrorController przekazuje ogolne "Nie znaleziono strony." (powielaloby tytul).
 * Wejsciowa zmienna $errorCode (z ErrorController, np. "auction_not_found" albo kod ogolny)
 * jest przechwytywana PRZED nadpisaniem jej na stale "404" (uzywane tylko do plakietki) i
 * decyduje, czy opis dotyczy konkretnie aukcji, czy jest ogolny.
 */
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$isAuctionNotFound = ($errorCode ?? '') === 'auction_not_found';
$errorCode = '404';
$errorIcon = 'search';
$errorLabel = $t('error.404.label', 'Błąd HTTP 404 • zasób niedostępny');
$errorTitle = $t('error.404.title', 'Nie znaleziono strony');
$errorDescription = $isAuctionNotFound
    ? $t('error.404.description_auction', 'Aukcja mogła zostać zakończona lub usunięta.')
    : $t('error.404.description', 'Sprawdź adres albo wróć do listy aukcji.');
require __DIR__ . '/../partials/error-card.html.php';
