<?php

declare(strict_types=1);

namespace App\Model;

// Konto użytkownika (tabela users). Model nie zawiera SQL; wiersze mapuje UserRepository.
final class User
{
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $passwordHash,
        private readonly string $name,
        private readonly Role $role,
        private readonly string $locale,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    // Kod języka interfejsu: 'pl' albo 'en'.
    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function hasRole(Role $role): bool
    {
        return $this->role === $role;
    }
}
