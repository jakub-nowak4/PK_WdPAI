<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ValidationException;
use App\Request;
use App\Service\AuthService;
use App\Service\Translator;

final class SecurityController extends AppController
{
    public function __construct(Translator $translator, private readonly AuthService $auth)
    {
        parent::__construct($translator);
    }

    public function login(): void
    {
        $this->render('security/login', [
            'pageTitle' => 'Logowanie',
        ]);
    }

    /*
    GET /register: formularz (zalogowany: 303 na stronę roli). POST /register: CSRF, rejestracja w AuthService (walidacja
    tylko tam), auto-logowanie i Post/Redirect/Get na /auctions. Błąd reguł to 422,
    zajęty e-mail 409: ten sam widok z błędami przy polach i bez hasła w polach.
    */
    public function register(Request $request): void
    {
        // Trasa dla gościa: zalogowany (GET i POST) wraca na stronę startową roli, przed CSRF.
        if ($this->redirectIfLoggedIn()) {
            return;
        }

        if (!$request->isPost()) {
            $this->render('security/register', [
                'pageTitle' => 'Rejestracja',
            ]);

            return;
        }

        $this->verifyCsrf($request);

        $name = $this->postString($request, 'name');
        $email = $this->postString($request, 'email');

        try {
            $user = $this->auth->registerBuyer(
                $name,
                $email,
                $this->postString($request, 'password'),
                $this->postString($request, 'passwordRepeat'),
                $this->translator->language(),
            );
        } catch (ValidationException $exception) {
            http_response_code($exception->statusCode());
            $this->render('security/register', [
                'pageTitle' => 'Rejestracja',
                'errors' => $exception->errors(),
                'old' => ['name' => $name, 'email' => $email],
            ]);

            return;
        }

        $this->logInUser($user);
        $this->addFlash('success', 'register.success');
        $this->redirect('/auctions');
    }

    public function registerCompany(): void
    {
        $this->render('security/register-company', [
            'pageTitle' => 'Rejestracja firmy',
        ]);
    }

    // Pole formularza jako tekst; brak lub tablica (name[]=x) to pusty tekst, który walidator odrzuci.
    private function postString(Request $request, string $name): string
    {
        $value = $request->post($name);

        return is_string($value) ? $value : '';
    }
}
