<?php

declare(strict_types=1);

namespace App\Model;

// Oferta (tabela bids): encja asocjacyjna użytkownik - aukcja z kwotą i czasem.
final class Bid
{
    public function __construct(
        private readonly int $id,
        private readonly int $auctionId,
        private readonly int $userId,
        private readonly Money $amount,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuctionId(): int
    {
        return $this->auctionId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
