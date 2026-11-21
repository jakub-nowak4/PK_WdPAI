<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Model\Role;
use App\Request;
use App\Service\Translator;

abstract class AppController
{
    /*
    Translator trafia do kontrolera przez konstruktor (Routing wstrzykuje go na
    podstawie typu). Kontroler z dodatkowymi zależnościami woła parent::__construct().
    */
    public function __construct(protected readonly Translator $translator)
    {
    }

    protected function viewExists(string $template): bool
    {
        return is_file($this->templatePath($template));
    }

    private function templatePath(string $template): string
    {
        return dirname(__DIR__, 2) . '/public/views/' . $template . '.html.php';
    }

    protected function render(string $template, array $variables = []): void
    {
        $templatePath = $this->templatePath($template);

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        $layoutPath = dirname(__DIR__, 2) . '/public/views/layout.html.php';

        // Tłumaczenia dla każdego widoku: $t('klucz', 'tekst domyślny') i $lang.
        // Wartości przekazane przez kontroler mają pierwszeństwo (EXTR_SKIP).
        // $csrfToken trafia do ukrytych pól formularzy (name="csrfToken") i do meta csrf-token.
        $variables += [
            't' => $this->translator->asClosure(),
            'lang' => $this->translator->language(),
            'csrfToken' => session_status() === PHP_SESSION_ACTIVE ? $this->csrfToken() : '',
        ];

        extract($variables, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        require $layoutPath;
    }

    /*
    Post/Redirect/Get: odpowiedź 303 na adres względny. Wywołujący ma przekazać
    ścieżkę już zwalidowaną (np. LanguageService::safeBackPath()).
    */
    protected function redirect(string $path): void
    {
        http_response_code(303);
        header('Location: ' . $path);
    }

    /*
    Id zalogowanego użytkownika z sesji albo null dla gościa. Sesja trzyma tylko
    userId i role (docs/api.md, 2.4); wartość o złym typie traktujemy jak brak logowania.
    */
    protected function currentUserId(): ?int
    {
        $userId = $_SESSION['userId'] ?? null;

        return is_int($userId) && $userId > 0 ? $userId : null;
    }

    protected function currentRole(): ?Role
    {
        $role = $_SESSION['role'] ?? null;

        return is_string($role) ? Role::tryFrom($role) : null;
    }

    /*
    Autoryzacja po stronie serwera, na początku chronionej akcji (kryterium 22).
    Gość: wyjątek 401, który ErrorController zamienia na 303 na /login (HTML)
    albo 401 JSON (/api/*). Zwraca id użytkownika. Strona chroniona nie trafia do
    cache przeglądarki, więc "Wstecz" po wylogowaniu nie pokaże danych (kryterium 24).
    */
    protected function requireLogin(): int
    {
        $userId = $this->currentUserId();

        if ($userId === null || $this->currentRole() === null) {
            throw HttpException::unauthorized();
        }

        header('Cache-Control: no-store');

        return $userId;
    }

    /*
    Wymaga zalogowania i jednej z podanych ról; inna rola to 403. Zwraca id użytkownika.
    Status firmy (zatwierdzona/oczekująca) sprawdza kontroler firmy, nie ta metoda.
    */
    protected function requireRole(Role ...$roles): int
    {
        $userId = $this->requireLogin();

        if (!in_array($this->currentRole(), $roles, true)) {
            throw HttpException::forbidden();
        }

        return $userId;
    }

    /*
    Token CSRF (docs/api.md, 1.4): jeden na sesję, tworzony przy pierwszym użyciu
    z random_bytes i trzymany w $_SESSION['csrf'].
    */
    protected function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \LogicException('CSRF token requires an active session.');
        }

        $token = $_SESSION['csrf'] ?? null;

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf'] = $token;
        }

        return $token;
    }

    /*
    Sprawdza token CSRF z pola formularza `csrfToken` albo nagłówka `X-CSRF-Token`
    (Fetch API). Porównanie stałoczasowe; brak lub zły token to 403 (csrf_invalid).
    Wołać na początku każdej akcji obsługującej POST.
    */
    protected function verifyCsrf(Request $request): void
    {
        $sent = $request->post('csrfToken');

        if (!is_string($sent)) {
            $sent = $request->header('X-CSRF-Token');
        }

        if ($sent === null || $sent === '' || !hash_equals($this->csrfToken(), $sent)) {
            throw HttpException::csrfInvalid();
        }
    }

    /*
    Komunikat jednorazowy po 303 (docs/api.md, 1.6): typ success|error|info i klucz
    tłumaczenia. Widok odczyta i usunie go z sesji przy następnym renderze.
    */
    protected function addFlash(string $type, string $messageKey): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION['flash'] = ['type' => $type, 'message' => $messageKey];
    }

    /**
     * MAKIETA: renderuje widok jako zalogowany uzytkownik demo (klucz `currentUser` dla layoutu).
     * Jedyne miejsce do podmiany na uzytkownika z sesji, gdy powstanie AuthService.
     *
     * @param array<string, mixed> $currentUser
     * @param array<string, mixed> $variables
     */
    protected function renderAsUser(string $template, array $currentUser, array $variables = []): void
    {
        $this->render($template, ['currentUser' => $currentUser] + $variables);
    }
}
