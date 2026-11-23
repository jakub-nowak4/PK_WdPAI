<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\BidException;
use App\Model\Auction;
use App\Model\Bid;
use App\Model\Money;
use App\Model\NotificationType;
use App\Model\Role;
use App\Model\User;
use App\Repository\AuctionRepository;
use App\Repository\BidRepository;
use App\Repository\CompanyRepository;
use App\Repository\NotificationRepository;

/*
Składanie oferty (docs/api.md 3.2, reguły 2-5). Cała operacja to jedna transakcja PostgreSQL: SELECT ... FOR UPDATE
na wierszu aukcji serializuje oferty (druga czeka, a po zwolnieniu blokady widzi już nową cenę), czas bierze z bazy
po uzyskaniu blokady, a odrzucenie albo błąd wycofuje wszystko. Powiadomienie 'outbid' dla przebitego prowadzącego
powstaje w tej samej transakcji co oferta; e-mail wysyła dopiero po COMMIT OutbidNotifier na podstawie
BidResult::getOutbidUserId() (T-036).
Granice kwoty są stałymi modelu Money; kontroler ich nie powtarza (docs/api.md 1.7).
*/
final class BidService
{
    // Oferta przyjęta, gdy do końca zostało nie więcej niż tyle sekund, przedłuża aukcję o EXTEND_SECONDS.
    public const EXTEND_WINDOW_SECONDS = 30;
    public const EXTEND_SECONDS = 30;

    // $pdo to to samo połączenie, na którym działają repozytoria: transakcję prowadzi serwis (docs/api.md 1.8).
    public function __construct(
        private readonly AuctionRepository $auctions,
        private readonly BidRepository $bids,
        private readonly CompanyRepository $companies,
        private readonly NotificationRepository $notifications,
        private readonly \PDO $pdo,
        private readonly Translator $translator,
    ) {
    }

    /**
     * $rawAmount to surowa wartość z żądania (kwota w groszach); serwis sprawdza ją przed utworzeniem Money.
     *
     * @throws BidException 403 forbidden_role / own_auction, 404 auction_not_found, 409 auction_not_active /
     *                      already_leading, 422 amount_invalid / bid_too_low
     */
    public function placeBid(int $auctionId, User $bidder, mixed $rawAmount): BidResult
    {
        if (!$bidder->hasRole(Role::Buyer)) {
            throw $this->error(403, 'forbidden_role', 'bid.error.forbidden_role');
        }

        if (!is_int($rawAmount) || $rawAmount <= 0 || $rawAmount > Money::MAX_GROSZE) {
            throw $this->error(422, 'amount_invalid', 'bid.error.amount_invalid');
        }

        $amount = new Money($rawAmount);

        $this->pdo->beginTransaction();

        try {
            $result = $this->placeLocked($auctionId, $bidder, $amount);
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    // Wywoływana wewnątrz transakcji; po findForUpdate() inne oferty tej aukcji czekają do COMMIT/ROLLBACK.
    private function placeLocked(int $auctionId, User $bidder, Money $amount): BidResult
    {
        $auction = $this->auctions->findForUpdate($auctionId);

        if ($auction === null) {
            throw $this->error(404, 'auction_not_found', 'auction.error.not_found');
        }

        // Obrona w głąb: rola DEALER odpada wcześniej, ale reguła 2 ma być sprawdzana jawnie.
        $company = $this->companies->findByUserId($bidder->getId());
        if ($company !== null && $company->getId() === $auction->getCompanyId()) {
            throw $this->error(403, 'own_auction', 'bid.error.own_auction');
        }

        // Czas po uzyskaniu blokady: oferta czekająca na blokadę nie dostaje starego czasu.
        $now = $this->auctions->currentTime();

        if (!$auction->isActive() || $now >= $auction->getEndsAt()) {
            throw $this->error(409, 'auction_not_active', 'bid.error.not_active');
        }

        $leading = $this->bids->findLeading($auctionId);

        if ($leading !== null && $leading->getUserId() === $bidder->getId()) {
            throw $this->error(409, 'already_leading', 'bid.error.already_leading');
        }

        $this->assertAmountEnough($auction, $leading, $amount);

        $bid = $this->bids->create($auctionId, $bidder->getId(), $amount);

        // Anti-sniping liczy od końca zapisanego w bazie i czasu bazy, nie przeglądarki.
        $extended = $auction->getEndsAt() <= $now->modify('+' . self::EXTEND_WINDOW_SECONDS . ' seconds');
        $endsAt = $extended
            ? $this->auctions->extendEndsAt($auctionId, self::EXTEND_SECONDS)
            : $auction->getEndsAt();

        // Przebity prowadzący dostaje powiadomienie w tej samej transakcji (docs/api.md 3.2). Prowadzący różny od
        // składającego jest gwarantowany wyżej (already_leading), więc nikt nie przebija samego siebie.
        $outbidUserId = $leading?->getUserId();

        if ($outbidUserId !== null) {
            $this->notifications->create($outbidUserId, $auctionId, NotificationType::Outbid);
        }

        return new BidResult($bid, $endsAt, $extended, $outbidUserId);
    }

    // Pierwsza oferta musi osiągnąć cenę wywoławczą, kolejne: cena prowadzącego + minimalny krok (reguła 2).
    private function assertAmountEnough(Auction $auction, ?Bid $leading, Money $amount): void
    {
        $minGrosze = $leading === null
            ? $auction->getStartingPrice()->grosze()
            : $leading->getAmount()->grosze() + $auction->getMinIncrement()->grosze();

        if ($amount->grosze() >= $minGrosze) {
            return;
        }

        // Gdy lider jest blisko MAX_GROSZE minimum może wyjść poza zakres Money (nikt już nie przebije lidera);
        // do odpowiedzi bierzemy wtedy górną granicę.
        $min = new Money(min($minGrosze, Money::MAX_GROSZE));

        throw new BidException(
            422,
            'bid_too_low',
            $this->translator->translate('bid.error.too_low', ['min' => $min->format()]),
            ['minNextBid' => $min->grosze(), 'minNextBidFormatted' => $min->format()],
        );
    }

    private function error(int $status, string $code, string $messageKey): BidException
    {
        return new BidException($status, $code, $this->translator->translate($messageKey));
    }
}
