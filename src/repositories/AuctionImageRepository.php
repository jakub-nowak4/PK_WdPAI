<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\AuctionImage;
use DateTimeImmutable;

// Zdjęcia aukcji (tabela auction_images). Cały SQL dla zdjęć jest tutaj; walidację, transakcje
// i usuwanie plików z dysku robi serwis. Przy usunięciu aukcji wiersze znikają kaskadowo (ON DELETE CASCADE).
final class AuctionImageRepository extends BaseRepository
{
    private const COLUMNS = 'id, auction_id, path, position, created_at';

    // Ścieżka pochodzi z UploadService (/uploads/auctions/<32 znaki hex>.<jpg|png|webp>), nie z danych użytkownika.
    // UNIQUE (auction_id, position) i CHECK position 1–8 pilnuje baza; serwis zamienia błędy 23505/23514 na komunikat.
    // Brak aukcji kończy się PDOException z SQLSTATE 23503. position to SMALLINT, więc wartość z zakresu 1–8 jest bezpieczna.
    public function create(int $auctionId, string $path, int $position): AuctionImage
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO auction_images (auction_id, path, position)
             VALUES (:auction_id, :path, :position)
             RETURNING ' . self::COLUMNS
        );
        $statement->execute(['auction_id' => $auctionId, 'path' => $path, 'position' => $position]);

        return $this->mapRow($statement->fetch());
    }

    /**
     * Zdjęcia aukcji w kolejności wyświetlania; pierwsze (position 1) to okładka.
     *
     * @return list<AuctionImage>
     */
    public function findByAuctionId(int $auctionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM auction_images WHERE auction_id = :auction_id ORDER BY position'
        );
        $statement->execute(['auction_id' => $auctionId]);

        return array_map($this->mapRow(...), $statement->fetchAll());
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): AuctionImage
    {
        return new AuctionImage(
            (int) $row['id'],
            (int) $row['auction_id'],
            (string) $row['path'],
            (int) $row['position'],
            new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
