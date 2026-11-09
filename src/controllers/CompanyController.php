<?php

declare(strict_types=1);

namespace App\Controller;

final class CompanyController extends AppController
{
    public function index(): void
    {
        $this->render('company/index', [
            'pageTitle' => 'Panel firmy',
        ]);
    }

    public function create(): void
    {
        $this->render('company/create', [
            'pageTitle' => 'Dodaj aukcję',
        ]);
    }
}
