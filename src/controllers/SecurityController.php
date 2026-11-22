<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\InvalidCredentialsException;
use App\Exception\ValidationException;
use App\Request;
use App\Service\AuthService;
use App\Service\LanguageService;
use App\Service\Translator;

final class SecurityController extends AppController
{
    public function __construct(Translator $translator, private readonly AuthService $auth)
    {
        parent::__construct($translator);
    }

    /*
    GET /login: formularz (zalogowany: 303 na stronę roli, przed CSRF). POST /login: CSRF,
    AuthService::login, logInUser i Post/Redirect/Get na `next` (jeśli bezpieczny) albo na
    stronę startową roli. Puste pola lub zły e-mail to 422, zły e-mail lub hasło 401 z jednym
    ogólnym komunikatem; formularz wraca z zachowanym e-mailem, nigdy z hasłem.
    */
    public function login(Request $request): void
    {
        if ($this->redirectIfLoggedIn()) {
            return;
        }

        if (!$request->isPost()) {
            $this->render('security/login', [
                'pageTitle' => 'Logowanie',
                'next' => $this->safeNext($request->query('next')) ?? '',
            ]);

            return;
        }

        $this->verifyCsrf($request);

        $email = $this->postString($request, 'email');
        $next = $this->safeNext($request->post('next'));

        try {
            $user = $this->auth->login($email, $this->postString($request, 'password'));
        } catch (ValidationException $exception) {
            http_response_code($exception->statusCode());
            $this->render('security/login', [
                'pageTitle' => 'Logowanie',
                'errors' => $exception->errors(),
                'old' => ['email' => $email],
                'next' => $next ?? '',
            ]);

            return;
        } catch (InvalidCredentialsException $exception) {
            http_response_code(401);
            $this->render('security/login', [
                'pageTitle' => 'Logowanie',
                'formError' => $exception->getMessage(),
                'old' => ['email' => $email],
                'next' => $next ?? '',
            ]);

            return;
        }

        $this->logInUser($user);
        $this->redirect($next ?? $this->homePathFor($user->getRole()));
    }

    /*
    POST /logout: tylko CSRF (przycisk w nagłówku i dolnej nawigacji to formularz POST),
    potem zniszczenie sesji i 303 na stronę główną.
    */
    public function logout(Request $request): void
    {
        $this->verifyCsrf($request);
        $this->logOutUser();
        $this->redirect('/');
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

    /*
    `next` z formularza lub query (docs/api.md, 1.5): tylko ścieżka względna z reguł
    LanguageService::safeBackPath (jeden "/", bez "//", "\" i znaków sterujących, do 200
    znaków). Inna wartość, także pusta, to null (ochrona przed open redirect).
    */
    private function safeNext(string|array|null $raw): ?string
    {
        $path = LanguageService::safeBackPath($raw);

        return $raw === $path ? $path : null;
    }

    // Pole formularza jako tekst; brak lub tablica (name[]=x) to pusty tekst, który walidator odrzuci.
    private function postString(Request $request, string $name): string
    {
        $value = $request->post($name);

        return is_string($value) ? $value : '';
    }
}
