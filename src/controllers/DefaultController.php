<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuctionListService;
use App\Service\CurrentUserProvider;
use App\Service\Translator;

final class DefaultController extends AppController
{
    public function __construct(
        Translator $translator,
        CurrentUserProvider $currentUsers,
        private readonly AuctionListService $auctionList,
    ) {
        parent::__construct($translator, $currentUsers);
    }

    public function index(): void
    {
        // Ten sam blok listy co /auctions: pierwsza strona, bez filtrów, sort=ending; query nie jest czytane
        // (nieznane parametry są ignorowane, więc / nie zwraca 400, docs/api.md 2.2).
        $list = $this->auctionList->firstPage();

        // MAKIETA (T-009): widok rysuje karty z partials/mock-auctions.php, dopóki T-030 go nie podłączy.
        $this->render('home', [
            'pageTitle' => $this->translator->translate('page.home.title'),
            'auctionCards' => $list->getAuctions(),
            'brandOptions' => $list->getBrands(),
            'filters' => $list->getFilters(),
            'page' => $list->getPage(),
            'pages' => $list->getPages(),
            'total' => $list->getTotal(),
        ]);
    }
}
