<?php

declare(strict_types=1);

/**
 * Lista linkow nawigacji zalezna od roli. Zwraca tablice elementow:
 * ['href' => string, 'label' => string, 'icon' => string, 'bottomOnly' => bool (opcjonalnie), 'match' => list<string> (opcjonalnie)].
 * match = dodatkowe sciezki, na ktorych pozycja jest aktywna (np. "Aukcje" takze na "/").
 * bottomOnly = wpis tylko w dolnej nawigacji (gorna go pomija; na desktopie ten link jest w akcjach naglowka).
 * Oczekuje zmiennych: $t (callable), $currentUser (array|null).
 * Rola: BUYER, DEALER (firma), ADMIN; brak uzytkownika = gosc.
 */

$role = is_array($currentUser ?? null) ? (string) ($currentUser['role'] ?? '') : '';
$approved = is_array($currentUser ?? null) && ($currentUser['approved'] ?? false) === true;

$items = [
    ['href' => '/auctions', 'label' => $t('nav.auctions', 'Aukcje'), 'icon' => '🚗', 'match' => ['/']],
];

if ($role === 'BUYER') {
    // Powiadomienia i konto: dzwonek i kafelek w naglowku (nie ma ich na liscie).
    $items[] = ['href' => '/my-bids', 'label' => $t('nav.my_bids', 'Moje oferty'), 'icon' => '🏷️'];
} elseif ($role === 'DEALER') {
    if ($approved) {
        $items[] = ['href' => '/company', 'label' => $t('nav.company_panel', 'Panel firmy'), 'icon' => '🏢'];
        $items[] = ['href' => '/company/auctions/new', 'label' => $t('nav.add_auction', 'Dodaj aukcję'), 'icon' => '➕'];
    } else {
        $items[] = ['href' => '/account/pending', 'label' => $t('nav.account_status', 'Status konta'), 'icon' => '⏳'];
    }
} elseif ($role === 'ADMIN') {
    $items[] = ['href' => '/admin', 'label' => $t('nav.admin_panel', 'Panel administratora'), 'icon' => '🛡️'];
} else {
    $items[] = ['href' => '/login', 'label' => $t('nav.login', 'Zaloguj się'), 'icon' => '👤', 'bottomOnly' => true];
    $items[] = ['href' => '/register', 'label' => $t('nav.register', 'Zarejestruj się'), 'icon' => '📝', 'bottomOnly' => true];
}

return $items;
