<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MailException;
use App\Model\Role;
use App\Model\User;

/*
E-mail powitalny po rejestracji (docs/api.md, 2.4). Treść składa się z kluczy tłumaczeń
w języku zapisanym na koncie (users.locale), a nie w języku bieżącego żądania, więc
ten sam kod zadziała też poza żądaniem (np. CRON). Kupujący dostaje zaproszenie do
aukcji, firma informację, że konto czeka na zatwierdzenie. Wysyłkę wykonuje wstrzyknięty
MailerInterface; błędy poczty (MailException) obsługuje wołający, tu ich nie połykamy.
*/
final class WelcomeMailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $langDir,
        private readonly string $appUrl,
    ) {
    }

    /**
     * @throws MailException
     */
    public function sendWelcome(User $user): void
    {
        $translator = new Translator($this->langDir, $user->getLocale());
        $baseUrl = rtrim($this->appUrl, '/');

        if ($user->hasRole(Role::Dealer)) {
            $prefix = 'mail.welcome_company';
            $url = $baseUrl . '/account/pending';
        } else {
            $prefix = 'mail.welcome';
            $url = $baseUrl . '/auctions';
        }

        $this->mailer->send(
            $user->getEmail(),
            $user->getName(),
            $translator->translate($prefix . '.subject'),
            $translator->translate($prefix . '.body', ['name' => $user->getName(), 'url' => $url]),
        );
    }
}
