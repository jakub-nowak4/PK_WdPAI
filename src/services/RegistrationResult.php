<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;

/*
Wynik rejestracji: zapisane konto oraz informacja, czy e-mail powitalny wyszedł.
Konto istnieje niezależnie od poczty; kontroler użyje flagi tylko do doboru komunikatu.
*/
final class RegistrationResult
{
    public function __construct(
        private readonly User $user,
        private readonly bool $welcomeMailSent,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isWelcomeMailSent(): bool
    {
        return $this->welcomeMailSent;
    }
}
