<?php

declare(strict_types=1);

namespace App\Exception;

/*
Wyjątek niosący kod statusu HTTP. Rzuca go router (zły format parametru -> 400,
brak trasy -> 404) oraz kontrolery i serwisy (np. brak aukcji w bazie -> 404,
brak logowania -> 401, brak uprawnień lub zły token CSRF -> 403).
Przechwytuje go front controller i przekazuje do ErrorController.
Opcjonalny errorCode to kod błędu z JSON-a (docs/api.md, sekcja 4), np. csrf_invalid.
*/
final class HttpException extends \RuntimeException
{
    private function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly ?string $errorCode = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self(400, $message);
    }

    /*
    Brak zalogowania. Dla HTML ErrorController robi 303 na /login, dla /api/* zwraca
    401 JSON (login_required).
    */
    public static function unauthorized(): self
    {
        return new self(401, '', 'login_required');
    }

    /*
    Pusty komunikat oznacza "brak własnego komunikatu": ErrorController
    pokaże wtedy domyślny tekst 403.
    */
    public static function forbidden(string $message = '', ?string $errorCode = null): self
    {
        return new self(403, $message, $errorCode);
    }

    /*
    Firma DEALER niezatwierdzona (status pending lub rejected, docs/api.md 1.5): 403, a
    ErrorController zamiast strony 403 pokazuje ekran "konto czeka na zatwierdzenie".
    Komunikat wyjątku niesie status firmy (wartość CompanyStatus).
    */
    public static function companyNotApproved(string $companyStatus): self
    {
        return new self(403, $companyStatus, 'company_not_approved');
    }

    /*
    Brak lub zły token CSRF (403, w JSON kod csrf_invalid).
    */
    public static function csrfInvalid(): self
    {
        return new self(403, '', 'csrf_invalid');
    }

    public static function notFound(string $message = 'Not found'): self
    {
        return new self(404, $message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }
}
