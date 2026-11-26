<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\HttpException;
use App\Model\AuctionSummary;
use App\Model\Company;
use App\Repository\AuctionImageRepository;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;

/*
Usuwanie aukcji przez firmę (docs/api.md 2.5, reguła 1, kryteria 22 i 26): tylko właściciel i tylko gdy aukcja nie
ma ofert, inaczej 403. Kolejność reguł: aukcja istnieje (404) -> należy do firmy (403) -> brak ofert (403).
Usuwanie to jedna transakcja PostgreSQL: SELECT ... FOR UPDATE na aukcji (ta sama blokada, którą bierze BidService,
więc równoległa oferta czeka i nie wstawi się między sprawdzenie a DELETE), liczba ofert, ścieżki zdjęć, DELETE
(wiersze auction_images znikają kaskadowo). Pliki z public/uploads/ usuwamy dopiero po COMMIT: wcześniej nie wolno,
bo przy wycofaniu transakcji aukcja zostałaby bez zdjęć. Ostatnią linią obrony jest bids.auction_id ON DELETE
RESTRICT (SQLSTATE 23001 albo 23503), też zamieniany na 403. Błąd kasowania pliku nie cofa usunięcia aukcji, idzie do logu.
*/
final class AuctionDeletionService
{
    private const FOREIGN_KEY_STATES = ['23001', '23503'];

    // $pdo to to samo połączenie, na którym działają repozytoria: transakcję prowadzi serwis (docs/api.md 1.8).
    public function __construct(
        private readonly AuctionRepository $auctions,
        private readonly AuctionImageRepository $images,
        private readonly BidRepository $bids,
        private readonly UploadService $uploads,
        private readonly \PDO $pdo,
    ) {
    }

    /**
     * Dane ekranu potwierdzenia (GET). Niczego nie zmienia, ale stosuje te same reguły co usunięcie,
     * więc firma nie zobaczy przycisku dla cudzej aukcji ani aukcji z ofertami.
     *
     * @throws HttpException 404 brak aukcji, 403 cudza aukcja albo aukcja z ofertami
     */
    public function findForConfirmation(Company $company, int $auctionId): AuctionSummary
    {
        $summary = $this->auctions->findSummaryById($auctionId);
        if ($summary === null) {
            throw HttpException::notFound('Auction not found', 'auction_not_found');
        }

        if ($summary->getAuction()->getCompanyId() !== $company->getId()) {
            throw HttpException::forbidden();
        }

        if ($summary->getBidCount() > 0) {
            throw HttpException::forbidden('company.error.has_bids', 'auction_has_bids');
        }

        return $summary;
    }

    /**
     * @throws HttpException 404 brak aukcji, 403 cudza aukcja albo aukcja z ofertami
     */
    public function delete(Company $company, int $auctionId): void
    {
        $paths = [];
        $this->pdo->beginTransaction();

        try {
            $auction = $this->auctions->findForUpdate($auctionId);
            if ($auction === null) {
                throw HttpException::notFound('Auction not found', 'auction_not_found');
            }

            if ($auction->getCompanyId() !== $company->getId()) {
                throw HttpException::forbidden();
            }

            if ($this->bids->countByAuctionId($auctionId) > 0) {
                throw HttpException::forbidden('company.error.has_bids', 'auction_has_bids');
            }

            foreach ($this->images->findByAuctionId($auctionId) as $image) {
                $paths[] = $image->getPath();
            }

            $this->auctions->delete($auctionId);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof \PDOException && $this->isForeignKeyViolation($exception)) {
                throw HttpException::forbidden('company.error.has_bids', 'auction_has_bids');
            }

            throw $exception;
        }

        $this->removeFiles($auctionId, $paths);
    }

    private function isForeignKeyViolation(\PDOException $exception): bool
    {
        $state = $exception->errorInfo[0] ?? $exception->getCode();

        return in_array((string) $state, self::FOREIGN_KEY_STATES, true);
    }

    /**
     * @param list<string> $paths
     */
    private function removeFiles(int $auctionId, array $paths): void
    {
        foreach ($paths as $path) {
            if (!$this->uploads->delete($path)) {
                error_log(sprintf('auction=delete_file_failed id=%d path=%s', $auctionId, $path));
            }
        }
    }
}
