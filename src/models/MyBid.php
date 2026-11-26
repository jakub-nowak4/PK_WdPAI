<?php

declare(strict_types=1);

namespace App\Model;

/*
Wiersz ekranu "Moje oferty" (docs/api.md 2.4): aukcja (AuctionSummary), najwyższa oferta zalogowanego w tej
aukcji i jego status liczony na czasie serwera. Niemutowalny, bez SQL.
*/
final class MyBid
{
    public function __construct(
        private readonly AuctionSummary $summary,
        private readonly Money $myHighestBid,
        private readonly MyBidStatus $status,
    ) {
    }

    public function getSummary(): AuctionSummary
    {
        return $this->summary;
    }

    public function getMyHighestBid(): Money
    {
        return $this->myHighestBid;
    }

    public function getStatus(): MyBidStatus
    {
        return $this->status;
    }

    // Kwota, o którą aktualna cena przewyższa ofertę użytkownika (do podpisu "+X zł" przy przebiciu).
    public function getGap(): Money
    {
        $gap = $this->summary->getCurrentPrice()->grosze() - $this->myHighestBid->grosze();

        return new Money(max(0, $gap));
    }

    // Link do aukcji; "aukcja wygrana" tylko po zamknięciu przez CRON (status ended w bazie), inaczej trasa da 404.
    public function getLink(): string
    {
        $id = $this->summary->getAuction()->getId();

        return $this->status === MyBidStatus::Won && $this->summary->getAuction()->isEnded()
            ? '/auctions/' . $id . '/won'
            : '/auctions/' . $id;
    }
}
