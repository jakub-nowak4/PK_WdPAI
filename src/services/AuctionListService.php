<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\HttpException;
use App\Model\AuctionFilter;
use App\Model\AuctionListPage;
use App\Model\AuctionSort;
use App\Model\Money;
use App\Repository\AuctionRepository;
use App\Repository\BrandRepository;
use App\Request;

/*
Lista aukcji (docs/api.md 2.2): waliduje query (q, brand, priceMax, sort, page; zły format = HttpException 400),
buduje AuctionFilter i AuctionSort, liczy offset i liczbę stron (PER_PAGE = 12) i składa AuctionListPage dla
widoku. Granice zapisane raz, tutaj (docs/api.md 1.7). SQL jest w AuctionRepository; poprawny format bez
wyników (nieznana marka, strona za daleko) to pusta strona, nie błąd.
*/
final class AuctionListService
{
    public const PER_PAGE = 12;
    public const MAX_PAGE = 1000;
    public const MAX_QUERY_LENGTH = 100;
    public const MAX_PRICE_ZLOTY = 100_000_000;

    private const BRAND_PATTERN = '/\A[a-z0-9-]{1,40}\z/';

    public function __construct(
        private readonly AuctionRepository $auctions,
        private readonly BrandRepository $brands,
    ) {
    }

    /*
    Strona listy z query żądania (GET /auctions). Pusty parametr = brak filtra, nieznane parametry są ignorowane.
    */
    public function fromRequest(Request $request): AuctionListPage
    {
        $terms = $this->searchTerms($request);
        $brand = $request->queryString('brand', self::BRAND_PATTERN);
        $priceMax = $request->queryInt('priceMax', 1, self::MAX_PRICE_ZLOTY);
        $sort = AuctionSort::tryFrom(
            (string) $request->queryOneOf('sort', array_column(AuctionSort::cases(), 'value'), AuctionSort::Ending->value),
        ) ?? AuctionSort::Ending;
        $page = (int) $request->queryInt('page', 1, self::MAX_PAGE, 1);

        return $this->load(
            new AuctionFilter($brand, $priceMax === null ? null : Money::fromZloty($priceMax), $terms),
            $sort,
            $page,
            [
                'q' => implode(' ', $terms),
                'brand' => $brand ?? '',
                'priceMax' => $priceMax === null ? '' : (string) $priceMax,
                'sort' => $sort->value,
            ],
        );
    }

    /*
    Strona główna (GET /): pierwsza strona bez filtrów, sortowana po końcu aukcji; nie czyta query.
    */
    public function firstPage(): AuctionListPage
    {
        return $this->load(
            new AuctionFilter(),
            AuctionSort::Ending,
            1,
            ['q' => '', 'brand' => '', 'priceMax' => '', 'sort' => AuctionSort::Ending->value],
        );
    }

    /**
     * @param array{q: string, brand: string, priceMax: string, sort: string} $values
     */
    private function load(AuctionFilter $filter, AuctionSort $sort, int $page, array $values): AuctionListPage
    {
        $total = $this->auctions->countListed($filter);
        $offset = ($page - 1) * self::PER_PAGE;

        // Strona za daleko (lub brak wyników): bez drugiego zapytania, widok pokaże stan pusty.
        $cards = $offset < $total
            ? $this->auctions->findListed($filter, $sort, self::PER_PAGE, $offset)
            : [];

        return new AuctionListPage(
            $cards,
            $this->brands->findAll(),
            $values,
            $page,
            max(1, (int) ceil($total / self::PER_PAGE)),
            $total,
        );
    }

    /*
    ?q= po trim: najwyżej 100 znaków, bez znaków sterujących (NUL odrzuciłaby baza błędem 500), podzielone na
    słowa po białych znakach. Pusty tekst = brak filtra. Tablica (?q[]=a) to zły format.
    @return list<string>
    */
    private function searchTerms(Request $request): array
    {
        $raw = $request->query('q');

        if ($raw === null) {
            return [];
        }
        if (!is_string($raw)) {
            throw HttpException::badRequest('Invalid query parameter: q');
        }

        $trimmed = (string) preg_replace('/\A\s+|\s+\z/u', '', $raw);

        if (mb_strlen($trimmed, 'UTF-8') > self::MAX_QUERY_LENGTH
            || preg_match('/[^\P{Cc}\s]/u', $trimmed) === 1) {
            throw HttpException::badRequest('Invalid query parameter: q');
        }

        $terms = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY);

        return $terms === false ? [] : $terms;
    }
}
