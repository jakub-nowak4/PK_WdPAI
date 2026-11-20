<?php

declare(strict_types=1);

namespace App\Model;

// Profil firmy 1:1 z kontem DEALER (tabela companies).
final class Company
{
    public function __construct(
        private readonly int $id,
        private readonly int $userId,
        private readonly string $name,
        private readonly string $city,
        private readonly ?string $phone,
        private readonly CompanyStatus $status,
        private readonly ?int $reviewedBy,
        private readonly ?\DateTimeImmutable $reviewedAt,
        private readonly \DateTimeImmutable $createdAt,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getStatus(): CompanyStatus
    {
        return $this->status;
    }

    // Id administratora, który rozpatrzył wniosek (null, dopóki pending).
    public function getReviewedBy(): ?int
    {
        return $this->reviewedBy;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isApproved(): bool
    {
        return $this->status === CompanyStatus::Approved;
    }
}
