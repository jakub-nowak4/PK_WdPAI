<?php

declare(strict_types=1);

namespace App\Exception;

/*
Zły e-mail lub hasło przy logowaniu (docs/api.md, 2.4). Komunikat jest jeden i
ogólny (już przetłumaczony), żeby nie zdradzać, które pole było błędne.
Kontroler odpowiada na to statusem 401 i ponownie wyświetla formularz.
*/
final class InvalidCredentialsException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 401);
    }
}
