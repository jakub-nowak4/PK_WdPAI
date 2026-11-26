<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Bid;
use App\Model\Money;
use DateTimeImmutable;

// Oferty (tabela bids). Cały SQL dla ofert jest tutaj; reguły (krok oferty, własna aukcja, anti-sniping) i transakcję
// z blokadą AuctionRepository::findForUpdate() prowadzi BidService (docs/api.md 3.2). Oferty nigdy nie są edytowane
// ani usuwane (historia), więc repozytorium ma tylko zapis nowej i odczyty.
final class BidRepository extends BaseRepository
{
    private const COLUMNS = 'id, auction_id, user_id, amount, created_at';

    // Bez sprawdzania kroku i kwoty: to robi BidService. Ostatnią linią obrony jest baza (PDOException):
    // 23505 (bids_auction_amount_uq, ta sama kwota w aukcji), 23514 (kwota spoza 1…100000000000),
    // 23503 (brak aukcji lub użytkownika). created_at ustawia DEFAULT clock_timestamp() (moment zapisu, nie start transakcji).
    public function create(int $auctionId, int $userId, Money $amount): Bid
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO bids (auction_id, user_id, amount)
             VALUES (:auction_id, :user_id, :amount)
             RETURNING ' . self::COLUMNS
        );
        $statement->execute([
            'auction_id' => $auctionId,
            'user_id' => $userId,
            'amount' => $amount->grosze(),
        ]);

        return $this->mapRow($statement->fetch());
    }

    // Najwyższa oferta aukcji (prowadzący i aktualna cena) albo null, gdy ofert brak. Wywołuj w transakcji
    // po AuctionRepository::findForUpdate(), wtedy wynik jest aktualny. Kwoty w aukcji są unikalne, id tylko stabilizuje sort.
    public function findLeading(int $auctionId): ?Bid
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM bids
             WHERE auction_id = :auction_id
             ORDER BY amount DESC, id ASC
             LIMIT 1'
        );
        $statement->execute(['auction_id' => $auctionId]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    // Id użytkowników, którzy złożyli w aukcji choć jedną ofertę (każdy raz): odbiorcy powiadomień po zamknięciu aukcji.
    /**
     * @return list<int>
     */
    public function findBidderIds(int $auctionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT DISTINCT user_id FROM bids WHERE auction_id = :auction_id ORDER BY user_id'
        );
        $statement->execute(['auction_id' => $auctionId]);

        return array_map(static fn (mixed $id): int => (int) $id, $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    // Historia ofert aukcji od najstarszej (docs/api.md 3.1 recentBids). Chronologia jest potrzebna serwisowi,
    // bo numer "Licytujący N" wynika z kolejności pierwszej oferty danej osoby; ostatnie 10 wycina serwis.
    /**
     * @return list<Bid>
     */
    public function findByAuctionId(int $auctionId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM bids WHERE auction_id = :auction_id ORDER BY id ASC'
        );
        $statement->execute(['auction_id' => $auctionId]);

        return array_map($this->mapRow(...), $statement->fetchAll());
    }

    // "Moje oferty" (docs/api.md 2.4): dla każdej aukcji, w której użytkownik licytował, jego najwyższa oferta
    // (DISTINCT ON po auction_id), od ostatnio złożonej. Status (leading/outbid/won/lost) i dane aukcji
    // dokłada serwis z AuctionSummary. Korzysta z bids_user_auction_idx.
    /**
     * @return list<Bid>
     */
    public function findHighestByUserId(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM (
                 SELECT DISTINCT ON (auction_id) ' . self::COLUMNS . '
                 FROM bids
                 WHERE user_id = :user_id
                 ORDER BY auction_id, amount DESC
             ) AS highest
             ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map($this->mapRow(...), $statement->fetchAll());
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): Bid
    {
        return new Bid(
            (int) $row['id'],
            (int) $row['auction_id'],
            (int) $row['user_id'],
            new Money((int) $row['amount']),
            new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
