<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
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

    public function delete(int $id): void
    {
        // MAKIETA, do zastąpienia sprawdzeniem w repozytorium: aukcja nr 1 ma oferty
        // w danych przykładowych, więc jej usunięcie daje 403 (reguła biznesowa 1).
        if ($id === 1) {
            throw HttpException::forbidden('Nie można usunąć aukcji, w której są oferty.');
        }

        // MAKIETA: zalogowana, zatwierdzona firma demo, do czasu AuthService (karta auth).
        $this->renderAsUser('company/delete', DemoUser::approvedCompany(), [
            'pageTitle' => 'Usuń aukcję',
            'auctionId' => $id,
        ]);
    }
}
