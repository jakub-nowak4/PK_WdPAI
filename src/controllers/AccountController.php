<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Model\MyBidStatus;
use App\Model\Role;
use App\Repository\CompanyRepository;
use App\Request;
use App\Service\CurrentUserProvider;
use App\Service\MyBidsService;
use App\Service\Translator;

final class AccountController extends AppController
{
    private const BID_STATUSES = ['all', 'leading', 'outbid', 'won', 'lost'];

    public function __construct(
        Translator $translator,
        CurrentUserProvider $currentUsers,
        private readonly CompanyRepository $companies,
        private readonly MyBidsService $myBids,
    ) {
        parent::__construct($translator, $currentUsers);
    }

    public function myBids(Request $request): void
    {
        // Guard z tabeli 2.1 (docs/api.md, 1.5): gość 303 na /login, inna rola 403.
        $userId = $this->requireRole(Role::Buyer);

        // Zły status w query to 400 (docs/api.md); "all" to brak filtra.
        $status = $request->queryOneOf('status', self::BID_STATUSES, 'all');
        $overview = $this->myBids->forUser($userId, MyBidStatus::tryFrom((string) $status));

        // MAKIETA (T-009): widok nadal rysuje wiersze z partials/mock-my-bids.php, dopóki T-039 go nie podłączy.
        // Dane z bazy dla widoku: $myBids (list<MyBid>, po filtrze), $statusCounts (all, leading, outbid, won, lost),
        // $runningTotal (Money, suma ofert w trwających aukcjach), $serverTime (DateTimeImmutable).
        $this->render('account/my-bids', [
            'pageTitle' => $this->translator->translate('page.my_bids.title'),
            'status' => $status,
            'myBids' => $overview->getBids(),
            'statusCounts' => $overview->getCounts(),
            'runningTotal' => $overview->getRunningTotal(),
            'serverTime' => $overview->getServerTime(),
        ]);
    }

    public function notifications(): void
    {
        $this->requireLogin();

        // MAKIETA: dane z partials/mock-notifications.php, do czasu repozytorium.
        $this->render('account/notifications', [
            'pageTitle' => $this->translator->translate('page.notifications.title'),
        ]);
    }

    /*
    GET /account/pending (docs/api.md, 2.4): tylko DEALER (gość 303 na /login, inna rola 403).
    Status firmy czytamy z bazy: pending i rejected to 200 z odpowiednim ekranem, zatwierdzona
    firma dostaje 303 na /company (od tego zależy strona startowa firmy z homePathFor()).
    */
    public function pending(): void
    {
        $userId = $this->requireRole(Role::Dealer);
        $company = $this->companies->findByUserId($userId);

        // Konto DEALER zawsze ma profil firmy; jego brak to niespójne dane, nie dostęp do panelu.
        if ($company === null) {
            throw HttpException::forbidden();
        }

        if ($company->isApproved()) {
            $this->redirect('/company');

            return;
        }

        $this->render('account/pending', [
            'pageTitle' => $this->translator->translate('page.pending.title'),
            'companyStatus' => $company->getStatus()->value,
        ]);
    }
}
