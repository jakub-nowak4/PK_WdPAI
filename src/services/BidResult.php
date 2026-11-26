<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Bid;

/*
Wynik przyjętej oferty: zapisana oferta (nowa cena = jej kwota), koniec aukcji po ewentualnym przedłużeniu,
flaga przedłużenia (anti-sniping) i id przebitego prowadzącego (null, gdy to pierwsza oferta w aukcji).
Powiadomienie i e-mail dla przebitego robi T-036 na podstawie tego pola.
*/
final class BidResult
{
    public function __construct(
        private readonly Bid $bid,
        private readonly \DateTimeImmutable $endsAt,
        private readonly bool $extended,
        private readonly ?int $outbidUserId,
    ) {
    }

    public function getBid(): Bid
    {
        return $this->bid;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function isExtended(): bool
    {
        return $this->extended;
    }

    public function getOutbidUserId(): ?int
    {
        return $this->outbidUserId;
    }
}
