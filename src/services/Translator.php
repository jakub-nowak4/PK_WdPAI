<?php

declare(strict_types=1);

namespace App\Service;

/*
Tłumaczenia interfejsu i komunikatów walidacji (PL/EN).
Katalog językowy to plik lang/{kod}.php zwracający płaską tablicę
'klucz.kropkowany' => 'tekst'. Tekst może zawierać symbole {nazwa}, które
translate() zastępuje wartościami z tablicy $params.
Kolejność wyszukiwania: język bieżący -> język zapasowy (pl) -> $default -> sam klucz.
Obiekt jest niezmienny: zmiana języka to utworzenie nowego Translatora.
*/
final class Translator
{
    public const DEFAULT_LANGUAGE = 'pl';

    /** Biała lista kodów języków (nigdy nie składamy ścieżki pliku z nieprzefiltrowanego wejścia). */
    public const SUPPORTED_LANGUAGES = ['pl', 'en'];

    /** @var array<string, string> */
    private readonly array $messages;

    /** @var array<string, string> */
    private readonly array $fallbackMessages;

    private readonly string $language;

    public function __construct(private readonly string $langDir, string $language = self::DEFAULT_LANGUAGE)
    {
        $this->language = self::isSupported($language) ? $language : self::DEFAULT_LANGUAGE;
        $this->fallbackMessages = $this->load(self::DEFAULT_LANGUAGE);
        $this->messages = $this->language === self::DEFAULT_LANGUAGE
            ? $this->fallbackMessages
            : $this->load($this->language);
    }

    public static function isSupported(string $language): bool
    {
        return in_array($language, self::SUPPORTED_LANGUAGES, true);
    }

    public function language(): string
    {
        return $this->language;
    }

    public function has(string $key): bool
    {
        return isset($this->messages[$key]) || isset($this->fallbackMessages[$key]);
    }

    /**
     * @param array<string, scalar> $params wartości dla symboli {nazwa}
     */
    public function translate(string $key, array $params = [], string $default = ''): string
    {
        $text = $this->messages[$key]
            ?? $this->fallbackMessages[$key]
            ?? ($default !== '' ? $default : $key);

        if ($params === []) {
            return $text;
        }

        $replacements = [];

        foreach ($params as $name => $value) {
            $replacements['{' . $name . '}'] = (string) $value;
        }

        return strtr($text, $replacements);
    }

    /*
    Skrót dla widoków: ($t)('klucz', 'tekst domyślny', ['param' => 1]).
    Taki podpis ma zmienna $t w layoutcie i partialach.
    */
    public function asClosure(): \Closure
    {
        return fn (string $key, string $default = '', array $params = []): string
            => $this->translate($key, $params, $default);
    }

    /**
     * @return array<string, string>
     */
    private function load(string $language): array
    {
        $path = rtrim($this->langDir, '/\\') . '/' . $language . '.php';

        if (!is_file($path)) {
            throw new \RuntimeException('Translation catalog not found: ' . $language);
        }

        $messages = require $path;

        if (!is_array($messages)) {
            throw new \RuntimeException('Translation catalog must return an array: ' . $language);
        }

        return $messages;
    }
}
