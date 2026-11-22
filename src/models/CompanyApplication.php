<?php

declare(strict_types=1);

namespace App\Model;

// Wiersz panelu administratora: firma razem z e-mailem jej konta (users.email). Bez SQL.
final class CompanyApplication
{
    public function __construct(
        private readonly Company $company,
        private readonly string $email,
    ) {
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
