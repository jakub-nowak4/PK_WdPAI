<?php

declare(strict_types=1);

namespace App\Model;

/*
Aukcja (tabela auctions). Aktualnej ceny nie przechowuje: to MAX(bids.amount) albo cena wywoławcza,
liczone w widoku auction_summary (model AuctionSummary). Model nie zawiera SQL ani czasu serwera.
*/
final class Auction
{
    public function __construct(
        private readonly int $id,
        private readonly int $companyId,
        private readonly int $brandId,
        private readonly string $title,
        private readonly string $model,
        private readonly int $year,
        private readonly int $mileageKm,
        private readonly Fuel $fuel,
        private readonly Transmission $transmission,
        private readonly string $originCountry,
        private readonly ?string $vin,
        private readonly string $description,
        private readonly Money $startingPrice,
        private readonly Money $minIncrement,
        private readonly \DateTimeImmutable $startsAt,
        private readonly \DateTimeImmutable $endsAt,
        private readonly \DateTimeImmutable $originalEndsAt,
        private readonly AuctionStatus $status,
        private readonly ?int $winningBidId,
        private readonly ?\DateTimeImmutable $closedAt,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }

    public function getBrandId(): int
    {
        return $this->brandId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMileageKm(): int
    {
        return $this->mileageKm;
    }

    public function getFuel(): Fuel
    {
        return $this->fuel;
    }

    public function getTransmission(): Transmission
    {
        return $this->transmission;
    }

    // Dwuliterowy kod kraju pochodzenia (ISO 3166-1 alpha-2).
    public function getOriginCountry(): string
    {
        return $this->originCountry;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStartingPrice(): Money
    {
        return $this->startingPrice;
    }

    public function getMinIncrement(): Money
    {
        return $this->minIncrement;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    // Aktualny koniec (po ewentualnych przedłużeniach anti-sniping).
    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getOriginalEndsAt(): \DateTimeImmutable
    {
        return $this->originalEndsAt;
    }

    public function getStatus(): AuctionStatus
    {
        return $this->status;
    }

    public function getWinningBidId(): ?int
    {
        return $this->winningBidId;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isActive(): bool
    {
        return $this->status === AuctionStatus::Active;
    }

    public function isEnded(): bool
    {
        return $this->status === AuctionStatus::Ended;
    }

    public function isExtended(): bool
    {
        return $this->endsAt > $this->originalEndsAt;
    }
}
