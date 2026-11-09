<?php

declare(strict_types=1);

namespace App\Controller;

final class AuctionController extends AppController
{
    public function show(int $id): void
    {
        $this->render('auctions/show', [
            'pageTitle' => 'Szczegóły aukcji',
            'auctionId' => $id,
        ]);
    }
}
