<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request;
use App\Service\DemoUser;

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

        // MAKIETA (T-009): zalogowany kupujący demo i lista z partials/mock-auctions.php, do czasu repozytorium.
        $this->renderAsUser('auctions/index', DemoUser::buyer(), [
            'pageTitle' => 'Aukcje',
            'filters' => $filters,
            'page' => $page,
        ]);
    }

    public function show(int $id, Request $request): void
    {
        // MAKIETA (T-009): ?state=confirm|leading|error|outbid pokazuje ekrany 04-07; zła wartość to 400.
        $state = $request->queryOneOf('state', self::PREVIEW_STATES);

        // MAKIETA: zalogowany kupujacy demo, do czasu AuthService (karta auth).
        $this->renderAsUser('auctions/show', DemoUser::buyer(), [
            'pageTitle' => 'Szczegóły aukcji',
            'auctionId' => $id,
            'previewState' => $state,
        ]);
    }

    public function won(int $id): void
    {
        // MAKIETA (T-009): ekran 08 jako osobna strona; docelowo tylko zwycięzca (403/404 inaczej).
        $this->renderAsUser('auctions/won', DemoUser::buyer(), [
            'pageTitle' => 'Aukcja wygrana',
            'auctionId' => $id,
        ]);
    }
}
