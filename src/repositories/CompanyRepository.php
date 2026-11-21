<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Company;
use App\Model\CompanyStatus;
use DateTimeImmutable;

// Profile firm (tabela companies). Cały SQL dla firm jest tutaj; walidację i 409 (firma nie jest pending) robi serwis.
final class CompanyRepository extends BaseRepository
{
    private const COLUMNS = 'id, user_id, name, city, phone, status, reviewed_by, reviewed_at, created_at';

    public function findById(int $id): ?Company
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM companies WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    // Firma konta DEALER (relacja 1:1, companies_user_uq).
    public function findByUserId(int $userId): ?Company
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM companies WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    /**
     * Panel administratora, filtr ?status=pending|approved|rejected (docs/api.md 2.6); najstarsze zgłoszenia pierwsze.
     *
     * @return list<Company>
     */
    public function findAllByStatus(CompanyStatus $status): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM companies WHERE status = :status ORDER BY created_at, id'
        );
        $statement->execute(['status' => $status->value]);

        return array_map($this->mapRow(...), $statement->fetchAll());
    }

    /**
     * Panel administratora, filtr ?status=all.
     *
     * @return list<Company>
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query('SELECT ' . self::COLUMNS . ' FROM companies ORDER BY created_at, id');

        return array_map($this->mapRow(...), $statement->fetchAll());
    }

    // Status ustawia baza (DEFAULT 'pending'). Wywołuje serwis rejestracji firmy w transakcji razem z UserRepository::create().
    // Drugi profil z tym samym user_id kończy się PDOException z SQLSTATE 23505 (companies_user_uq).
    public function create(int $userId, string $name, string $city, ?string $phone): Company
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (user_id, name, city, phone)
             VALUES (:user_id, :name, :city, :phone)
             RETURNING ' . self::COLUMNS
        );
        $statement->execute([
            'user_id' => $userId,
            'name' => $name,
            'city' => $city,
            'phone' => $phone,
        ]);

        return $this->mapRow($statement->fetch());
    }

    // Zatwierdzenie lub odrzucenie przez administratora. reviewed_at ustawia baza (czas serwera), bo CHECK
    // companies_reviewed_chk wymaga reviewed_at dla każdego statusu poza pending; dlatego $status ma być Approved albo Rejected.
    // Zwraca null, gdy firma nie istnieje. Sprawdzenie, że była pending (409), robi serwis.
    public function updateStatus(int $id, CompanyStatus $status, int $reviewedBy): ?Company
    {
        $statement = $this->pdo->prepare(
            'UPDATE companies
             SET status = :status, reviewed_by = :reviewed_by, reviewed_at = now()
             WHERE id = :id
             RETURNING ' . self::COLUMNS
        );
        $statement->execute([
            'status' => $status->value,
            'reviewed_by' => $reviewedBy,
            'id' => $id,
        ]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): Company
    {
        return new Company(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['name'],
            (string) $row['city'],
            $row['phone'] === null ? null : (string) $row['phone'],
            CompanyStatus::from((string) $row['status']),
            $row['reviewed_by'] === null ? null : (int) $row['reviewed_by'],
            $row['reviewed_at'] === null ? null : new DateTimeImmutable((string) $row['reviewed_at']),
            new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
