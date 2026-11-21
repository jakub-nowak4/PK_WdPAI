<?php

declare(strict_types=1);

namespace App\Model;

// Powiadomienie w serwisie (tabela notifications): przebicie, wygrana, koniec aukcji.
final class Notification
{
    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly int $auctionId,
        private readonly NotificationType $type,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?\DateTimeImmutable $readAt,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAuctionId(): int
    {
        return $this->auctionId;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }
}
