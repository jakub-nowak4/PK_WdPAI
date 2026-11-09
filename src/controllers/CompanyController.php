<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DemoUser;

final class CompanyController extends AppController
{
    public function index(): void
    {
        // MAKIETA: zalogowana, zatwierdzona firma demo, do czasu AuthService (karta auth).
        $this->renderAsUser('company/index', DemoUser::approvedCompany(), [
            'pageTitle' => 'Panel firmy',
        ]);
    }

    public function create(): void
    {
        // MAKIETA: zalogowana, zatwierdzona firma demo, do czasu AuthService (karta auth).
        $this->renderAsUser('company/create', DemoUser::approvedCompany(), [
            'pageTitle' => 'Dodaj aukcję',
        ]);
    }
}
