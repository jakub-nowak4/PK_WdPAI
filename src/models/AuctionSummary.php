<?php

declare(strict_types=1);

namespace App\Model;

/*
Aukcja z danymi liczonymi w widoku auction_summary (lista, szczegóły, panel firmy). Zawiera Auction i dokłada
nazwę firmy i marki, aktualną cenę, minimalną następną ofertę, liczbę ofert, lidera i okładkę. Niemutowalny,
bez SQL; aktualną cenę liczy baza (MAX(bids.amount) albo cena wywoławcza), nie PHP.
*/
final class AuctionSummary
{
    public function __construct(
        private readonly Auction $auction,
        private readonly string $companyName,
        private readonly string $brandName,
        private readonly string $brandSlug,
        private readonly Money $currentPrice,
        private readonly Money $minNextBid,
        private readonly int $bidCount,
        private readonly ?int $leaderUserId,
        private readonly ?string $coverPath,
    ) {
    }

    public function getAuction(): Auction
    {
        return $this->auction;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function getBrandSlug(): string
    {
        return $this->brandSlug;
    }

    public function getCurrentPrice(): Money
    {
        return $this->currentPrice;
    }

    // Najniższa przyjmowana oferta: cena wywoławcza przy braku ofert, potem aktualna cena + krok.
    public function getMinNextBid(): Money
    {
        return $this->minNextBid;
    }

    public function getBidCount(): int
    {
        return $this->bidCount;
    }

    // Autor najwyższej oferty; null, gdy nie ma ofert.
    public function getLeaderUserId(): ?int
    {
        return $this->leaderUserId;
    }

    // Ścieżka zdjęcia z position = 1 (np. /uploads/auctions/<32 hex>.jpg); null, gdy aukcja nie ma zdjęć.
    public function getCoverPath(): ?string
    {
        return $this->coverPath;
    }
}
