<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\HttpException;
use App\Model\WonAuction;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;

/*
Ekran "Aukcja wygrana" (docs/api.md 2.3). Zwycięzcę ustala CRON (AuctionCloser): status = 'ended' i winning_bid_id.
Ten serwis tylko to czyta, więc aukcja po czasie, której CRON jeszcze nie zamknął, daje 404 tak samo jak aukcja
trwająca albo nieistniejąca (kod auction_not_found, bez zdradzania, czy aukcja istnieje). Zalogowany, który nie jest
właścicielem zwycięskiej oferty (także gdy aukcja zakończyła się bez ofert), dostaje 403. Kolejność: 404, potem 403.
*/
final class WonAuctionService
{
    public function __construct(
        private readonly AuctionRepository $auctions,
        private readonly BidRepository $bids,
    ) {
    }

    public function forWinner(int $auctionId, int $userId): WonAuction
    {
        $summary = $this->auctions->findSummaryById($auctionId);

        if ($summary === null || !$summary->getAuction()->isEnded()) {
            throw HttpException::notFound('Auction not found', 'auction_not_found');
        }

        $auction = $summary->getAuction();
        $winningBidId = $auction->getWinningBidId();
        $winningBid = $winningBidId === null ? null : $this->bids->findById($winningBidId);

        // Oferta musi należeć do tej aukcji: to obrona przed niespójnymi danymi, nie normalna ścieżka.
        if ($winningBid === null || $winningBid->getAuctionId() !== $auctionId || $winningBid->getUserId() !== $userId) {
            throw HttpException::forbidden('auction.won.error.not_winner', 'not_winner');
        }

        return new WonAuction($summary, $winningBid->getAmount(), $auction->getClosedAt());
    }
}
