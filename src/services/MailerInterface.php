<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\MailException;

/*
Wysyłka jednej wiadomości tekstowej. Interfejs pozwala podmienić transport
(SMTP w MailService, atrapa w testach) bez zmian w serwisach, które wysyłają listy.
*/
interface MailerInterface
{
    /**
     * @throws MailException gdy wiadomości nie udało się przekazać serwerowi pocztowemu
     */
    public function send(string $toEmail, string $toName, string $subject, string $textBody): void;
}
