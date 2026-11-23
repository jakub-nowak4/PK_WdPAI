<?php

declare(strict_types=1);

namespace App\Model;

/*
Oferta w publicznej historii aukcji (docs/api.md 2.3, 3.1): bez danych osobowych, licytujący to tylko numer
stały w obrębie aukcji ("Licytujący 3" składa widok z tłumaczenia). isYou ustawia serwis dla zalogowanego.
Niemutowalny, bez SQL.
*/
final class PublicBid
{
    public function __construct(
        private readonly int $bidderNumber,
        private readonly Money $amount,
        private readonly \DateTimeImmutable $placedAt,
        private readonly bool $isYou,
    ) {
    }

    // Numer wg kolejności pierwszej oferty danej osoby w tej aukcji (1, 2, 3...).
    public function getBidderNumber(): int
    {
        return $this->bidderNumber;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getPlacedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function isYou(): bool
    {
        return $this->isYou;
    }
}
