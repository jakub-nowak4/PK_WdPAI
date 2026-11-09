<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DemoUser;

final class AuctionController extends AppController
{
    public function show(int $id): void
    {
        // MAKIETA: zalogowany kupujacy demo, do czasu AuthService (karta auth).
        $this->renderAsUser('auctions/show', DemoUser::buyer(), [
            'pageTitle' => 'Szczegóły aukcji',
            'auctionId' => $id,
        ]);
    }
}
