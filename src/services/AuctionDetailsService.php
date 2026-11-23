<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\HttpException;
use App\Model\AuctionDetails;
use App\Model\Bid;
use App\Model\PublicBid;
use App\Repository\AuctionImageRepository;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;

/*
Szczegóły aukcji (docs/api.md 2.3): składa AuctionDetails z podsumowania aukcji (findSummaryById), zdjęć i historii
ofert. Brak aukcji to HttpException 404. Oferty są anonimowe: numer licytującego wynika z kolejności jego pierwszej
oferty w tej aukcji, widoczne jest 10 ostatnich (najnowsza pierwsza). Czas serwera pochodzi stąd, nie z przeglądarki.
Dostęp jest publiczny, więc $viewerId (może być null) służy tylko do oznaczenia własnych ofert.
*/
final class AuctionDetailsService
{
    public const RECENT_BIDS_LIMIT = 10;

    public function __construct(
        private readonly AuctionRepository $auctions,
        private readonly AuctionImageRepository $images,
        private readonly BidRepository $bids,
    ) {
    }

    public function find(int $id, ?int $viewerId = null, ?\DateTimeImmutable $now = null): AuctionDetails
    {
        $summary = $this->auctions->findSummaryById($id);

        if ($summary === null) {
            throw HttpException::notFound('Auction not found');
        }

        return new AuctionDetails(
            $summary,
            $this->images->findByAuctionId($id),
            $this->recentBids($this->bids->findByAuctionId($id), $viewerId),
            $now ?? new \DateTimeImmutable(),
        );
    }

    /**
     * @param list<Bid> $bids od najstarszej
     * @return list<PublicBid> najnowsza pierwsza
     */
    private function recentBids(array $bids, ?int $viewerId): array
    {
        $numbers = [];
        $public = [];

        foreach ($bids as $bid) {
            $userId = $bid->getUserId();
            $numbers[$userId] ??= count($numbers) + 1;
            $public[] = new PublicBid($numbers[$userId], $bid->getAmount(), $bid->getCreatedAt(), $userId === $viewerId);
        }

        return array_slice(array_reverse($public), 0, self::RECENT_BIDS_LIMIT);
    }
}
