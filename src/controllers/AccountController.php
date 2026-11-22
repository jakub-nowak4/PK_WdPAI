<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Role;
use App\Request;

final class AccountController extends AppController
{
    private const BID_STATUSES = ['all', 'leading', 'outbid', 'won', 'lost'];

    public function myBids(Request $request): void
    {
        // Guard z tabeli 2.1 (docs/api.md, 1.5): gość 303 na /login, inna rola 403.
        $this->requireRole(Role::Buyer);

        // Zły status w query to 400 (docs/api.md).
        $status = $request->queryOneOf('status', self::BID_STATUSES, 'all');

        // MAKIETA: dane z partials/mock-my-bids.php, do czasu repozytorium (naglowek: zalogowany uzytkownik z sesji).
        $this->render('account/my-bids', [
            'pageTitle' => 'Moje oferty',
            'status' => $status,
        ]);
    }

    public function notifications(): void
    {
        $this->requireLogin();

        // MAKIETA: dane z partials/mock-notifications.php, do czasu repozytorium.
        $this->render('account/notifications', [
            'pageTitle' => 'Powiadomienia',
        ]);
    }

    public function pending(): void
    {
        // Tylko rola; status firmy (pending/rejected/approved) dołoży T-021.
        $this->requireRole(Role::Dealer);

        // MAKIETA: tresc statyczna; status firmy do czasu T-021.
        $this->render('account/pending', [
            'pageTitle' => 'Konto czeka na zatwierdzenie',
        ]);
    }
}
