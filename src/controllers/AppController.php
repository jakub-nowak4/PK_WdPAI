<?php

declare(strict_types=1);

namespace App\Controller;

abstract class AppController
{
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

        extract($variables, EXTR_SKIP);

        ob_start();
        require $templatePath;
        $content = ob_get_clean();

        require $layoutPath;
    }
}
