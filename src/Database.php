<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

/*
Połączenie PDO z PostgreSQL. Obiekt tworzony raz w public/index.php i
wstrzykiwany do repozytoriów. Połączenie otwiera się leniwie przy pierwszym
użyciu. Szczegóły błędu (mogą zawierać host lub użytkownika) trafiają tylko do
logu, a wyjątek wyżej niesie ogólny komunikat bez danych dostępowych.
*/
final class Database
{
    private ?PDO $connection = null;

    /**
     * @param array{host: string, port: int, name: string, user: string, password: string} $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = $this->connect();
        }

        return $this->connection;
    }

    private function connect(): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['name'],
        );

        try {
            return new PDO($dsn, $this->config['user'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            error_log('Database connection failed: ' . $exception->getMessage());

            throw new RuntimeException('Database connection failed.');
        }
    }
}
