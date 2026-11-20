<?php

declare(strict_types=1);

namespace App\Model;

// Typ powiadomienia w serwisie (notifications.type).
enum NotificationType: string
{
    case Outbid = 'outbid';
    case Won = 'won';
    case AuctionEnded = 'auction_ended';
}
