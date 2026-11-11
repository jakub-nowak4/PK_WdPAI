<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request;
use App\Service\DemoUser;

final class AuctionController extends AppController
{
    private const PREVIEW_STATES = ['confirm', 'leading', 'error'];

    public function show(int $id, Request $request): void
    {
        // MAKIETA (T-009): ?state=confirm|leading|error pokazuje ekrany 04-06; zła wartość to 400.
        $state = $request->queryOneOf('state', self::PREVIEW_STATES);

        // MAKIETA: zalogowany kupujacy demo, do czasu AuthService (karta auth).
        $this->renderAsUser('auctions/show', DemoUser::buyer(), [
            'pageTitle' => 'Szczegóły aukcji',
            'auctionId' => $id,
            'previewState' => $state,
        ]);
    }
}
