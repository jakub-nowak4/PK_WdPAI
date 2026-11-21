<?php

declare(strict_types=1);

namespace App\Model;

/*
Filtry listy aukcji po walidacji query (docs/api.md 2.2): obiekt wejściowy dla AuctionRepository::findListed()
i countListed(). Tylko przenosi wartości (bez zachowania i bez SQL), dlatego pola są publiczne i tylko do odczytu.
brandSlug to slug ze słownika marek (porównywany wprost z brand_slug, bez zamiany na id); null = bez filtra marki.
priceMax to górna granica aktualnej ceny; null = bez filtra ceny.
searchTerms to słowa z ?q= (serwis już je przyciął i podzielił); każde musi pasować do tytułu lub marki.
Sortowanie i strona nie są częścią filtra: idą osobnymi argumentami (AuctionSort, limit, offset).
*/
final class AuctionFilter
{
    /**
     * @param list<string> $searchTerms
     */
    public function __construct(
        public readonly ?string $brandSlug = null,
        public readonly ?Money $priceMax = null,
        public readonly array $searchTerms = [],
    ) {
    }
}
