<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Exception\ValidationException;
use App\Model\AuctionSummary;
use App\Model\Company;
use App\Model\Role;
use App\Repository\BrandRepository;
use App\Repository\CompanyRepository;
use App\Request;
use App\Service\AuctionCreationService;
use App\Service\AuctionDeletionService;
use App\Service\CompanyPanelService;
use App\Service\CurrentUserProvider;
use App\Service\Translator;
use App\Service\UploadService;

final class CompanyController extends AppController
{
    // Pola tekstowe formularza aukcji (docs/api.md 2.5); zdjęcia idą osobno, jako $_FILES['photos'].
    private const FORM_FIELDS = [
        'title', 'brand', 'model', 'year', 'mileageKm', 'fuel', 'transmission', 'originCountry',
        'vin', 'description', 'startingPrice', 'minIncrement', 'startsAt', 'endsAt',
    ];

    public function __construct(
        Translator $translator,
        CurrentUserProvider $currentUsers,
        private readonly CompanyRepository $companies,
        private readonly BrandRepository $brands,
        private readonly AuctionCreationService $auctionCreation,
        private readonly AuctionDeletionService $auctionDeletion,
        private readonly CompanyPanelService $panel,
    ) {
        parent::__construct($translator, $currentUsers);
    }

    // GET /company (docs/api.md 2.5): aukcje firmy z bazy (CompanyPanelService, jedno zapytanie).
    public function index(): void
    {
        $company = $this->requireApprovedCompany();

        $this->render('company/index', [
            'pageTitle' => $this->translator->translate('page.company.title'),
            'company' => $this->panel->companyView($company),
            'auctions' => $this->panel->auctionsFor($company),
        ]);
    }

    public function create(): void
    {
        $this->requireApprovedCompany();

        $this->renderCreateForm();
    }

    /*
    POST /company/auctions (docs/api.md 2.5): zalogowany DEALER z zatwierdzoną firmą (401/403), przekroczony
    post_max_size (422, bo znika też token), CSRF (403), potem AuctionCreationService. Błąd pól lub zdjęć to 422
    i ten sam formularz z błędami oraz zachowanymi wartościami tekstowymi; sukces to 303 na /company z komunikatem.
    */
    public function store(Request $request): void
    {
        $company = $this->requireApprovedCompany();

        if ($request->isBodyDiscarded()) {
            $this->renderCreateForm(
                ['photos' => $this->translator->translate('upload.post_too_large')],
                [],
                422,
            );

            return;
        }

        $this->verifyCsrf($request);

        $input = [];
        foreach (self::FORM_FIELDS as $field) {
            $value = $request->post($field);
            $input[$field] = is_string($value) ? $value : '';
        }

        try {
            $this->auctionCreation->create(
                $company,
                $input,
                UploadService::normalizeFiles($request->files(UploadService::FIELD)),
            );
        } catch (ValidationException $exception) {
            $this->renderCreateForm($exception->errors(), $input, $exception->statusCode());

            return;
        }

        $this->addFlash('success', 'company.auction_created');
        $this->redirect('/company');
    }

    /*
    GET /company/auctions/{id}/delete (docs/api.md 2.5): ekran potwierdzenia, niczego nie usuwa. Kolejność:
    format id (router, 400) -> gość (303) -> rola i firma zatwierdzona (403) -> aukcja istnieje (404) ->
    właściciel (403) -> brak ofert (403, komunikat company.error.has_bids).
    */
    public function confirmDelete(int $id): void
    {
        $company = $this->requireApprovedCompany();
        $summary = $this->auctionDeletion->findForConfirmation($company, $id);

        $this->render('company/delete', [
            'pageTitle' => $this->translator->translate('page.company_delete.title'),
            'auction' => $this->deletionView($summary),
        ]);
    }

    /*
    POST /company/auctions/{id}/delete: te same sprawdzenia co GET, a CSRF (403) tuż po guardzie roli, przed
    sprawdzeniem aukcji. Usunięcie to transakcja w AuctionDeletionService (FOR UPDATE, brak ofert, DELETE,
    potem pliki zdjęć). Sukces: 303 na /company z komunikatem (Post/Redirect/Get).
    */
    public function delete(int $id, Request $request): void
    {
        $company = $this->requireApprovedCompany();
        $this->verifyCsrf($request);

        $this->auctionDeletion->delete($company, $id);

        $this->addFlash('success', 'company.auction_deleted');
        $this->redirect('/company');
    }

    /**
     * Dane ekranu potwierdzenia dla widoku (kwota w pełnych złotych, jak w pozostałych widokach firmy).
     *
     * @return array{id: int, title: string, startPrice: int, bidCount: int}
     */
    private function deletionView(AuctionSummary $summary): array
    {
        $auction = $summary->getAuction();

        return [
            'id' => $auction->getId(),
            'title' => $auction->getTitle(),
            'startPrice' => intdiv($auction->getStartingPrice()->grosze(), 100),
            'bidCount' => $summary->getBidCount(),
        ];
    }

    /**
     * Formularz "Dodaj aukcję" (GET i ponowne wyświetlenie po błędzie): marki ze słownika jako slug => nazwa.
     *
     * @param array<string, string> $errors pole => komunikat
     * @param array<string, string> $old zachowane wartości tekstowe (plików nie da się odtworzyć)
     */
    private function renderCreateForm(array $errors = [], array $old = [], int $status = 200): void
    {
        $brands = [];
        foreach ($this->brands->findAll() as $brand) {
            $brands[$brand->getSlug()] = $brand->getName();
        }

        http_response_code($status);
        $this->render('company/create', [
            'pageTitle' => $this->translator->translate('page.company_create.title'),
            'brands' => $brands,
            'errors' => $errors,
            'old' => $old,
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
