<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request;
use App\Service\DemoUser;

final class AccountController extends AppController
{
    private const BID_STATUSES = ['all', 'leading', 'outbid', 'won', 'lost'];

    public function myBids(Request $request): void
    {
        // Zły status w query to 400 (docs/api.md).
        $status = $request->queryOneOf('status', self::BID_STATUSES, 'all');

        // MAKIETA: zalogowany kupujący demo i dane z partials/mock-my-bids.php, do czasu AuthService i repozytorium.
        $this->renderAsUser('account/my-bids', DemoUser::buyer(), [
            'pageTitle' => 'Moje oferty',
            'status' => $status,
        ]);
    }

    public function notifications(): void
    {
        // MAKIETA: kupujący demo i dane z partials/mock-notifications.php, do czasu AuthService i repozytorium.
        $this->renderAsUser('account/notifications', DemoUser::buyer(), [
            'pageTitle' => 'Powiadomienia',
        ]);
    }

    public function pending(): void
    {
        // MAKIETA: firma demo oczekujaca na zatwierdzenie, do czasu AuthService (karta auth).
        $this->renderAsUser('account/pending', DemoUser::pendingCompany(), [
            'pageTitle' => 'Konto czeka na zatwierdzenie',
        ]);
    }
}
