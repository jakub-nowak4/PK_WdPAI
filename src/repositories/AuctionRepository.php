<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Auction;
use App\Model\AuctionStatus;
use App\Model\Fuel;
use App\Model\Money;
use App\Model\NewAuction;
use App\Model\Transmission;
use DateTimeImmutable;

// Aukcje (tabela auctions). Cały SQL dla aukcji jest tutaj; walidację, transakcje i reguły (np. 403 przy aukcji
// z ofertami) robi serwis. Zdjęcia, lista z filtrami i agregaty (cena, liczba ofert) są w osobnych klasach.
final class AuctionRepository extends BaseRepository
{
    private const COLUMNS = [
        'id', 'company_id', 'brand_id', 'title', 'model', 'year', 'mileage_km', 'fuel', 'transmission',
        'origin_country', 'vin', 'description', 'starting_price', 'min_increment', 'starts_at', 'ends_at',
        'original_ends_at', 'status', 'winning_bid_id', 'closed_at', 'created_at',
    ];

    // auctions.id to INTEGER, a id z URL przeszło router (1…2147483647), więc porównujemy wprost.
    public function findById(int $id): ?Auction
    {
        $statement = $this->pdo->prepare('SELECT ' . $this->columns() . ' FROM auctions WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    // Blokada wiersza aukcji do końca transakcji serwisu (oferta: docs/api.md 3.2, usuwanie: 2.5).
    // Poza transakcją blokada znika od razu, więc wywołuje to wyłącznie serwis po beginTransaction().
    public function findForUpdate(int $id): ?Auction
    {
        $statement = $this->pdo->prepare('SELECT ' . $this->columns() . ' FROM auctions WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    // Status (active, gdy starts_at <= now(), inaczej scheduled) i original_ends_at liczy sam INSERT na czasie serwera
    // bazy (docs/api.md 1.8). Natywne prepared statements nie pozwalają powtórzyć nazwy parametru, więc startsAt
    // idzie pod dwiema nazwami (starts_at, starts_at_check), a endsAt pod ends_at i original_ends_at.
    // brand_id to SMALLINT, ale $new->brandId pochodzi z Brand::getId() (wiersz słownika z bazy), więc mieści się w typie.
    // Naruszenie CHECK (luka w walidatorze) kończy się PDOException z SQLSTATE 23514.
    public function create(NewAuction $new): Auction
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO auctions (company_id, brand_id, title, model, year, mileage_km, fuel, transmission,
                                   origin_country, vin, description, starting_price, min_increment,
                                   starts_at, ends_at, original_ends_at, status)
             VALUES (:company_id, :brand_id, :title, :model, :year, :mileage_km, :fuel, :transmission,
                     :origin_country, :vin, :description, :starting_price, :min_increment,
                     :starts_at, :ends_at, :original_ends_at,
                     CASE WHEN CAST(:starts_at_check AS TIMESTAMPTZ) <= now() THEN \'active\' ELSE \'scheduled\' END)
             RETURNING ' . $this->columns()
        );
        $startsAt = $new->startsAt->format(DATE_ATOM);
        $endsAt = $new->endsAt->format(DATE_ATOM);
        $statement->execute([
            'company_id' => $new->companyId,
            'brand_id' => $new->brandId,
            'title' => $new->title,
            'model' => $new->model,
            'year' => $new->year,
            'mileage_km' => $new->mileageKm,
            'fuel' => $new->fuel->value,
            'transmission' => $new->transmission->value,
            'origin_country' => $new->originCountry,
            'vin' => $new->vin,
            'description' => $new->description,
            'starting_price' => $new->startingPrice->grosze(),
            'min_increment' => $new->minIncrement->grosze(),
            'starts_at' => $startsAt,
            'starts_at_check' => $startsAt,
            'ends_at' => $endsAt,
            'original_ends_at' => $endsAt,
        ]);

        return $this->mapRow($statement->fetch());
    }

    // Bez sprawdzania ofert: aukcja z ofertami kończy się PDOException z SQLSTATE 23001 (restrict_violation
    // z bids.auction_id ON DELETE RESTRICT, nie 23503), którą serwis zamienia na 403. Zdjęcia znikają z bazy
    // kaskadowo; pliki z dysku usuwa serwis. Zwraca false, gdy aukcja nie istniała.
    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM auctions WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    // Lista kolumn auctions z opcjonalnym prefiksem aliasu (np. 'a.'): jedno źródło dla wszystkich zapytań.
    private function columns(string $prefix = ''): string
    {
        return implode(', ', array_map(static fn (string $column): string => $prefix . $column, self::COLUMNS));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): Auction
    {
        return new Auction(
            (int) $row['id'],
            (int) $row['company_id'],
            (int) $row['brand_id'],
            (string) $row['title'],
            (string) $row['model'],
            (int) $row['year'],
            (int) $row['mileage_km'],
            Fuel::from((string) $row['fuel']),
            Transmission::from((string) $row['transmission']),
            (string) $row['origin_country'],
            $row['vin'] === null ? null : (string) $row['vin'],
            (string) $row['description'],
            new Money((int) $row['starting_price']),
            new Money((int) $row['min_increment']),
            new DateTimeImmutable((string) $row['starts_at']),
            new DateTimeImmutable((string) $row['ends_at']),
            new DateTimeImmutable((string) $row['original_ends_at']),
            AuctionStatus::from((string) $row['status']),
            $row['winning_bid_id'] === null ? null : (int) $row['winning_bid_id'],
            $row['closed_at'] === null ? null : new DateTimeImmutable((string) $row['closed_at']),
            new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
