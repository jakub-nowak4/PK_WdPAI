<?php

declare(strict_types=1);

/*
Konfiguracja aplikacji odczytywana ze zmiennych środowiskowych. Wartości
trafiają do kontenera php z pliku .env przez docker-compose.yaml, więc żadne
sekrety nie są zapisane w repozytorium. Plik zwraca tablicę i jest wczytywany
raz w public/index.php (oraz w skryptach z bin/).
*/

$env = static function (string $name, string $default = ''): string {
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
};

return [
    'app' => [
        'env' => $env('APP_ENV', 'production'),
        'url' => $env('APP_URL', 'http://localhost:8080'),
    ],
    'database' => [
        'host' => $env('DB_HOST', 'db'),
        'port' => (int) $env('DB_PORT', '5432'),
        'name' => $env('POSTGRES_DB', 'app'),
        'user' => $env('POSTGRES_USER', 'app'),
        'password' => $env('POSTGRES_PASSWORD'),
    ],
];
