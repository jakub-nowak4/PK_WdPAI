<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request;
use App\Service\AuctionListService;
use App\Service\CurrentUserProvider;
use App\Service\Translator;

final class AuctionController extends AppController
{
    private const PREVIEW_STATES = ['confirm', 'leading', 'error', 'outbid'];

    public function __construct(
        Translator $translator,
        CurrentUserProvider $currentUsers,
        private readonly AuctionListService $auctionList,
    ) {
        parent::__construct($translator, $currentUsers);
    }

    public function index(Request $request): void
    {
        // Walidację query (400 przy złym formacie), filtry i paginację robi serwis (docs/api.md 2.2).
        $list = $this->auctionList->fromRequest($request);

        // MAKIETA (T-009): widok nadal rysuje karty z partials/mock-auctions.php, dopóki T-030 go nie podłączy.
        // $filters, $page czyta już formularz i paginacja widoku; $auctionCards i $brandOptions to dane z bazy dla T-030.
        $this->render('auctions/index', [
            'pageTitle' => $this->translator->translate('page.auctions.title'),
            'auctionCards' => $list->getAuctions(),
            'brandOptions' => $list->getBrands(),
            'filters' => $list->getFilters(),
            'page' => $list->getPage(),
            'pages' => $list->getPages(),
            'total' => $list->getTotal(),
        ]);
    }

    public function show(int $id, Request $request): void
    {
        // MAKIETA (T-009): ?state=confirm|leading|error|outbid pokazuje ekrany 04-07; zła wartość to 400.
        $state = $request->queryOneOf('state', self::PREVIEW_STATES);

        // MAKIETA: dane aukcji statyczne, do czasu repozytorium.
        $this->render('auctions/show', [
            'pageTitle' => $this->translator->translate('page.auction_show.title'),
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
            'pageTitle' => $this->translator->translate('page.auction_won.title'),
            'auctionId' => $id,
        ]);
    }
}
