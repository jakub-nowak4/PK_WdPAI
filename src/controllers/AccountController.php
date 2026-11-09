<?php

declare(strict_types=1);

namespace App\Controller;

final class AccountController extends AppController
{
    public function pending(): void
    {
        $this->render('account/pending', [
            'pageTitle' => 'Konto czeka na zatwierdzenie',
        ]);
    }
}
