<?php

declare(strict_types=1);

/**
 * Dane karty aukcji do wyswietlenia (tylko prezentacja: formatowanie i tlumaczenie etykiet).
 * Przyjmuje App\Model\AuctionSummary (docs/api.md 1.8) albo tablice z mock-auctions.php (MAKIETA).
 * Pola bez odpowiednika w bazie (stan pojazdu, naped) zwracaja null i karta ich nie pokazuje.
 */

if (!function_exists('auctionCardTimeLeft')) {
    /** Czas do konca wg zegara serwera, np. "2d 4h", "0h 48m", "0m 30s". */
    function auctionCardTimeLeft(\DateTimeImmutable $endsAt, \DateTimeImmutable $now, callable $t): string
    {
        $seconds = max(0, $endsAt->getTimestamp() - $now->getTimestamp());
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);
        $unit = static fn (string $key, string $default): string => $t('time.' . $key, $default);
        if ($d > 0) {
            return $d . $unit('day_short', 'd') . ' ' . $h . $unit('hour_short', 'h');
        }
        if ($h > 0 || $m > 0) {
            return $h . $unit('hour_short', 'h') . ' ' . $m . $unit('minute_short', 'm');
        }

        return $seconds . $unit('second_short', 's');
    }
}

if (!function_exists('auctionCardData')) {
    /**
     * @param \App\Model\AuctionSummary|array<string,mixed> $auction
     * @return array{id:int,title:string,seller:string,origin:string,condition:?string,image:?string,mileage:string,gearbox:string,fuel:string,drive:?string,price:string,bids:int,timeLeft:string,urgent:bool,status:string}
     */
    function auctionCardData(\App\Model\AuctionSummary|array $auction, callable $t, \DateTimeImmutable $now): array
    {
        $format = static fn (int $zloty): string => number_format($zloty, 0, ',', "\u{00A0}");

        if (is_array($auction)) {
            return [
                'id' => (int) $auction['id'],
                'title' => (string) $auction['title'],
                'seller' => (string) $auction['seller'],
                'origin' => (string) $auction['origin'],
                'condition' => $auction['condition'] ?? null,
                'image' => !empty($auction['image']) ? (string) $auction['image'] : null,
                'mileage' => (string) $auction['mileage'],
                'gearbox' => (string) $auction['gearbox'],
                'fuel' => (string) $auction['fuel'],
                'drive' => $auction['drive'] ?? null,
                'price' => $format((int) $auction['price']),
                'bids' => (int) $auction['bids'],
                'timeLeft' => (string) $auction['timeLeft'],
                'urgent' => !empty($auction['urgent']),
                'status' => (string) ($auction['status'] ?? 'active'),
            ];
        }

        $a = $auction->getAuction();
        $isScheduled = $a->getStatus()->value === 'scheduled';
        $secondsLeft = $a->getEndsAt()->getTimestamp() - $now->getTimestamp();
        $country = $a->getOriginCountry();

        return [
            'id' => $a->getId(),
            'title' => $a->getTitle(),
            'seller' => $auction->getCompanyName(),
            'origin' => $t('country.' . $country, $country),
            'condition' => null,
            'image' => $auction->getCoverPath(),
            'mileage' => $format($a->getMileageKm()) . "\u{00A0}km",
            'gearbox' => $t('transmission.' . $a->getTransmission()->value, $a->getTransmission()->value),
            'fuel' => $t('fuel.' . $a->getFuel()->value, $a->getFuel()->value),
            'drive' => null,
            'price' => $format(intdiv($auction->getCurrentPrice()->grosze(), 100)),
            'bids' => $auction->getBidCount(),
            'timeLeft' => $isScheduled
                ? $t('auction.starts', 'Start') . ' ' . \App\Support\LocalTime::format($a->getStartsAt(), 'd.m.Y H:i')
                : auctionCardTimeLeft($a->getEndsAt(), $now, $t),
            'urgent' => !$isScheduled && $secondsLeft < 3600,
            'status' => $a->getStatus()->value,
        ];
    }
}
