<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Request;
use App\Service\LanguageService;

/*
Odpowiedzi błędów 400, 401, 403 i 404. Dla ścieżek /api/* zwraca JSON w formacie z
docs/api.md (sekcja 3), dla pozostałych stronę HTML.
401 w HTML nie jest stroną błędu: gość dostaje 303 na /login (GET: z ?next=<ścieżka>,
POST: komunikat "Sesja wygasła"), w JSON zwykłe 401 login_required (docs/api.md, 1.3 i 1.5).
Domyślne komunikaty pochodzą z katalogu tłumaczeń (klucze error.*); własny komunikat
403 z wyjątku zostaje bez zmian.
*/
final class ErrorController extends AppController
{
    private const API_PREFIX = '/api/';

    private const ERRORS = [
        400 => [
            'code' => 'bad_request',
            'message' => 'error.bad_request',
            'view' => 'errors/400',
        ],
        401 => [
            'code' => 'login_required',
            'message' => 'error.login_required',
        ],
        403 => [
            'code' => 'forbidden',
            'message' => 'error.forbidden',
            'view' => 'errors/403',
        ],
        404 => [
            'code' => 'not_found',
            'message' => 'error.not_found',
            'view' => 'errors/404',
        ],
    ];

    /** Kody błędów z wyjątku, które mają własny klucz tłumaczenia (docs/api.md, sekcja 4). */
    private const CODE_MESSAGES = [
        'login_required' => 'error.login_required',
        'csrf_invalid' => 'error.csrf_invalid',
    ];

    public function handle(HttpException $exception, Request $request): void
    {
        $status = array_key_exists($exception->statusCode(), self::ERRORS)
            ? $exception->statusCode()
            : 404;
        $error = self::ERRORS[$status];
        $isApi = str_starts_with($request->path(), self::API_PREFIX);

        if ($status === 401 && !$isApi) {
            $this->redirectToLogin($request);

            return;
        }

        $exceptionCode = $exception->errorCode();

        if ($exceptionCode !== null && $status === $exception->statusCode()) {
            $error['code'] = $exceptionCode;
            $error['message'] = self::CODE_MESSAGES[$exceptionCode] ?? $error['message'];
        }

        // Własny komunikat z wyjątku dotyczy tylko 403 (reguły biznesowe po polsku).
        // Komunikaty routera dla 400/404 są techniczne i po angielsku, więc ich nie pokazujemy.
        if ($status === 403 && $exception->getMessage() !== '') {
            $error['message'] = $exception->getMessage();
        } else {
            $error['message'] = $this->translator->translate($error['message']);
        }

        http_response_code($status);

        if ($isApi) {
            $this->renderJson($error['code'], $error['message']);

            return;
        }

        if (!isset($error['view']) || !$this->viewExists($error['view'])) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $status . ' ' . $error['message'];

            return;
        }

        $this->render($error['view'], [
            'pageTitle' => $this->translator->translate('error.' . $status . '.title'),
            'errorMessage' => $error['message'],
        ]);
    }

    /*
    Gość na stronie chronionej. GET wraca po zalogowaniu na bieżący adres (next
    przechodzi tę samą walidację co back w LanguageService); POST kończy się
    komunikatem o wygasłej sesji, bo formularza nie da się powtórzyć automatycznie.
    */
    private function redirectToLogin(Request $request): void
    {
        if ($request->isPost()) {
            $this->addFlash('error', 'auth.session_expired');
            $this->redirect('/login');

            return;
        }

        $next = LanguageService::safeBackPath($request->pathWithQuery());

        $this->redirect('/login?next=' . rawurlencode($next));
    }

    private function renderJson(string $code, string $message): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        echo json_encode(
            ['error' => ['code' => $code, 'message' => $message]],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }
}
