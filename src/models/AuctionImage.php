<?php

declare(strict_types=1);

namespace App\Model;

// Zdjęcie auta (tabela auction_images): w bazie tylko ścieżka publiczna i kolejność (1 = okładka).
final class AuctionImage
{
    public function __construct(
        private readonly int $id,
        private readonly int $auctionId,
        private readonly string $path,
        private readonly int $position,
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

    // Ścieżka względem document rootu, np. /uploads/auctions/<32 znaki hex>.jpg
    public function getPath(): string
    {
        return $this->path;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isCover(): bool
    {
        return $this->position === 1;
    }
}
