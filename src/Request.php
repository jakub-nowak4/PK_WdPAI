<?php

declare(strict_types=1);

namespace App;

use App\Exception\HttpException;

/*
Niezmienny obiekt żądania tworzony raz w public/index.php. Dzięki niemu
kontrolery nie sięgają do $_GET, $_POST i $_SERVER. Metody query* walidują
parametry query: pusty lub brakujący parametr daje wartość domyślną (formularz
GET wysyła puste pola), zły format kończy się wyjątkiem 400.
*/
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $cookies
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $cookies = [],
        private readonly bool $secure = false,
    ) {
    }

    public static function fromGlobals(): self
    {
        $https = (string) ($_SERVER['HTTPS'] ?? '');

        return new self(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $_GET,
            $_POST,
            $_COOKIE,
            $https !== '' && strtolower($https) !== 'off',
        );
    }

    /*
    Czy żądanie przyszło przez HTTPS (flaga Secure ciasteczek).
    */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /*
    Wartość ciasteczka albo null, gdy go brak lub nie jest tekstem.
    */
    public function cookie(string $name): ?string
    {
        $value = $this->cookies[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /*
    Ścieżka bez query stringa. Nie używamy parse_url(), bo dla adresu
    "//auctions" traktuje on "auctions" jako host i gubi ścieżkę.
    Ukośniki nie są normalizowane: "/auctions/" i "//auctions" to inne ścieżki.
    */
    public function path(): string
    {
        $path = explode('?', $this->uri, 2)[0];

        return $path === '' ? '/' : $path;
    }

    public function query(string $name): string|array|null
    {
        return $this->query[$name] ?? null;
    }

    public function post(string $name): string|array|null
    {
        return $this->post[$name] ?? null;
    }

    /*
    Parametr tekstowy pasujący do wzorca (pełne wyrażenie regularne z ogranicznikami).
    */
    public function queryString(
        string $name,
        string $pattern,
        ?string $default = null,
    ): ?string {
        $value = $this->rawQueryValue($name);

        if ($value === null) {
            return $default;
        }

        if (preg_match($pattern, $value) !== 1) {
            throw HttpException::badRequest('Invalid query parameter: ' . $name);
        }

        return $value;
    }

    /*
    Parametr całkowity z zakresu min..max, bez zer wiodących i znaków innych niż cyfry.
    */
    public function queryInt(
        string $name,
        int $min,
        int $max,
        ?int $default = null,
    ): ?int {
        $value = $this->rawQueryValue($name);

        if ($value === null) {
            return $default;
        }

        if (preg_match('/\A(0|[1-9][0-9]{0,17})\z/', $value) !== 1) {
            throw HttpException::badRequest('Invalid query parameter: ' . $name);
        }

        $number = (int) $value;

        if ($number < $min || $number > $max) {
            throw HttpException::badRequest('Invalid query parameter: ' . $name);
        }

        return $number;
    }

    /*
    Parametr o wartości z białej listy (np. sort=ending).
    */
    public function queryOneOf(
        string $name,
        array $allowed,
        ?string $default = null,
    ): ?string {
        $value = $this->rawQueryValue($name);

        if ($value === null) {
            return $default;
        }

        if (!in_array($value, $allowed, true)) {
            throw HttpException::badRequest('Invalid query parameter: ' . $name);
        }

        return $value;
    }

    /*
    Zwraca tekst albo null dla parametru brakującego lub pustego.
    Tablica (?brand[]=a) jest błędnym formatem.
    */
    private function rawQueryValue(string $name): ?string
    {
        $value = $this->query[$name] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            throw HttpException::badRequest('Invalid query parameter: ' . $name);
        }

        return $value;
    }
}
