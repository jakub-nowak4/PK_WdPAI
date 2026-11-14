<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Request;

/*
Odpowiedzi błędów 400, 403 i 404. Dla ścieżek /api/* zwraca JSON w formacie z
docs/api.md (sekcja 3), dla pozostałych stronę HTML.
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
        403 => [
            'code' => 'forbidden',
            'message' => 'error.forbidden',
            'view' => 'errors/403',
        ],
        404 => [
            'code' => 'not_found',
            'message' => 'error.not_found',
            'view' => 'not-found',
        ],
    ];

    public function handle(HttpException $exception, Request $request): void
    {
        $status = array_key_exists($exception->statusCode(), self::ERRORS)
            ? $exception->statusCode()
            : 404;
        $error = self::ERRORS[$status];

        // Własny komunikat z wyjątku dotyczy tylko 403 (reguły biznesowe po polsku).
        // Komunikaty routera dla 400/404 są techniczne i po angielsku, więc ich nie pokazujemy.
        if ($status === 403 && $exception->getMessage() !== '') {
            $error['message'] = $exception->getMessage();
        } else {
            $error['message'] = $this->translator->translate($error['message']);
        }

        http_response_code($status);

        if (str_starts_with($request->path(), self::API_PREFIX)) {
            $this->renderJson($error['code'], $error['message']);

            return;
        }

        if (!$this->viewExists($error['view'])) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $status . ' ' . $error['message'];

            return;
        }

        $this->render($error['view'], [
            'pageTitle' => (string) $status,
            'errorMessage' => $error['message'],
        ]);
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
