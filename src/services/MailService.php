<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MailException;

/*
Własny klient SMTP (bez zewnętrznych bibliotek, jak w laboratorium Mailpit).
Rozmowa z serwerem: powitanie 220, EHLO, MAIL FROM, RCPT TO, DATA (354), treść
zakończona linią z kropką (250), QUIT. Po każdym poleceniu sprawdzamy kod odpowiedzi
(także wieloliniowej); inny niż oczekiwany to MailException. Połączenie ma limit czasu.

Bezpieczeństwo listu: adresy muszą przejść filtr e-mail (bez CR/LF), nazwy i temat są
kodowane jako =?UTF-8?B?...?= (CR/LF w nich nie da się wstrzyknąć jako nowego nagłówka),
treść idzie jako quoted-printable z "dot-stuffingiem" (linia zaczynająca się od kropki
dostaje drugą kropkę, żeby nie zakończyła DATA). Komunikaty wyjątków zawierają etap i
kod odpowiedzi, bez adresów i treści, bo trafiają do logu.

Limity czasu: samo połączenie ma krótszy limit (CONNECT_TIMEOUT_SECONDS), żeby niedostępny
serwer poczty nie wstrzymywał żądania (rejestracja wysyła list w trakcie żądania, bo użytkownik
ma zobaczyć komunikat o błędzie poczty); odpowiedzi serwera mają dłuższy limit.
*/
final class MailService implements MailerInterface
{
    private const CRLF = "\r\n";
    // Najdłuższy kawałek tematu/nazwy przed kodowaniem: 45 B -> 60 znaków base64 + 12 znaków otoczki < 76.
    private const ENCODED_WORD_BYTES = 45;
    private const HELO_NAME = 'localhost';
    private const CONNECT_TIMEOUT_SECONDS = 2.0;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $fromEmail,
        private readonly string $fromName,
        private readonly float $responseTimeoutSeconds = 5.0,
    ) {
    }

    public function send(string $toEmail, string $toName, string $subject, string $textBody): void
    {
        $this->assertAddress($this->fromEmail);
        $this->assertAddress($toEmail);

        $message = $this->buildMessage($toEmail, $toName, $subject, $textBody);

        $errorNumber = 0;
        $errorMessage = '';
        $socket = @fsockopen($this->host, $this->port, $errorNumber, $errorMessage, min(self::CONNECT_TIMEOUT_SECONDS, $this->responseTimeoutSeconds));

        if ($socket === false) {
            throw new MailException(sprintf('SMTP connect failed (errno %d)', $errorNumber));
        }

        try {
            stream_set_timeout($socket, (int) ceil($this->responseTimeoutSeconds));

            $this->expect($socket, [220], 'greeting');
            $this->command($socket, 'EHLO ' . self::HELO_NAME, [250], 'EHLO');
            $this->command($socket, 'MAIL FROM:<' . $this->fromEmail . '>', [250], 'MAIL FROM');
            $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251], 'RCPT TO');
            $this->command($socket, 'DATA', [354], 'DATA');
            $this->write($socket, $this->dotStuff($message) . self::CRLF . '.' . self::CRLF);
            $this->expect($socket, [250], 'message body');

            // Po przyjęciu listu błąd QUIT niczego nie psuje.
            @fwrite($socket, 'QUIT' . self::CRLF);
        } finally {
            fclose($socket);
        }
    }

    // Adres nadawcy i odbiorcy trafia do poleceń SMTP: CR, LF, spacje i "<>" musiałyby się przebić przez filtr.
    private function assertAddress(string $address): void
    {
        if (strlen($address) > 254 || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            throw new MailException('Invalid e-mail address');
        }
    }

    private function buildMessage(string $toEmail, string $toName, string $subject, string $textBody): string
    {
        $domain = substr(strrchr($this->fromEmail, '@') ?: '@localhost', 1);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->formatAddress($this->fromEmail, $this->fromName),
            'To: ' . $this->formatAddress($toEmail, $toName),
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
        ];

        $body = preg_replace('/\r\n|\r|\n/', self::CRLF, $textBody) ?? $textBody;

        return implode(self::CRLF, $headers) . self::CRLF . self::CRLF . quoted_printable_encode($body);
    }

    private function formatAddress(string $email, string $name): string
    {
        $name = trim($name);

        return $name === '' ? '<' . $email . '>' : $this->encodeHeader($name) . ' <' . $email . '>';
    }

    /*
    Tekst nagłówka jako ciąg "encoded-words" RFC 2047 (UTF-8, base64). Znaki sterujące
    (w tym CR i LF) zamieniamy na spacje, a całość kodujemy zawsze, więc wartość z
    formularza nie może dołożyć własnego nagłówka. Długie wartości dzielimy na kawałki
    po granicy znaku UTF-8, każdy kawałek to osobne słowo (limit 75 znaków w linii).
    */
    private function encodeHeader(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value) ?? '';
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $words = [];
        $chunk = '';

        foreach (mb_str_split($value, 1, 'UTF-8') as $character) {
            if ($chunk !== '' && strlen($chunk) + strlen($character) > self::ENCODED_WORD_BYTES) {
                $words[] = $chunk;
                $chunk = '';
            }

            $chunk .= $character;
        }

        $words[] = $chunk;

        return implode(self::CRLF . ' ', array_map(
            static fn (string $word): string => '=?UTF-8?B?' . base64_encode($word) . '?=',
            $words,
        ));
    }

    // Linia zaczynająca się od kropki dostaje drugą (RFC 5321, 4.5.2); serwer ją usuwa.
    private function dotStuff(string $message): string
    {
        return preg_replace('/^\./m', '..', $message) ?? $message;
    }

    /**
     * @param resource $socket
     * @param list<int> $expectedCodes
     */
    private function command($socket, string $line, array $expectedCodes, string $stage): void
    {
        $this->write($socket, $line . self::CRLF);
        $this->expect($socket, $expectedCodes, $stage);
    }

    /**
     * @param resource $socket
     */
    private function write($socket, string $data): void
    {
        $written = 0;
        $length = strlen($data);

        while ($written < $length) {
            $result = @fwrite($socket, substr($data, $written));

            if ($result === false || $result === 0) {
                throw new MailException('SMTP write failed');
            }

            $written += $result;
        }
    }

    /*
    Czyta odpowiedź (linie "250-..." trwają, ostatnia ma "250 ...") i porównuje kod.
    Tekst odpowiedzi serwera pomijamy w wyjątku: mógłby zawierać adres odbiorcy.
    */
    /**
     * @param resource $socket
     * @param list<int> $expectedCodes
     */
    private function expect($socket, array $expectedCodes, string $stage): void
    {
        $code = 0;

        do {
            $line = fgets($socket, 1024);

            if ($line === false) {
                $timedOut = (bool) (stream_get_meta_data($socket)['timed_out'] ?? false);

                throw new MailException(sprintf('SMTP %s: %s', $stage, $timedOut ? 'timeout' : 'connection closed'));
            }

            if (preg_match('/^(\d{3})([ -])/', $line, $matches) !== 1) {
                throw new MailException(sprintf('SMTP %s: malformed response', $stage));
            }

            $code = (int) $matches[1];
        } while ($matches[2] === '-');

        if (!in_array($code, $expectedCodes, true)) {
            throw new MailException(sprintf('SMTP %s: unexpected response code %d', $stage, $code));
        }
    }
}
