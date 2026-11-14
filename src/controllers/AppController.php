<?php

declare(strict_types=1);

namespace App\Controller;

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
        $variables += [
            't' => $this->translator->asClosure(),
            'lang' => $this->translator->language(),
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
