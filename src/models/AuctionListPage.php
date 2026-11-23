<?php

declare(strict_types=1);

namespace App\Model;

/*
Jedna strona listy aukcji dla widoku (docs/api.md 2.2): karty, wartości filtrów do formularza, marki do <select>
i paginacja. Niemutowalny read-model bez SQL, składany przez AuctionListService. Filtry to teksty tak, jak
trafiają do pól formularza (pusty tekst = brak filtra), a nie obiekty AuctionFilter/Money.
*/
final class AuctionListPage
{
    /**
     * @param list<AuctionSummary> $auctions
     * @param list<Brand> $brands
     * @param array{q: string, brand: string, priceMax: string, sort: string} $filters
     */
    public function __construct(
        private readonly array $auctions,
        private readonly array $brands,
        private readonly array $filters,
        private readonly int $page,
        private readonly int $pages,
        private readonly int $total,
    ) {
    }

    /**
     * @return list<AuctionSummary>
     */
    public function getAuctions(): array
    {
        return $this->auctions;
    }

    /**
     * @return list<Brand>
     */
    public function getBrands(): array
    {
        return $this->brands;
    }

    /**
     * @return array{q: string, brand: string, priceMax: string, sort: string}
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    // Liczba stron (co najmniej 1, także gdy nic nie znaleziono).
    public function getPages(): int
    {
        return $this->pages;
    }

    // Liczba wszystkich aukcji pasujących do filtrów (nie tylko na tej stronie).
    public function getTotal(): int
    {
        return $this->total;
    }
}
