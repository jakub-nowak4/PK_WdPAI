<?php

declare(strict_types=1);

namespace App\Controller;

final class SecurityController extends AppController
{
    public function login(): void
    {
        $this->render('security/login', [
            'pageTitle' => 'Logowanie',
        ]);
    }

    public function register(): void
    {
        $this->render('security/register', [
            'pageTitle' => 'Rejestracja',
        ]);
    }

    public function registerCompany(): void
    {
        $this->render('security/register-company', [
            'pageTitle' => 'Rejestracja firmy',
        ]);
    }
}
