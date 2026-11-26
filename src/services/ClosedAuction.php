<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Money;

// Wynik zamknięcia jednej aukcji w AuctionCloser: kto wygrał i czy zwycięzca ma jeszcze dostać e-mail.
final class ClosedAuction
{
    public function __construct(
        private readonly int $auctionId,
        private readonly ?int $winningBidId,
        private readonly ?int $winnerUserId,
        private readonly ?Money $finalPrice,
        private readonly bool $winnerNotificationCreated,
    ) {
    }

    public function getAuctionId(): int
    {
        return $this->auctionId;
    }

    public function getWinningBidId(): ?int
    {
        return $this->winningBidId;
    }

    public function getWinnerUserId(): ?int
    {
        return $this->winnerUserId;
    }

    public function getFinalPrice(): ?Money
    {
        return $this->finalPrice;
    }

    public function hasWinner(): bool
    {
        return $this->winnerUserId !== null;
    }

    // true tylko wtedy, gdy powiadomienie 'won' powstało teraz (createOnce nie zwrócił null): ponowny przebieg
    // nie wyśle drugiego e-maila (docs/api.md 2.4).
    public function shouldEmailWinner(): bool
    {
        return $this->winnerUserId !== null && $this->finalPrice !== null && $this->winnerNotificationCreated;
    }
}
