<?php

declare(strict_types=1);

namespace App\Service;

use App\Request;

/*
Wybór i zapamiętanie języka interfejsu (docs/api.md, 2.7).
Język ustalany przy każdym żądaniu: sesja -> ciasteczko `lang` -> pl.
Wartości spoza białej listy (Translator::SUPPORTED_LANGUAGES) są ignorowane.
Sesja musi być uruchomiona w public/index.php przed wywołaniem resolve()/remember();
bez aktywnej sesji działa samo ciasteczko.
*/
final class LanguageService
{
    public const COOKIE_NAME = 'lang';
    private const SESSION_KEY = 'lang';
    private const COOKIE_LIFETIME = 31536000; // 1 rok
    private const MAX_BACK_LENGTH = 200;

    public function resolve(Request $request): string
    {
        $fromSession = $this->sessionActive() ? ($_SESSION[self::SESSION_KEY] ?? null) : null;

        foreach ([$fromSession, $request->cookie(self::COOKIE_NAME)] as $candidate) {
            if (is_string($candidate) && Translator::isSupported($candidate)) {
                return $candidate;
            }
        }

        return Translator::DEFAULT_LANGUAGE;
    }

    /*
    Zapisuje wybór w sesji i w ciasteczku (HttpOnly, SameSite=Lax, Path=/, 1 rok).
    Wywołujący sprawdza wcześniej język (Translator::isSupported).
    */
    public function remember(string $language, bool $secure): void
    {
        if (!Translator::isSupported($language)) {
            throw new \InvalidArgumentException('Unsupported language: ' . $language);
        }

        if ($this->sessionActive()) {
            $_SESSION[self::SESSION_KEY] = $language;
        }

        setcookie(self::COOKIE_NAME, $language, [
            'expires' => time() + self::COOKIE_LIFETIME,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /*
    Bezpieczny adres powrotu po zmianie języka (ochrona przed open redirect):
    tylko ścieżka względna zaczynająca się od jednego "/", bez "//", "\", spacji
    i znaków sterujących, maks. 200 znaków. Inna wartość daje "/".
    */
    public function safeBackPath(string|array|null $back): string
    {
        if (!is_string($back) || strlen($back) > self::MAX_BACK_LENGTH) {
            return '/';
        }

        return preg_match('~\A/(?!/)[^\\\\\x00-\x20\x7f]*\z~', $back) === 1 ? $back : '/';
    }

    private function sessionActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}
