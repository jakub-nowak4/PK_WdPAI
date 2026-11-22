<?php

declare(strict_types=1);

namespace App\Exception;

/*
Błąd wysyłki poczty (połączenie, odpowiedź serwera SMTP, niepoprawne dane wiadomości).
Komunikat opisuje etap i kod odpowiedzi, ale nigdy nie zawiera adresu odbiorcy ani
treści listu, bo trafia do logu. Wywołujący łapie go, loguje i dalej obsługuje żądanie:
nieudana poczta nie cofa zapisanych danych.
*/
final class MailException extends \RuntimeException
{
}
