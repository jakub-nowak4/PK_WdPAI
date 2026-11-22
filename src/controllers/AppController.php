<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\HttpException;
use App\Model\Role;
use App\Model\User;
use App\Request;
use App\Service\CurrentUserProvider;
use App\Service\Translator;

abstract class AppController
{
    /*
    Translator trafia do kontrolera przez konstruktor (Routing wstrzykuje go na
    podstawie typu). Kontroler z dodatkowymi zależnościami woła parent::__construct().
    CurrentUserProvider dostarcza layoutowi zalogowanego użytkownika z bazy (null =
    layout bez użytkownika, np. ErrorController poza routerem).
    */
    public function __construct(
        protected readonly Translator $translator,
        private readonly ?CurrentUserProvider $currentUsers = null,
    ) {
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

        // Layout dostaje $currentUser z bazy (docs/api.md, 1.5); kontroler może go jawnie nadpisać.
        if (!array_key_exists('currentUser', $variables)) {
            $variables['currentUser'] = $this->loadCurrentUser();
        }

        // Flash z sesji czytamy tylko wtedy, gdy kontroler nie przekazał własnego $flash.
        if (!array_key_exists('flash', $variables)) {
            $variables['flash'] = $this->takeFlash();
        }

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
    Zalogowany użytkownik dla layoutu albo null (gość). Sesja daje tylko id; nazwę, status
    firmy i licznik powiadomień czyta CurrentUserProvider z bazy. Konto usunięte z bazy
    kończy sesję (czyścimy userId i role), więc nie zostaje "duch" bez danych.
    @return array{id: int, name: string, initials: string, role: string, unreadCount: int, approved: bool}|null
    */
    private function loadCurrentUser(): ?array
    {
        $userId = $this->currentUserId();

        if ($userId === null || $this->currentRole() === null || $this->currentUsers === null) {
            return null;
        }

        $user = $this->currentUsers->find($userId);

        if ($user === null) {
            unset($_SESSION['userId'], $_SESSION['role']);
        }

        return $user;
    }

    /*
    Strona startowa roli (docs/api.md, 1.5): czysta mapa bez bazy. Firma w każdym
    statusie trafia na /account/pending, a zatwierdzoną przekierowuje dalej
    AccountController::pending (T-021).
    */
    protected function homePathFor(Role $role): string
    {
        return match ($role) {
            Role::Buyer => '/auctions',
            Role::Dealer => '/account/pending',
            Role::Admin => '/admin',
        };
    }

    /*
    Trasy tylko dla gościa (/login, /register…): zalogowany dostaje 303 na stronę
    startową roli. Zwraca true, gdy przekierowanie zostało ustawione, a wywołujący
    ma wtedy zakończyć akcję (przed CSRF i walidacją).
    */
    protected function redirectIfLoggedIn(): bool
    {
        $role = $this->currentRole();

        if ($role === null) {
            return false;
        }

        $this->redirect($this->homePathFor($role));

        return true;
    }

    /*
    Jedyne miejsce, które loguje użytkownika (docs/api.md, 1.5): wołają je rejestracja
    i logowanie. Kolejność ma znaczenie: nowy identyfikator sesji (session fixation),
    potem usunięcie starego tokenu CSRF (nowy powstanie przy następnym renderze),
    na końcu w sesji tylko id i rola. Nazwę i status firmy czytamy z bazy przy żądaniu.
    */
    protected function logInUser(User $user): void
    {
        session_regenerate_id(true);
        unset($_SESSION['csrf']);

        $_SESSION['userId'] = $user->getId();
        $_SESSION['role'] = $user->getRole()->value;
    }

    /*
    Wylogowanie (docs/api.md, 2.4): czyścimy $_SESSION, wysyłamy cookie sesji z datą w
    przeszłości (te same parametry, z jakimi je ustawiono) i niszczymy sesję po stronie
    serwera, więc stary identyfikator nie działa. Cookie `lang` jest niezależne od sesji.
    */
    protected function logOutUser(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies') === '1') {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }

        session_destroy();
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

    /*
    Odczyt komunikatu flash dla layoutu (docs/api.md, 1.6): jednorazowo, bo wpis
    znika z sesji przy pierwszym renderze. Zły kształt (typ spoza success|error|info,
    pusty lub nietekstowy klucz) daje null. Klucz jest tłumaczony przez Translator.
    @return array{type: string, message: string}|null
    */
    private function takeFlash(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !array_key_exists('flash', $_SESSION)) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        $type = is_array($flash) ? ($flash['type'] ?? null) : null;
        $key = is_array($flash) ? ($flash['message'] ?? null) : null;

        if (!in_array($type, ['success', 'error', 'info'], true) || !is_string($key) || trim($key) === '') {
            return null;
        }

        return ['type' => $type, 'message' => $this->translator->translate($key)];
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
