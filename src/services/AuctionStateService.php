<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AuctionDetails;
use App\Model\AuctionStatus;
use App\Model\Role;
use App\Repository\BidRepository;
use App\Repository\CompanyRepository;
use App\Repository\NotificationRepository;
use App\Support\LocalTime;

/*
Stan aukcji dla Fetch API (docs/api.md 3.1): składa tablicę gotową do json_encode z AuctionDetailsService
(cena, liczba ofert, status i sekundy liczone na czasie serwera, ostatnie oferty bez danych osobowych)
oraz bloku viewer (status oferty zalogowanego, czy może licytować i dlaczego nie, nieprzeczytane powiadomienia).
Brak aukcji to HttpException 404 (auction_not_found) z AuctionDetailsService. Kwoty w groszach, daty ISO 8601
w strefie Europe/Warsaw. Serwis tylko czyta; po złożeniu oferty woła się go po COMMIT, więc widzi nowy stan.
*/
final class AuctionStateService
{
    public function __construct(
        private readonly AuctionDetailsService $details,
        private readonly BidRepository $bids,
        private readonly CompanyRepository $companies,
        private readonly NotificationRepository $notifications,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(int $auctionId, ?int $viewerId, ?Role $viewerRole): array
    {
        $details = $this->details->find($auctionId, $viewerId);
        $summary = $details->getSummary();
        $auction = $summary->getAuction();

        return [
            'auction' => [
                'id' => $auction->getId(),
                'status' => $details->getStatus()->value,
                'currentPrice' => $summary->getCurrentPrice()->grosze(),
                'currentPriceFormatted' => $summary->getCurrentPrice()->format(),
                'minIncrement' => $auction->getMinIncrement()->grosze(),
                'minNextBid' => $summary->getMinNextBid()->grosze(),
                'minNextBidFormatted' => $summary->getMinNextBid()->format(),
                'bidCount' => $summary->getBidCount(),
                'startsAt' => $this->iso($auction->getStartsAt()),
                'endsAt' => $this->iso($auction->getEndsAt()),
                'secondsLeft' => $details->getSecondsLeft(),
                'extended' => $auction->isExtended(),
            ],
            'serverTime' => $this->iso($details->getServerTime()),
            'recentBids' => $this->recentBids($details),
            'viewer' => $this->viewer($details, $viewerId, $viewerRole),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentBids(AuctionDetails $details): array
    {
        $label = $this->translator->translate('bids.bidder');
        $bids = [];

        foreach ($details->getRecentBids() as $bid) {
            $bids[] = [
                'amount' => $bid->getAmount()->grosze(),
                'amountFormatted' => $bid->getAmount()->format(),
                'bidder' => $label . ' ' . $bid->getBidderNumber(),
                'isYou' => $bid->isYou(),
                'placedAt' => $this->iso($bid->getPlacedAt()),
            ];
        }

        return $bids;
    }

    /**
     * @return array<string, mixed>
     */
    private function viewer(AuctionDetails $details, ?int $viewerId, ?Role $viewerRole): array
    {
        if ($viewerId === null || $viewerRole === null) {
            return [
                'loggedIn' => false,
                'role' => null,
                'status' => 'none',
                'myHighestBid' => null,
                'canBid' => false,
                'cannotBidReason' => 'login_required',
                'unreadNotifications' => 0,
            ];
        }

        $summary = $details->getSummary();
        $myHighest = $this->highestBidOf($summary->getAuction()->getId(), $viewerId);
        $isLeader = $summary->getLeaderUserId() === $viewerId;
        $reason = $this->cannotBidReason($details, $viewerId, $viewerRole, $isLeader);

        return [
            'loggedIn' => true,
            'role' => $viewerRole->value,
            'status' => $this->bidStatus($details->getStatus(), $myHighest, $isLeader),
            'myHighestBid' => $myHighest,
            'canBid' => $reason === null,
            'cannotBidReason' => $reason,
            'unreadNotifications' => $this->notifications->countUnread($viewerId),
        ];
    }

    // Najwyższa oferta zalogowanego w tej aukcji w groszach albo null, gdy nie licytował.
    private function highestBidOf(int $auctionId, int $viewerId): ?int
    {
        $highest = null;

        foreach ($this->bids->findByAuctionId($auctionId) as $bid) {
            if ($bid->getUserId() === $viewerId) {
                $highest = max($highest ?? 0, $bid->getAmount()->grosze());
            }
        }

        return $highest;
    }

    // none | leading | outbid (aukcja trwa), none | won | lost (po końcu); aukcja przed startem nie ma ofert.
    private function bidStatus(AuctionStatus $auctionStatus, ?int $myHighest, bool $isLeader): string
    {
        if ($myHighest === null) {
            return 'none';
        }

        if ($auctionStatus === AuctionStatus::Ended) {
            return $isLeader ? 'won' : 'lost';
        }

        return $isLeader ? 'leading' : 'outbid';
    }

    // Pierwszy powód z docs/api.md 3.1 w kolejności: rola, własna aukcja, status aukcji, już prowadzi.
    private function cannotBidReason(AuctionDetails $details, int $viewerId, Role $role, bool $isLeader): ?string
    {
        if ($role !== Role::Buyer) {
            $company = $role === Role::Dealer ? $this->companies->findByUserId($viewerId) : null;
            $ownsAuction = $company !== null
                && $company->getId() === $details->getSummary()->getAuction()->getCompanyId();

            return $ownsAuction ? 'own_auction' : 'role';
        }

        if ($details->getStatus() !== AuctionStatus::Active) {
            return 'not_active';
        }

        return $isLeader ? 'already_leading' : null;
    }

    private function iso(\DateTimeImmutable $time): string
    {
        return LocalTime::iso($time);
    }
}
