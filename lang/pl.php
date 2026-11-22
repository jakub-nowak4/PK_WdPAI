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
    'error.login_required' => 'Zaloguj się, aby wykonać tę operację.',
    'auth.session_expired' => 'Sesja wygasła. Zaloguj się ponownie.',
    'error.language_invalid' => 'Nieobsługiwany język.',
    'error.400.label' => 'Błąd żądania • kod błędu 400',
    'error.400.title' => 'Nieprawidłowe żądanie',
    'error.400.description' => 'Adres zawiera nieprawidłowy parametr.',
    'error.403.label' => 'Autoryzacja • kod błędu 403',
    'error.403.title' => 'Brak dostępu',
    'error.403.description' => 'Nie masz uprawnień do tej strony.',
    'error.404.label' => 'Błąd HTTP 404 • zasób niedostępny',
    'error.404.title' => 'Nie znaleziono strony',
    'error.404.description' => 'Aukcja mogła zostać zakończona lub usunięta.',
    'error.browse_auctions' => 'Przeglądaj aukcje',
    'error.back_home' => 'Wróć do strony głównej',
    'company.error.has_bids' => 'Nie można usunąć aukcji, w której są oferty.',

    // Walidacja formularzy
    'validation.required' => 'To pole jest wymagane.',
    'validation.email' => 'Podaj poprawny adres e-mail.',
    'validation.min_length' => 'Minimalna liczba znaków: {min}.',
    'validation.max_length' => 'Maksymalna liczba znaków: {max}.',
    'validation.password_max_bytes' => 'Hasło może mieć najwyżej {max} bajty.',
    'validation.password_mismatch' => 'Hasła nie są takie same.',
    'validation.number' => 'Podaj poprawną liczbę.',
    'validation.min_value' => 'Wartość nie może być mniejsza niż {min}.',
    'validation.max_value' => 'Wartość nie może być większa niż {max}.',
    'validation.invalid_format' => 'Niepoprawny format.',
    'validation.email_taken' => 'Konto z tym adresem e-mail już istnieje.',

    // Logowanie
    'auth.invalid_credentials' => 'Nieprawidłowy e-mail lub hasło.',
    'auth.logged_out' => 'Wylogowano.',

    // Rejestracja
    'register.success' => 'Konto zostało utworzone. Witaj w MotoBid!',
    'register_company.success' => 'Konto firmy zostało utworzone. Czeka na zatwierdzenie przez administratora.',
];
