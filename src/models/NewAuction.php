<?php

declare(strict_types=1);

namespace App\Model;

/*
Dane nowej aukcji po walidacji formularza (docs/api.md 1.8): obiekt wejściowy dla AuctionRepository::create().
Tylko przenosi wartości (bez zachowania, bez id i bez SQL), dlatego pola są publiczne i tylko do odczytu.
Nie ma statusu ani original_ends_at: liczy je INSERT na czasie serwera bazy.
Reguły danych (długości, zakresy, białe listy) sprawdza walidator formularza, nie ten obiekt.
brandId pochodzi z Brand::getId() (wiersz słownika z bazy), a originCountry to kod ISO 3166-1 alpha-2 z białej listy.
*/
final class NewAuction
{
    public function __construct(
        public readonly int $companyId,
        public readonly int $brandId,
        public readonly string $title,
        public readonly string $model,
        public readonly int $year,
        public readonly int $mileageKm,
        public readonly Fuel $fuel,
        public readonly Transmission $transmission,
        public readonly string $originCountry,
        public readonly ?string $vin,
        public readonly string $description,
        public readonly Money $startingPrice,
        public readonly Money $minIncrement,
        public readonly \DateTimeImmutable $startsAt,
        public readonly \DateTimeImmutable $endsAt,
    ) {
    }
}
