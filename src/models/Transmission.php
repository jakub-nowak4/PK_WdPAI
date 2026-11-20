<?php

declare(strict_types=1);

namespace App\Model;

// Skrzynia biegów (auctions.transmission).
enum Transmission: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';
}
