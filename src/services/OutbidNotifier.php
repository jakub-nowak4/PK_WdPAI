<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MailException;
use App\Model\Money;
use App\Repository\AuctionRepository;
use App\Repository\UserRepository;

/*
E-mail „Zostałeś przebity" (docs/api.md 3.2 i 2.4, reguła 6, kryterium 33). Wołany po COMMIT oferty, więc nic
tu nie cofa zapisanej oferty ani powiadomienia w serwisie (to robi BidService w transakcji). Treść składa się
z kluczy tłumaczeń w języku zapisanym na koncie przebitego (users.locale), a nie w języku żądania osoby,
która przebiła. Link prowadzi do aukcji pod APP_URL. Każdy błąd (poczta, brak użytkownika, baza) kończy się
jedną linią logu w formacie z docs/api.md 2.4, bez adresu, treści i danych sesji; metoda nie rzuca wyjątku.
*/
final class OutbidNotifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UserRepository $users,
        private readonly AuctionRepository $auctions,
        private readonly string $langDir,
        private readonly string $appUrl,
    ) {
    }

    /**
     * Zwraca true, gdy wiadomość przyjął serwer pocztowy; false przy każdym błędzie (zalogowanym).
     * Brak przebitego prowadzącego (pierwsza oferta w aukcji) to nic do zrobienia, nie błąd.
     */
    public function notifyByEmail(?int $outbidUserId, int $auctionId, Money $newPrice): bool
    {
        if ($outbidUserId === null) {
            return false;
        }

        try {
            $this->send($outbidUserId, $auctionId, $newPrice);

            return true;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'mail=failed type=outbid userId=%d auctionId=%d reason=%s',
                $outbidUserId,
                $auctionId,
                $exception instanceof MailException ? $exception->getMessage() : $exception::class,
            ));

            return false;
        }
    }

    /**
     * @throws MailException
     */
    private function send(int $outbidUserId, int $auctionId, Money $newPrice): void
    {
        $user = $this->users->findById($outbidUserId)
            ?? throw new \RuntimeException('Outbid user not found');
        $summary = $this->auctions->findSummaryById($auctionId)
            ?? throw new \RuntimeException('Auction not found');

        $translator = new Translator($this->langDir, $user->getLocale());
        $params = [
            'name' => $user->getName(),
            'title' => $summary->getAuction()->getTitle(),
            'price' => $newPrice->format(),
            'url' => rtrim($this->appUrl, '/') . '/auctions/' . $auctionId,
        ];

        $this->mailer->send(
            $user->getEmail(),
            $user->getName(),
            $this->singleLine($translator->translate('mail.outbid.subject', $params)),
            $translator->translate('mail.outbid.body', $params),
        );
    }

    // Tytuł aukcji pochodzi od firmy; w temacie nie zostawiamy łamań linii ani znaków sterujących.
    private function singleLine(string $text): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text));
    }
}
