<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MailException;
use App\Model\Money;
use App\Repository\AuctionRepository;
use App\Repository\UserRepository;

/*
E-mail „Wygrałeś aukcję" (docs/api.md 2.4, reguła 7, kryterium 33). Wołany przez AuctionCloser po COMMIT zamknięcia
aukcji, więc błąd poczty nie cofa wyniku aukcji ani powiadomienia w serwisie. Treść składa się z kluczy tłumaczeń
w języku zapisanym na koncie zwycięzcy (users.locale; CRON nie ma sesji ani języka żądania). Każdy błąd (poczta, brak
użytkownika, baza) kończy się jedną linią logu w formacie z docs/api.md 2.4, bez adresu i treści; metoda nie rzuca wyjątku.
*/
final class WonNotifier
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
     */
    public function notifyByEmail(int $winnerUserId, int $auctionId, Money $finalPrice): bool
    {
        try {
            $this->send($winnerUserId, $auctionId, $finalPrice);

            return true;
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'mail=failed type=won userId=%d auctionId=%d reason=%s',
                $winnerUserId,
                $auctionId,
                $exception instanceof MailException ? $exception->getMessage() : $exception::class,
            ));

            return false;
        }
    }

    /**
     * @throws MailException
     */
    private function send(int $winnerUserId, int $auctionId, Money $finalPrice): void
    {
        $user = $this->users->findById($winnerUserId)
            ?? throw new \RuntimeException('Winner not found');
        $summary = $this->auctions->findSummaryById($auctionId)
            ?? throw new \RuntimeException('Auction not found');

        $translator = new Translator($this->langDir, $user->getLocale());
        $params = [
            'name' => $user->getName(),
            'title' => $summary->getAuction()->getTitle(),
            'price' => $finalPrice->format(),
            'url' => rtrim($this->appUrl, '/') . '/auctions/' . $auctionId . '/won',
        ];

        $this->mailer->send(
            $user->getEmail(),
            $user->getName(),
            $this->singleLine($translator->translate('mail.won.subject', $params)),
            $translator->translate('mail.won.body', $params),
        );
    }

    // Tytuł aukcji pochodzi od firmy; w temacie nie zostawiamy łamań linii ani znaków sterujących.
    private function singleLine(string $text): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text));
    }
}
