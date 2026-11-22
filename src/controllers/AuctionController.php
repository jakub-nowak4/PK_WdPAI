<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request;

final class AuctionController extends AppController
{
    private const PREVIEW_STATES = ['confirm', 'leading', 'error', 'outbid'];

    private const SORTS = ['ending', 'newest', 'price_asc', 'price_desc'];

    public function index(Request $request): void
    {
        // Zła wartość w query to 400 (docs/api.md 2.2); pusty parametr = domyślna.
        $filters = [
            'q' => (string) $request->queryString('q', '/\A.{1,100}\z/su', ''),
            'brand' => (string) $request->queryString('brand', '/\A[a-z0-9-]{1,40}\z/', ''),
            'priceMax' => (string) $request->queryInt('priceMax', 1, 100000000, null),
            'sort' => (string) $request->queryOneOf('sort', self::SORTS, 'ending'),
        ];
        $page = (int) $request->queryInt('page', 1, 100000, 1);

        // MAKIETA (T-009): lista z partials/mock-auctions.php, do czasu repozytorium.
        $this->render('auctions/index', [
            'pageTitle' => 'Aukcje',
            'filters' => $filters,
            'page' => $page,
        ]);
    }

    public function show(int $id, Request $request): void
    {
        // MAKIETA (T-009): ?state=confirm|leading|error|outbid pokazuje ekrany 04-07; zła wartość to 400.
        $state = $request->queryOneOf('state', self::PREVIEW_STATES);

        // MAKIETA: dane aukcji statyczne, do czasu repozytorium.
        $this->render('auctions/show', [
            'pageTitle' => 'Szczegóły aukcji',
            'auctionId' => $id,
            'previewState' => $state,
        ]);
    }

    public function won(int $id): void
    {
        // Tylko zalogowany; sprawdzenie zwycięzcy i 404 dojdą z danymi.
        $this->requireLogin();

        // MAKIETA (T-009): ekran 08 jako osobna strona; docelowo tylko zwycięzca (403/404 inaczej).
        $this->render('auctions/won', [
            'pageTitle' => 'Aukcja wygrana',
            'auctionId' => $id,
        ]);
    }
}
