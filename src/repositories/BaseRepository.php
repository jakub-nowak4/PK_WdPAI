<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

// Wspólna baza repozytoriów: trzyma wstrzyknięte połączenie PDO (z App\Database::getConnection()).
// Repozytorium nie otwiera transakcji; robi to serwis na tym samym PDO (docs/api.md 1.8).
abstract class BaseRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }
}
