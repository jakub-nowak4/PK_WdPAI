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
    public function __construct(
        Translator $translator,
        private readonly AuthService $auth,
        private readonly LanguageService $languages,
    ) {
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
                'pageTitle' => $this->translator->translate('page.login.title'),
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
                'pageTitle' => $this->translator->translate('page.login.title'),
                'errors' => $exception->errors(),
                'old' => ['email' => $email],
                'next' => $next ?? '',
            ]);

            return;
        } catch (InvalidCredentialsException $exception) {
            http_response_code(401);
            $this->render('security/login', [
                'pageTitle' => $this->translator->translate('page.login.title'),
                'formError' => $exception->getMessage(),
                'old' => ['email' => $email],
                'next' => $next ?? '',
            ]);

            return;
        }

        $this->logInUser($user);
        // Język zapisany na koncie wygrywa z wyborem sprzed logowania (UI i e-maile w jednym języku).
        $this->languages->adoptAccountLanguage($user, $request->isSecure());
        $this->redirect($next ?? $this->homePathFor($user->getRole()));
    }

    /*
    POST /logout (docs/api.md, 2.4): gość (brak zalogowanego) dostaje 303 na "/" bez CSRF, bo
    wylogowanie jest idempotentne. Zalogowany: CSRF (zły token to 403), zniszczenie sesji,
    nowa sesja tylko na komunikat "Wylogowano." (flash) i 303 na stronę główną.
    */
    public function logout(Request $request): void
    {
        if ($this->currentUserId() === null) {
            $this->redirect('/');

            return;
        }

        $this->verifyCsrf($request);
        $this->logOutUser();

        // Stary identyfikator jest już nieważny (use_strict_mode), więc ta sesja dostaje nowy.
        session_start();
        $this->addFlash('info', 'auth.logged_out');
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
                'pageTitle' => $this->translator->translate('page.register.title'),
            ]);

            return;
        }

        $this->verifyCsrf($request);

        $name = $this->postString($request, 'name');
        $email = $this->postString($request, 'email');

        try {
            $registration = $this->auth->registerBuyer(
                $name,
                $email,
                $this->postString($request, 'password'),
                $this->postString($request, 'passwordRepeat'),
                $this->translator->language(),
            );
        } catch (ValidationException $exception) {
            http_response_code($exception->statusCode());
            $this->render('security/register', [
                'pageTitle' => $this->translator->translate('page.register.title'),
                'errors' => $exception->errors(),
                'old' => ['name' => $name, 'email' => $email],
            ]);

            return;
        }

        // Konto jest zapisane także wtedy, gdy e-mail powitalny nie wyszedł (szczegóły w logu).
        $this->logInUser($registration->getUser());
        if ($registration->isWelcomeMailSent()) {
            $this->addFlash('success', 'register.success');
        } else {
            $this->addFlash('info', 'register.success_mail_failed');
        }

        $this->redirect('/auctions');
    }

    /*
    GET /register/company: formularz (zalogowany: 303 na stronę roli, przed CSRF). POST: CSRF,
    AuthService::registerCompany (konto DEALER + firma pending w jednej transakcji), logInUser
    i Post/Redirect/Get na /account/pending z komunikatem. Błąd reguł to 422, zajęty e-mail 409:
    ten sam widok z błędami przy polach i zachowanymi wartościami (bez haseł).
    */
    public function registerCompany(Request $request): void
    {
        if ($this->redirectIfLoggedIn()) {
            return;
        }

        if (!$request->isPost()) {
            $this->render('security/register-company', [
                'pageTitle' => $this->translator->translate('page.register_company.title'),
            ]);

            return;
        }

        $this->verifyCsrf($request);

        $old = [
            'name' => $this->postString($request, 'name'),
            'email' => $this->postString($request, 'email'),
            'companyName' => $this->postString($request, 'companyName'),
            'city' => $this->postString($request, 'city'),
            'phone' => $this->postString($request, 'phone'),
        ];

        try {
            $registration = $this->auth->registerCompany(
                $old['name'],
                $old['email'],
                $this->postString($request, 'password'),
                $this->postString($request, 'passwordRepeat'),
                $old['companyName'],
                $old['city'],
                $old['phone'],
                $this->translator->language(),
            );
        } catch (ValidationException $exception) {
            http_response_code($exception->statusCode());
            $this->render('security/register-company', [
                'pageTitle' => $this->translator->translate('page.register_company.title'),
                'errors' => $exception->errors(),
                'old' => $old,
            ]);

            return;
        }

        $this->logInUser($registration->getUser());
        if ($registration->isWelcomeMailSent()) {
            $this->addFlash('success', 'register_company.success');
        } else {
            $this->addFlash('info', 'register_company.success_mail_failed');
        }

        $this->redirect('/account/pending');
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
