<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Brand;

// Słownik marek (tabela brands). Cały SQL dla marek jest tutaj.
final class BrandRepository extends BaseRepository
{
    private const COLUMNS = 'id, name, slug';

    /**
     * @return list<Brand> posortowane alfabetycznie po nazwie
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query('SELECT ' . self::COLUMNS . ' FROM brands ORDER BY name');

        return array_map($this->mapRow(...), $statement->fetchAll());
    }

    public function findById(int $id): ?Brand
    {
        // brands.id jest SMALLINT, a PDO (EMULATE_PREPARES=false) wiąże int jako INTEGER.
        // CAST na BIGINT daje porównanie smallint = bigint: dla dużych id wynik to null,
        // a nie błąd 22003 "value out of range for type smallint". Indeks dalej działa.
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM brands WHERE id = CAST(:id AS BIGINT)'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    public function findBySlug(string $slug): ?Brand
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM brands WHERE slug = :slug');
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): Brand
    {
        return new Brand((int) $row['id'], (string) $row['name'], (string) $row['slug']);
    }
}
