<?php

declare(strict_types=1);

namespace App\Model;

// Status aukcji (auctions.status).
enum AuctionStatus: string
{
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Ended = 'ended';
}
