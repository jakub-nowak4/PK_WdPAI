<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Service\DemoUser;

final class CompanyController extends AppController
{
    public function index(): void
    {
        // MAKIETA: tresc statyczna, do czasu panelu firmy.
        $this->render('company/index', [
            'pageTitle' => 'Panel firmy',
        ]);
    }

    public function create(): void
    {
        // MAKIETA: tresc statyczna, do czasu panelu firmy.
        $this->render('company/create', [
            'pageTitle' => 'Dodaj aukcję',
        ]);
    }

    public function delete(int $id): void
    {
        // MAKIETA, do zastąpienia sprawdzeniem w repozytorium: aukcja nr 1 ma oferty
        // w danych przykładowych, więc jej usunięcie daje 403 (reguła biznesowa 1).
        if ($id === 1) {
            throw HttpException::forbidden('company.error.has_bids');
        }

        // MAKIETA: tresc statyczna, do czasu panelu firmy.
        $this->render('company/delete', [
            'pageTitle' => 'Usuń aukcję',
            'auctionId' => $id,
        ]);
    }
}
