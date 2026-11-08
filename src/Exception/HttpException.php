<?php

declare(strict_types=1);

namespace App\Exception;

/*
Wyjątek niosący kod statusu HTTP. Rzuca go router (zły format parametru -> 400,
brak trasy -> 404) oraz kontrolery i serwisy (np. brak aukcji w bazie -> 404).
Przechwytuje go front controller i przekazuje do ErrorController.
*/
final class HttpException extends \RuntimeException
{
    private function __construct(
        private readonly int $statusCode,
        string $message,
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self(400, $message);
    }

    public static function notFound(string $message = 'Not found'): self
    {
        return new self(404, $message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
