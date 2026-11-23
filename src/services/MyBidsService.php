<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AuctionSummary;
use App\Model\Bid;
use App\Model\Money;
use App\Model\MyBid;
use App\Model\MyBidsOverview;
use App\Model\MyBidStatus;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;

/*
"Moje oferty" (docs/api.md 2.4): dwa zapytania, najwyższe oferty użytkownika (BidRepository) i podsumowania ich
aukcji (AuctionRepository). Status liczy ten serwis na czasie serwera (reguła 3): aukcja jest zakończona, gdy
status = ended albo ends_at <= teraz (CRON mógł jeszcze jej nie zamknąć). Zakończona: won, gdy użytkownik
prowadzi, inaczej lost; trwająca: leading albo outbid. Filtr statusu zawęża tę samą listę.
*/
final class MyBidsService
{
    public function __construct(
        private readonly BidRepository $bids,
        private readonly AuctionRepository $auctions,
    ) {
    }

    public function forUser(int $userId, ?MyBidStatus $filter = null, ?\DateTimeImmutable $now = null): MyBidsOverview
    {
        $now ??= new \DateTimeImmutable();
        $highest = $this->bids->findHighestByUserId($userId);
        $summaries = $this->auctions->findSummariesByIds(
            array_map(static fn (Bid $bid): int => $bid->getAuctionId(), $highest),
        );

        $all = [];
        foreach ($highest as $bid) {
            $summary = $summaries[$bid->getAuctionId()] ?? null;
            if ($summary === null) {
                continue;
            }
            $all[] = new MyBid($summary, $bid->getAmount(), $this->statusFor($summary, $userId, $now));
        }

        $counts = ['all' => count($all)];
        foreach (MyBidStatus::cases() as $case) {
            $counts[$case->value] = 0;
        }
        $running = 0;
        foreach ($all as $row) {
            $counts[$row->getStatus()->value]++;
            if ($row->getStatus() === MyBidStatus::Leading || $row->getStatus() === MyBidStatus::Outbid) {
                $running += $row->getMyHighestBid()->grosze();
            }
        }

        $shown = $filter === null
            ? $all
            : array_values(array_filter($all, static fn (MyBid $row): bool => $row->getStatus() === $filter));

        return new MyBidsOverview($shown, $counts, new Money(min($running, Money::MAX_GROSZE)), $now);
    }

    private function statusFor(AuctionSummary $summary, int $userId, \DateTimeImmutable $now): MyBidStatus
    {
        $auction = $summary->getAuction();
        $leads = $summary->getLeaderUserId() === $userId;

        if ($auction->isEnded() || $auction->getEndsAt() <= $now) {
            return $leads ? MyBidStatus::Won : MyBidStatus::Lost;
        }

        return $leads ? MyBidStatus::Leading : MyBidStatus::Outbid;
    }
}
