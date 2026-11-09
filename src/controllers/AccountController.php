<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DemoUser;

final class AccountController extends AppController
{
    public function pending(): void
    {
        // MAKIETA: firma demo oczekujaca na zatwierdzenie, do czasu AuthService (karta auth).
        $this->renderAsUser('account/pending', DemoUser::pendingCompany(), [
            'pageTitle' => 'Konto czeka na zatwierdzenie',
        ]);
    }
}
