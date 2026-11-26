<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AuctionStatus;
use App\Model\AuctionSummary;
use App\Model\Company;
use App\Repository\AuctionRepository;
use App\Support\LocalTime;

/*
Panel firmy (docs/api.md 2.5): aukcje zatwierdzonej firmy jednym zapytaniem do widoku auction_summary
(AuctionRepository::findSummariesByCompanyId, bez N+1: cena, liczba ofert i okładka są już w wierszu).
Serwis zamienia podsumowania na wiersze widoku company/index: kwoty w pełnych złotych, koniec w strefie
Europe/Warsaw, canDelete = brak ofert (reguła 1; backend i tak sprawdza to jeszcze raz przy usuwaniu).
Status "ended" także wtedy, gdy ends_at już minął, a CRON jeszcze nie zamknął aukcji (reguła 3: czas serwera).
*/
final class CompanyPanelService
{
    public function __construct(private readonly AuctionRepository $auctions)
    {
    }

    /**
     * @return array{name: string, city: string}
     */
    public function companyView(Company $company): array
    {
        return ['name' => $company->getName(), 'city' => $company->getCity()];
    }

    /**
     * @return list<array{id: int, title: string, image: ?string, status: string, price: int, minIncrement: int, bidCount: int, endsAtLabel: string, canDelete: bool}>
     */
    public function auctionsFor(Company $company, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();

        return array_map(
            fn (AuctionSummary $summary): array => $this->row($summary, $now),
            $this->auctions->findSummariesByCompanyId($company->getId()),
        );
    }

    /**
     * @return array{id: int, title: string, image: ?string, status: string, price: int, minIncrement: int, bidCount: int, endsAtLabel: string, canDelete: bool}
     */
    private function row(AuctionSummary $summary, \DateTimeImmutable $now): array
    {
        $auction = $summary->getAuction();
        $ended = $auction->getStatus() === AuctionStatus::Ended || $auction->getEndsAt() <= $now;

        return [
            'id' => $auction->getId(),
            'title' => $auction->getTitle(),
            'image' => $summary->getCoverPath(),
            'status' => $ended ? AuctionStatus::Ended->value : $auction->getStatus()->value,
            'price' => intdiv($summary->getCurrentPrice()->grosze(), 100),
            'minIncrement' => intdiv($auction->getMinIncrement()->grosze(), 100),
            'bidCount' => $summary->getBidCount(),
            'endsAtLabel' => LocalTime::format($auction->getEndsAt()),
            'canDelete' => $summary->getBidCount() === 0,
        ];
    }
}
