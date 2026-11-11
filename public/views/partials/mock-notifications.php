<?php

declare(strict_types=1);

/**
 * MAKIETA (T-009): przykladowe powiadomienia kupujacego do podgladu /notifications.
 * To NIE sa dane z bazy. Kontroler docelowo przekaze $notifications, a ten plik zostanie usuniety.
 * type: outbid | won | auction_ended (docs/api.md); unread = brak read_at.
 * Liczba nieprzeczytanych (2) zgadza sie z DemoUser::buyer()['unreadCount'].
 */

return [
    ['id' => 1, 'type' => 'outbid', 'auctionId' => 1, 'title' => '2018 Ford Mustang GT 5.0 V8 Premium',
        'dateLabel' => '11.11.2026, 13:42', 'unread' => true],
    ['id' => 2, 'type' => 'won', 'auctionId' => 5, 'title' => '2019 Toyota Camry SE',
        'dateLabel' => '10.11.2026, 18:30', 'unread' => true],
    ['id' => 3, 'type' => 'auction_ended', 'auctionId' => 6, 'title' => '2017 Jeep Wrangler Rubicon',
        'dateLabel' => '01.11.2026, 19:00', 'unread' => false],
];
