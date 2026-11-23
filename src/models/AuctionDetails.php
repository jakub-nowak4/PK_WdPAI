<?php

declare(strict_types=1);

namespace App\Model;

/*
Dane ekranu szczegółów aukcji (docs/api.md 2.3): podsumowanie z widoku auction_summary, zdjęcia, ostatnie oferty
(anonimowe, najnowsza pierwsza) i czas serwera z chwili odczytu. Status i sekundy do końca liczone na czasie
serwera (reguła 3), nie z samej kolumny status, bo CRON może jeszcze nie zamknąć aukcji po czasie.
Niemutowalny read-model bez SQL, składany przez AuctionDetailsService.
*/
final class AuctionDetails
{
    /**
     * @param list<AuctionImage> $images
     * @param list<PublicBid> $recentBids
     */
    public function __construct(
        private readonly AuctionSummary $summary,
        private readonly array $images,
        private readonly array $recentBids,
        private readonly \DateTimeImmutable $serverTime,
    ) {
    }

    public function getSummary(): AuctionSummary
    {
        return $this->summary;
    }

    /**
     * Zdjęcia w kolejności wyświetlania (pierwsze to okładka); pusta lista, gdy aukcja nie ma zdjęć.
     *
     * @return list<AuctionImage>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Do 10 ostatnich ofert, najnowsza pierwsza.
     *
     * @return list<PublicBid>
     */
    public function getRecentBids(): array
    {
        return $this->recentBids;
    }

    public function getServerTime(): \DateTimeImmutable
    {
        return $this->serverTime;
    }

    // ended po endsAt (albo gdy CRON już zamknął), scheduled przed startsAt, inaczej active.
    public function getStatus(): AuctionStatus
    {
        $auction = $this->summary->getAuction();

        if ($auction->isEnded() || $this->serverTime >= $auction->getEndsAt()) {
            return AuctionStatus::Ended;
        }

        return $this->serverTime < $auction->getStartsAt() ? AuctionStatus::Scheduled : AuctionStatus::Active;
    }

    // max(0, endsAt - czas serwera) w sekundach.
    public function getSecondsLeft(): int
    {
        return max(0, $this->summary->getAuction()->getEndsAt()->getTimestamp() - $this->serverTime->getTimestamp());
    }
}
