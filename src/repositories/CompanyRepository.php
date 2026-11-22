<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Company;
use App\Model\CompanyApplication;
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

    /**
     * Panel administratora (docs/api.md 2.6): firmy z e-mailem konta, opcjonalnie tylko w danym statusie
     * (null = wszystkie). Najstarsze zgłoszenia pierwsze, id jako drugi klucz daje stały porządek.
     *
     * @return list<CompanyApplication>
     */
    public function findApplications(?CompanyStatus $status): array
    {
        $sql = 'SELECT c.id, c.user_id, c.name, c.city, c.phone, c.status, c.reviewed_by, c.reviewed_at, c.created_at, u.email
                FROM companies c
                JOIN users u ON u.id = c.user_id';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE c.status = :status';
            $params['status'] = $status->value;
        }

        $statement = $this->pdo->prepare($sql . ' ORDER BY c.created_at, c.id');
        $statement->execute($params);

        return array_map(
            fn (array $row): CompanyApplication => new CompanyApplication($this->mapRow($row), (string) $row['email']),
            $statement->fetchAll()
        );
    }

    /**
     * Liczba firm w każdym statusie (kafelki panelu); status bez firm ma 0.
     *
     * @return array{pending: int, approved: int, rejected: int}
     */
    public function countByStatus(): array
    {
        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

        foreach ($this->pdo->query('SELECT status, COUNT(*) AS total FROM companies GROUP BY status') as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
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
    // Warunek status = 'pending' jest w samym UPDATE (atomowo, bez wyścigu dwóch administratorów): zwraca null, gdy
    // firmy nie ma albo nie była pending. Który z tych przypadków (404 czy 409) to jest, rozstrzyga serwis przez findById().
    public function updateStatus(int $id, CompanyStatus $status, int $reviewedBy): ?Company
    {
        $statement = $this->pdo->prepare(
            'UPDATE companies
             SET status = :status, reviewed_by = :reviewed_by, reviewed_at = now()
             WHERE id = :id AND status = \'pending\'
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
