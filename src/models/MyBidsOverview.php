<?php

declare(strict_types=1);

namespace App\Model;

/*
Dane ekranu "Moje oferty": lista po filtrze oraz liczniki zakładek i suma z całej (niefiltrowanej) listy,
bo widok pokazuje je także przy aktywnym filtrze. Niemutowalny, bez SQL.
*/
final class MyBidsOverview
{
    /**
     * @param list<MyBid> $bids po filtrze statusu, od ostatnio złożonej oferty
     * @param array<string, int> $counts klucze: all, leading, outbid, won, lost
     */
    public function __construct(
        private readonly array $bids,
        private readonly array $counts,
        private readonly Money $runningTotal,
        private readonly \DateTimeImmutable $serverTime,
    ) {
    }

    /**
     * @return list<MyBid>
     */
    public function getBids(): array
    {
        return $this->bids;
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return $this->counts;
    }

    // Suma najwyższych ofert w trwających aukcjach (leading + outbid).
    public function getRunningTotal(): Money
    {
        return $this->runningTotal;
    }

    public function getServerTime(): \DateTimeImmutable
    {
        return $this->serverTime;
    }
}
