<?php

declare(strict_types=1);

namespace App\Model;

/*
Dane ekranu "Aukcja wygrana" (docs/api.md 2.3): podsumowanie aukcji z widoku auction_summary (tytuł, marka, firma,
okładka, liczba ofert), cena końcowa (kwota zwycięskiej oferty) i czas zamknięcia ustawiony przez CRON.
Celowo bez ofert i danych innych licytujących. Niemutowalny read-model bez SQL, składany przez WonAuctionService.
*/
final class WonAuction
{
    public function __construct(
        private readonly AuctionSummary $summary,
        private readonly Money $finalPrice,
        private readonly ?\DateTimeImmutable $closedAt,
    ) {
    }

    public function getSummary(): AuctionSummary
    {
        return $this->summary;
    }

    public function getFinalPrice(): Money
    {
        return $this->finalPrice;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }
}
