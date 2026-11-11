<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request;
use App\Service\DemoUser;

final class AuctionController extends AppController
{
    private const PREVIEW_STATES = ['confirm', 'leading', 'error', 'outbid', 'won'];

    public function show(int $id, Request $request): void
    {
        // MAKIETA (T-009): ?state=confirm|leading|error|outbid|won pokazuje ekrany 04-08; zła wartość to 400.
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
        // MAKIETA (T-009): ekran 08 jako stan "won" szczegółów aukcji; docelowo tylko zwycięzca (403/404 inaczej).
        $this->renderAsUser('auctions/show', DemoUser::buyer(), [
            'pageTitle' => 'Aukcja wygrana',
            'auctionId' => $id,
            'previewState' => 'won',
        ]);
    }
}
