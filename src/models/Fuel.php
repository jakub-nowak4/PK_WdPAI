<?php

declare(strict_types=1);

namespace App\Model;

// Rodzaj paliwa (auctions.fuel).
enum Fuel: string
{
    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Hybrid = 'hybrid';
    case Electric = 'electric';
    case Lpg = 'lpg';
}
