<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\NotificationType;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\CompanyRepository;
use App\Repository\NotificationRepository;

/*
Zamykanie aukcji po czasie (CRON co minutę: bin/close-auctions.php; reguła 7, kryterium 32). Każda aukcja to osobna
transakcja PostgreSQL: SELECT ... FOR UPDATE na wierszu, ponowne sprawdzenie warunku pod blokadą, ustawienie
status = 'ended', closed_at i winning_bid_id (najwyższa oferta albo NULL bez ofert) oraz powiadomienia w serwisie:
'won' dla zwycięzcy, 'auction_ended' dla pozostałych licytujących i konta firmy-właściciela. Blokada serializuje
zamykanie z ofertami: oferta czekająca na blokadę zobaczy status ended (409), a oferta, która przedłużyła aukcję
przed blokadą (anti-sniping), sprawia, że warunek pod blokadą już nie zachodzi i aukcja jest pominięta.
Idempotencja: zakończone aukcje nie są wybierane, UPDATE ma warunek status <> 'ended', a powiadomienia idą przez
createOnce() (ON CONFLICT DO NOTHING), więc kolejny przebieg ani drugi równoległy nie zdubluje wyniku. E-mail
„Wygrałeś" idzie po COMMIT i tylko wtedy, gdy powiadomienie 'won' powstało teraz; błąd jednej aukcji nie blokuje pozostałych.
Przed zamykaniem aukcje zaplanowane, których start minął, są aktywowane (inaczej BidService nie przyjmie na nie ofert).
*/
final class AuctionCloser
{
    public function __construct(
        private readonly AuctionRepository $auctions,
        private readonly BidRepository $bids,
        private readonly CompanyRepository $companies,
        private readonly NotificationRepository $notifications,
        private readonly WonNotifier $wonNotifier,
        private readonly \PDO $pdo,
        private readonly LineLogger $logger,
    ) {
    }

    public function closeDueAuctions(): CloseReport
    {
        $closed = $skipped = $failed = $withWinner = $mailsSent = $mailsFailed = 0;

        // Najpierw zamykamy, potem aktywujemy: zaplanowana aukcja po czasie od razu kończy się, bez przystanku w active.
        foreach ($this->auctions->findDueForClosing() as $auctionId) {
            try {
                $result = $this->closeOne($auctionId);
            } catch (\Throwable $exception) {
                // Transakcja już wycofana w closeOne(); ta aukcja wróci w następnym przebiegu.
                $failed++;
                $this->logger->error(sprintf(
                    'auction=close_failed id=%d reason=%s: %s',
                    $auctionId,
                    $exception::class,
                    $exception->getMessage(),
                ));

                continue;
            }

            if ($result === null) {
                $skipped++;
                $this->logger->info(sprintf('auction=skipped id=%d reason=not_due_or_already_ended', $auctionId));

                continue;
            }

            $closed++;
            $this->logger->info(sprintf(
                'auction=closed id=%d winnerId=%s winningBidId=%s finalPrice=%s',
                $auctionId,
                $result->getWinnerUserId() ?? 'none',
                $result->getWinningBidId() ?? 'none',
                $result->getFinalPrice() === null ? 'none' : (string) $result->getFinalPrice()->grosze(),
            ));

            if ($result->hasWinner()) {
                $withWinner++;
            }

            // Po COMMIT: błąd poczty zapisuje WonNotifier (mail=failed type=won), a wynik aukcji zostaje.
            if ($result->shouldEmailWinner()) {
                $sent = $this->wonNotifier->notifyByEmail(
                    (int) $result->getWinnerUserId(),
                    $auctionId,
                    $result->getFinalPrice(),
                );
                $sent ? $mailsSent++ : $mailsFailed++;
            }
        }

        $activated = 0;
        try {
            $activated = $this->auctions->activateStarted();
        } catch (\Throwable $exception) {
            $failed++;
            $this->logger->error(sprintf('auction=activate_failed reason=%s: %s', $exception::class, $exception->getMessage()));
        }

        return new CloseReport($activated, $closed, $skipped, $failed, $withWinner, $mailsSent, $mailsFailed);
    }

    /**
     * Zamyka jedną aukcję w jednej transakcji. Zwraca null, gdy pod blokadą nie ma już czego zamykać
     * (inny przebieg zdążył, aukcja przedłużona ofertą albo usunięta).
     */
    private function closeOne(int $auctionId): ?ClosedAuction
    {
        $this->pdo->beginTransaction();

        try {
            $result = $this->closeLocked($auctionId);
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    // Wywoływana wewnątrz transakcji; po findForUpdate() oferty tej aukcji czekają do COMMIT/ROLLBACK.
    private function closeLocked(int $auctionId): ?ClosedAuction
    {
        $auction = $this->auctions->findForUpdate($auctionId);

        // Czas bazy po uzyskaniu blokady: anti-sniping mógł przesunąć koniec, odkąd aukcję wybrano do zamknięcia.
        if ($auction === null || $auction->isEnded() || $this->auctions->currentTime() < $auction->getEndsAt()) {
            return null;
        }

        $leading = $this->bids->findLeading($auctionId);
        $winnerId = $leading?->getUserId();

        if (!$this->auctions->close($auctionId, $leading?->getId())) {
            return null;
        }

        $winnerNotified = false;
        if ($winnerId !== null) {
            $winnerNotified = $this->notifications->createOnce($winnerId, $auctionId, NotificationType::Won) !== null;
        }

        // 'auction_ended' dostają przegrani licytujący (zwycięzca ma 'won') oraz firma, która wystawiła aukcję.
        $recipients = array_diff($this->bids->findBidderIds($auctionId), [$winnerId]);
        $company = $this->companies->findById($auction->getCompanyId());
        if ($company !== null) {
            $recipients[] = $company->getUserId();
        }
        foreach (array_unique($recipients) as $userId) {
            $this->notifications->createOnce($userId, $auctionId, NotificationType::AuctionEnded);
        }

        return new ClosedAuction($auctionId, $leading?->getId(), $winnerId, $leading?->getAmount(), $winnerNotified);
    }
}
