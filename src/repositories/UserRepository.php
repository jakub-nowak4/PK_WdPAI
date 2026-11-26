<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Role;
use App\Model\User;
use DateTimeImmutable;

// Konta użytkowników (tabela users). Cały SQL dla kont jest tutaj; walidację robi AuthService.
final class UserRepository extends BaseRepository
{
    private const COLUMNS = 'id, email, password_hash, name, role, locale, created_at';
    private const LOCALES = ['pl', 'en'];

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    // Porównanie bez względu na wielkość liter, zgodnie z indeksem users_email_lower_uq.
    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE LOWER(email) = LOWER(:email)'
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    // $passwordHash to wynik password_hash() z serwisu; $email jest już znormalizowany (małe litery).
    // Duplikat e-maila kończy się PDOException z SQLSTATE 23505, którą obsługuje serwis (409).
    public function create(string $email, string $passwordHash, string $name, Role $role, string $locale): User
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, name, role, locale)
             VALUES (:email, :password_hash, :name, :role, :locale)
             RETURNING ' . self::COLUMNS
        );
        $statement->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
            'name' => $name,
            'role' => $role->value,
            'locale' => $locale,
        ]);

        return $this->mapRow($statement->fetch());
    }

    // Zapamiętuje język konta (język e-maili wysyłanych bez sesji, np. przez CRON).
    // Dozwolone wartości są zgodne z CHECK users_locale_chk; zwraca false, gdy konta nie ma.
    public function updateLocale(int $userId, string $locale): bool
    {
        if (!in_array($locale, self::LOCALES, true)) {
            throw new \InvalidArgumentException('Unsupported locale: ' . $locale);
        }

        $statement = $this->pdo->prepare('UPDATE users SET locale = :locale WHERE id = :id');
        $statement->execute(['locale' => $locale, 'id' => $userId]);

        return $statement->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): User
    {
        return new User(
            (int) $row['id'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['name'],
            Role::from((string) $row['role']),
            (string) $row['locale'],
            new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
