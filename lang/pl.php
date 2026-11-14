<?php

declare(strict_types=1);

/*
Katalog tłumaczeń PL (język domyślny i zapasowy). Płaska tablica kluczy kropkowanych.
Symbole {nazwa} są podstawiane przez Translator::translate().
Pełny zestaw tekstów widoków dodaje T-048; tu klucze startowe.
*/
return [
    // Nawigacja i elementy wspólne
    'nav.skip' => 'Przejdź do treści',
    'nav.main' => 'Nawigacja główna',
    'nav.mobile' => 'Nawigacja mobilna',
    'nav.language' => 'Język',
    'nav.auctions' => 'Aukcje',
    'nav.my_bids' => 'Moje oferty',
    'nav.notifications' => 'Powiadomienia',
    'nav.company_panel' => 'Panel firmy',
    'nav.add_auction' => 'Dodaj aukcję',
    'nav.account_status' => 'Status konta',
    'nav.admin_panel' => 'Panel administratora',
    'nav.login' => 'Zaloguj się',
    'nav.register' => 'Zarejestruj się',
    'nav.logout' => 'Wyloguj się',
    'theme.toggle' => 'Zmień motyw',
    'theme.to_dark' => 'Włącz ciemny motyw',
    'common.cancel' => 'Anuluj',
    'common.close' => 'Zamknij',
    'error.back_to_auctions' => 'Wróć do aukcji',

    // Błędy HTTP i bezpieczeństwo
    'error.bad_request' => 'Nieprawidłowe żądanie.',
    'error.forbidden' => 'Nie masz uprawnień do tej strony.',
    'error.not_found' => 'Nie znaleziono strony.',
    'error.csrf_invalid' => 'Sesja formularza wygasła. Odśwież stronę i spróbuj ponownie.',
    'error.language_invalid' => 'Nieobsługiwany język.',

    // Walidacja formularzy
    'validation.required' => 'To pole jest wymagane.',
    'validation.email' => 'Podaj poprawny adres e-mail.',
    'validation.min_length' => 'Wpisz co najmniej {min} znaków.',
    'validation.max_length' => 'Wpisz co najwyżej {max} znaków.',
    'validation.password_mismatch' => 'Hasła nie są takie same.',
    'validation.number' => 'Podaj poprawną liczbę.',
    'validation.min_value' => 'Wartość nie może być mniejsza niż {min}.',
    'validation.max_value' => 'Wartość nie może być większa niż {max}.',
    'validation.invalid_format' => 'Niepoprawny format.',
];
