<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\BidException;
use App\Exception\HttpException;
use App\Model\Role;
use App\Repository\UserRepository;
use App\Request;
use App\Service\AuctionStateService;
use App\Service\BidService;
use App\Service\OutbidNotifier;
use App\Service\Translator;

/*
Endpointy JSON licytacji (docs/api.md 3.1 i 3.2): GET /api/auctions/{id}/state (publiczny, polling) i
POST /api/auctions/{id}/bids (kupujący, CSRF w nagłówku X-CSRF-Token). Kontroler tylko sprawdza dostęp,
czyta wejście i zamienia wynik albo BidException na JSON; reguły oferty są w BidService, stan składa
AuctionStateService; e-mail o przebiciu wysyła po odpowiedzi OutbidNotifier. Błędy 400/401/403/404 spoza BidException zgłasza HttpException, a dla /api/*
zamienia je na JSON ErrorController.
*/
final class BidApiController extends AppController
{
    public function __construct(
        Translator $translator,
        private readonly BidService $bidService,
        private readonly AuctionStateService $states,
        private readonly UserRepository $users,
        private readonly OutbidNotifier $outbidNotifier,
    ) {
        parent::__construct($translator);
    }

    public function state(int $id): void
    {
        $state = $this->states->build($id, $this->currentUserId(), $this->currentRole());

        $this->json(200, $state);
    }

    public function placeBid(int $id, Request $request): void
    {
        // Kolejność z docs/api.md 3.2: sesja (401), CSRF (403), rola (403), JSON (400), reguły oferty w serwisie.
        $userId = $this->requireLogin();
        $this->verifyCsrf($request);

        if ($this->currentRole() !== Role::Buyer) {
            throw HttpException::forbidden('bid.error.forbidden_role', 'forbidden_role');
        }

        $body = $request->jsonBody();

        if (!array_key_exists('amount', $body)) {
            throw HttpException::badRequest('Missing amount', 'invalid_json');
        }

        // Sesja wskazuje konto, którego już nie ma w bazie: traktujemy jak brak logowania.
        $user = $this->users->findById($userId) ?? throw HttpException::unauthorized();

        try {
            $result = $this->bidService->placeBid($id, $user, $body['amount']);
        } catch (BidException $exception) {
            $this->jsonBidError($exception, $id, $userId, $user->getRole());

            return;
        }

        // Stan składamy po COMMIT: widzi nową cenę, licznik ofert i ewentualne przedłużenie.
        $bid = $result->getBid();
        $messageKey = $result->isExtended() ? 'bid.success_extended' : 'bid.success';

        $this->json(201, [
            'bid' => [
                'id' => $bid->getId(),
                'amount' => $bid->getAmount()->grosze(),
                'amountFormatted' => $bid->getAmount()->format(),
                'placedAt' => $bid->getCreatedAt()
                    ->setTimezone(new \DateTimeZone('Europe/Warsaw'))
                    ->format(\DATE_ATOM),
            ],
            'extended' => $result->isExtended(),
            'message' => $this->translator->translate($messageKey, ['seconds' => BidService::EXTEND_SECONDS]),
            'state' => $this->states->build($id, $userId, $user->getRole()),
        ], ['Location' => '/api/auctions/' . $id . '/state']);

        // E-mail dla przebitego (T-036): po COMMIT i po wysłaniu odpowiedzi, więc wolny SMTP nie opóźnia 201.
        // Powiadomienie w serwisie zapisał już BidService w transakcji; błąd poczty trafia tylko do logu.
        $this->finishResponse();
        $this->outbidNotifier->notifyByEmail($result->getOutbidUserId(), $id, $bid->getAmount());
    }

    // Oddaje odpowiedź klientowi przed dalszą pracą (PHP-FPM) i zwalnia blokadę sesji; poza FPM nic nie robi.
    private function finishResponse(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    // Błąd reguły oferty: status i kod z wyjątku; przy 409/422 dokładamy aktualny stan, żeby JS odświeżył cenę.
    private function jsonBidError(BidException $exception, int $auctionId, int $userId, Role $role): void
    {
        $error = ['code' => $exception->errorCode(), 'message' => $exception->getMessage()];

        if (in_array($exception->errorCode(), ['amount_invalid', 'bid_too_low'], true)) {
            $error['field'] = 'amount';
        }

        if ($exception->details() !== []) {
            $error['details'] = $exception->details();
        }

        $payload = ['error' => $error];

        if (in_array($exception->statusCode(), [409, 422], true)) {
            $payload['state'] = $this->states->build($auctionId, $userId, $role);
        }

        $this->json($exception->statusCode(), $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    private function json(int $status, array $payload, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
