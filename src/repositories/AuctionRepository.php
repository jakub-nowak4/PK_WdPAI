<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Auction;
use App\Model\AuctionFilter;
use App\Model\AuctionSort;
use App\Model\AuctionStatus;
use App\Model\AuctionSummary;
use App\Model\Fuel;
use App\Model\Money;
use App\Model\NewAuction;
use App\Model\Transmission;
use DateTimeImmutable;
use PDO;

// Aukcje (tabela auctions). Cały SQL dla aukcji jest tutaj; walidację, transakcje i reguły (np. 403 przy aukcji
// z ofertami) robi serwis. Agregaty (cena, liczba ofert, lider, okładka) czytamy z widoku auction_summary
// (AuctionSummary); zdjęcia są w AuctionImageRepository.
final class AuctionRepository extends BaseRepository
{
    private const COLUMNS = [
        'id', 'company_id', 'brand_id', 'title', 'model', 'year', 'mileage_km', 'fuel', 'transmission',
        'origin_country', 'vin', 'description', 'starting_price', 'min_increment', 'starts_at', 'ends_at',
        'original_ends_at', 'status', 'winning_bid_id', 'closed_at', 'created_at',
    ];

    private const SUMMARY_FROM = 'FROM auctions a JOIN auction_summary s ON s.id = a.id';

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

    // Aukcja z cena, liczba ofert, liderem i okładką z widoku auction_summary: jedno zapytanie, bez N+1.
    public function findSummaryById(int $id): ?AuctionSummary
    {
        $statement = $this->pdo->prepare('SELECT ' . $this->summaryColumns() . ' ' . self::SUMMARY_FROM . ' WHERE a.id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapSummaryRow($row);
    }

    // Podsumowania wielu aukcji jednym zapytaniem (bez N+1 w "Moje oferty" i na liście powiadomień). Wynik jest
    // indeksowany id aukcji, nieistniejące id po prostu nie występują; duplikaty w $ids nie szkodzą. Bez ORDER BY
    // (kolejność daje wołający po swojej liście) i bez filtra statusu/czasu: to odczyt po id, nie lista publiczna.
    // Id trafiają do SQL jako jeden parametr: literał tablicy zbudowany z typowanych int, rzutowany na INTEGER[].
    /**
     * @param list<int> $ids
     * @return array<int, AuctionSummary>
     */
    public function findSummariesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $statement = $this->pdo->prepare(
            'SELECT ' . $this->summaryColumns() . ' ' . self::SUMMARY_FROM
            . ' WHERE a.id = ANY(CAST(:ids AS INTEGER[]))'
        );
        $statement->execute(['ids' => '{' . implode(',', array_map(static fn (int $id): string => (string) $id, $ids)) . '}']);

        $summaries = [];
        foreach ($statement->fetchAll() as $row) {
            $summary = $this->mapSummaryRow($row);
            $summaries[$summary->getAuction()->getId()] = $summary;
        }

        return $summaries;
    }

    // Lista publiczna (docs/api.md 2.2): aukcje active i scheduled z ends_at > now() (po czasie znikają od razu, nawet
    // zanim CRON je zamknie), jedna strona. Cena (filtr i sortowanie) idzie po s.current_price z widoku, nie z PHP.
    // Jedno zapytanie na listę: agregaty, marka i okładka przychodzą z widoku (bez N+1).
    /**
     * @return list<AuctionSummary>
     */
    public function findListed(AuctionFilter $filter, AuctionSort $sort, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildListFilter($filter);

        // Kolejność z białej listy (enum), nigdy z wejścia; id na końcu rozstrzyga remisy, więc strony są stabilne.
        $orderBy = match ($sort) {
            AuctionSort::Ending => 'a.ends_at ASC, a.id ASC',
            AuctionSort::Newest => 'a.created_at DESC, a.id DESC',
            AuctionSort::PriceAsc => 's.current_price ASC, a.id ASC',
            AuctionSort::PriceDesc => 's.current_price DESC, a.id DESC',
        };

        $statement = $this->pdo->prepare(
            'SELECT ' . $this->summaryColumns() . ' ' . self::SUMMARY_FROM
            . ' WHERE ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value);
        }
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return array_map($this->mapSummaryRow(...), $statement->fetchAll());
    }

    // Liczba wyników dla tych samych filtrów co findListed() (paginacja).
    public function countListed(AuctionFilter $filter): int
    {
        [$where, $params] = $this->buildListFilter($filter);

        $statement = $this->pdo->prepare('SELECT COUNT(*) ' . self::SUMMARY_FROM . ' WHERE ' . $where);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    // Aukcje firmy (panel firmy), najnowsze pierwsze; id jako drugi klucz daje stały porządek przy równym created_at.
    /**
     * @return list<AuctionSummary>
     */
    public function findSummariesByCompanyId(int $companyId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . $this->summaryColumns() . ' ' . self::SUMMARY_FROM
            . ' WHERE a.company_id = :company_id ORDER BY a.created_at DESC, a.id DESC'
        );
        $statement->execute(['company_id' => $companyId]);

        return array_map($this->mapSummaryRow(...), $statement->fetchAll());
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

    // Wspólny WHERE dla findListed() i countListed(): [fragment, parametry]. Tylko stałe fragmenty SQL; wartości z wejścia
    // trafiają wyłącznie do parametrów nazwanych. Marka po slugu (brand_slug, UNIQUE w brands), więc bez zamiany na id.
    /**
     * @return array{0: string, 1: array<string, int|string>}
     */
    private function buildListFilter(AuctionFilter $filter): array
    {
        $conditions = ["a.status IN ('active', 'scheduled')", 'a.ends_at > now()'];
        $params = [];

        if ($filter->brandSlug !== null) {
            $conditions[] = 's.brand_slug = :brand';
            $params['brand'] = $filter->brandSlug;
        }
        if ($filter->priceMax !== null) {
            $conditions[] = 's.current_price <= :price_max';
            $params['price_max'] = $filter->priceMax->grosze();
        }
        foreach ($filter->searchTerms as $i => $term) {
            // Backslash, % i _ w słowie są zwykłymi znakami, nie wzorcem (backslash to domyślny znak ucieczki w LIKE).
            $conditions[] = "(s.title || ' ' || s.brand_name) ILIKE :term" . $i;
            $params['term' . $i] = '%' . addcslashes($term, '\\%_') . '%';
        }

        return [implode(' AND ', $conditions), $params];
    }

    // Kolumny aukcji (a.*) mapuje mapRow(); z widoku dochodzą tylko agregaty i nazwy z JOIN-ów.
    private function summaryColumns(): string
    {
        return $this->columns('a.')
            . ', s.company_name, s.brand_name, s.brand_slug, s.current_price, s.min_next_bid,'
            . ' s.bid_count, s.leader_user_id, s.cover_path';
    }

    // Lista kolumn auctions z opcjonalnym prefiksem aliasu (np. 'a.'): jedno źródło dla wszystkich zapytań.
    private function columns(string $prefix = ''): string
    {
        return implode(', ', array_map(static fn (string $column): string => $prefix . $column, self::COLUMNS));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapSummaryRow(array $row): AuctionSummary
    {
        return new AuctionSummary(
            $this->mapRow($row),
            (string) $row['company_name'],
            (string) $row['brand_name'],
            (string) $row['brand_slug'],
            new Money((int) $row['current_price']),
            new Money((int) $row['min_next_bid']),
            (int) $row['bid_count'],
            $row['leader_user_id'] === null ? null : (int) $row['leader_user_id'],
            $row['cover_path'] === null ? null : (string) $row['cover_path'],
        );
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
