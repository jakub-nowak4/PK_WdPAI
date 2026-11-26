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
use App\Service\NotificationService;
use App\Service\Translator;

final class AccountController extends AppController
{
    private const BID_STATUSES = ['all', 'leading', 'outbid', 'won', 'lost'];

    public function __construct(
        Translator $translator,
        CurrentUserProvider $currentUsers,
        private readonly CompanyRepository $companies,
        private readonly MyBidsService $myBids,
        private readonly NotificationService $notifications,
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

    /*
    GET /notifications (docs/api.md, 2.4): każdy zalogowany widzi tylko swoje powiadomienia, 50 najnowszych.
    Dane dla widoku: $notificationItems (list<NotificationItem>, najnowsze pierwsze). Liczba nieprzeczytanych
    (wszystkich, także starszych niż 50) jest w $currentUser['unreadCount'] z layoutu.
    */
    public function notifications(): void
    {
        $userId = $this->requireLogin();

        $this->render('account/notifications', [
            'pageTitle' => $this->translator->translate('page.notifications.title'),
            'notificationItems' => $this->notifications->forUser($userId),
        ]);
    }

    /*
    POST /notifications/read (docs/api.md, 2.1 wiersz 15): oznacza wszystkie powiadomienia zalogowanego jako
    przeczytane. Kolejność: zalogowany (401, POST: 303 /login), CSRF (403), zapis; potem 303 na /notifications.
    */
    public function readNotifications(Request $request): void
    {
        $userId = $this->requireLogin();
        $this->verifyCsrf($request);

        $changed = $this->notifications->markAllRead($userId);
        $this->addFlash(
            $changed > 0 ? 'success' : 'info',
            $changed > 0 ? 'notifications.marked_read' : 'notifications.nothing_to_mark',
        );
        $this->redirect('/notifications');
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
