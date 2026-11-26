<?php

declare(strict_types=1);

namespace App\Service;

// Wynik jednego przebiegu CRON zamykającego aukcje (do podsumowania w logu i kodu wyjścia skryptu).
final class CloseReport
{
    public function __construct(
        private readonly int $activated,
        private readonly int $closed,
        private readonly int $skipped,
        private readonly int $failed,
        private readonly int $withWinner,
        private readonly int $mailsSent,
        private readonly int $mailsFailed,
    ) {
    }

    // Aukcje zaplanowane, które właśnie stały się aktywne.
    public function getActivated(): int
    {
        return $this->activated;
    }

    // Aukcje zamknięte w tym przebiegu.
    public function getClosed(): int
    {
        return $this->closed;
    }

    // Aukcje wybrane do zamknięcia, ale pominięte (już zamknięte przez inny przebieg albo przedłużone w międzyczasie).
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    // Aukcje, których zamknięcie zakończyło się błędem (transakcja wycofana, spróbuje następny przebieg).
    public function getFailed(): int
    {
        return $this->failed;
    }

    // Spośród zamkniętych: te z oferta i zwycięzcą.
    public function getWithWinner(): int
    {
        return $this->withWinner;
    }

    public function getMailsSent(): int
    {
        return $this->mailsSent;
    }

    public function getMailsFailed(): int
    {
        return $this->mailsFailed;
    }

    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }
}
