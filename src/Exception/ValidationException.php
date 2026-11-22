<?php

declare(strict_types=1);

namespace App\Exception;

/*
Dane formularza nie przeszły reguł serwisu (docs/api.md, 1.7). Niesie błędy przy
polach (nazwa pola => już przetłumaczony komunikat) i status odpowiedzi: 422 dla
złego formatu lub zakresu, 409 dla konfliktu ze stanem (np. zajęty e-mail).
Kontroler ustawia ten status i renderuje formularz z błędami.
*/
final class ValidationException extends \RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private readonly array $errors, private readonly int $statusCode = 422)
    {
        parent::__construct('Validation failed', $statusCode);
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
