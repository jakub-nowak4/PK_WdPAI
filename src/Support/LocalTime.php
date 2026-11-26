<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/*
Jedyne miejsce, które zna strefę czasu użytkownika (Europe/Warsaw). Baza i PHP pracują w UTC
(TIMESTAMPTZ wraca z offsetem +00), więc każdy czas pokazywany użytkownikowi lub oddawany przez
API przechodzi tędy. To wyłącznie prezentacja: porównania, anti-sniping i CRON liczą na
DateTimeImmutable (ten sam moment bez względu na strefę), więc ich nie zmienia.
Klasa jest bezstanowa, dlatego metody są statyczne i można je wołać także z widoków.
*/
final class LocalTime
{
    public const TIMEZONE = 'Europe/Warsaw';

    /** Format czasu w interfejsie, np. "28.11.2026, 14:05". */
    public const LABEL_FORMAT = 'd.m.Y, H:i';

    private function __construct()
    {
    }

    public static function zone(): DateTimeZone
    {
        return new DateTimeZone(self::TIMEZONE);
    }

    public static function convert(DateTimeInterface $time): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($time)->setTimezone(self::zone());
    }

    public static function format(DateTimeInterface $time, string $pattern = self::LABEL_FORMAT): string
    {
        return self::convert($time)->format($pattern);
    }

    /** ISO 8601 z offsetem strefy Warszawy (+01:00 zimą, +02:00 latem), dla JSON. */
    public static function iso(DateTimeInterface $time): string
    {
        return self::convert($time)->format(DateTimeInterface::ATOM);
    }
}
