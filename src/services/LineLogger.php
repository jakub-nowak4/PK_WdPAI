<?php

declare(strict_types=1);

namespace App\Service;

/*
Log skryptów CLI (CRON): jedna linia = jedno zdarzenie, z czasem ISO 8601 na początku. Domyślnie pisze na standardowe
wyjście, które w kontenerze `cron` trafia do `docker compose logs cron`. Strumień jest wstrzykiwany, więc test może
podać php://memory. Nie loguje danych osobowych: wołający podaje tylko id i liczby.
*/
final class LineLogger
{
    /** @var resource */
    private $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct($stream = null)
    {
        $this->stream = $stream ?? STDOUT;
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    private function write(string $level, string $message): void
    {
        // Wyjątek z bazy może zawierać łamanie linii; log ma zostać jedną linią.
        $line = preg_replace('/\s+/', ' ', $message);

        fwrite($this->stream, sprintf("[%s] %s %s\n", (new \DateTimeImmutable())->format(DATE_ATOM), $level, $line));
    }
}
