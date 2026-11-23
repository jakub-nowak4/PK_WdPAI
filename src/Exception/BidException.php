<?php

declare(strict_types=1);

namespace App\Exception;

/*
Oferta odrzucona regułą biznesową (docs/api.md 3.2, tabela błędów). Niesie status HTTP (403, 404, 409, 422),
kod błędu z JSON-a (np. bid_too_low), już przetłumaczony komunikat i opcjonalne szczegóły (details), np. minNextBid.
Kontroler API (T-033) zamienia go na odpowiedź JSON, bez własnej wiedzy o regułach.
*/
final class BidException extends \RuntimeException
{
    /**
     * @param array<string, int|string> $details
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $errorCode,
        string $message,
        private readonly array $details = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, int|string>
     */
    public function details(): array
    {
        return $this->details;
    }
}
