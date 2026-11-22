<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Model\Company;
use App\Model\Role;
use App\Repository\CompanyRepository;
use App\Service\CurrentUserProvider;
use App\Service\Translator;

final class CompanyController extends AppController
{
    public function __construct(
        Translator $translator,
        CurrentUserProvider $currentUsers,
        private readonly CompanyRepository $companies,
    ) {
        parent::__construct($translator, $currentUsers);
    }

    public function index(): void
    {
        $this->requireApprovedCompany();

        // MAKIETA: tresc statyczna, do czasu panelu firmy.
        $this->render('company/index', [
            'pageTitle' => 'Panel firmy',
        ]);
    }

    public function create(): void
    {
        $this->requireApprovedCompany();

        // MAKIETA: tresc statyczna, do czasu panelu firmy.
        $this->render('company/create', [
            'pageTitle' => 'Dodaj aukcję',
        ]);
    }

    public function delete(int $id): void
    {
        // Guard przed regułą biznesową: gość 303 na /login, kupujący 403, firma niezatwierdzona 403.
        $this->requireApprovedCompany();

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

    /*
    Guard tras firmy (docs/api.md, 1.5): rola DEALER (requireRole: gość 401, inna rola 403),
    potem firma z bazy po userId. Status zawsze z bazy, nie z sesji, więc decyzja administratora
    działa od razu. Firma pending lub rejected (albo brak profilu) to 403 z ekranem
    "konto czeka na zatwierdzenie" (ErrorController). Zwraca zatwierdzoną firmę.
    */
    private function requireApprovedCompany(): Company
    {
        $userId = $this->requireRole(Role::Dealer);
        $company = $this->companies->findByUserId($userId);

        if ($company === null) {
            throw HttpException::forbidden();
        }

        if (!$company->isApproved()) {
            throw HttpException::companyNotApproved($company->getStatus()->value);
        }

        return $company;
    }
}
