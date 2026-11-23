<?php

declare(strict_types=1);

namespace App\Model;

// Status aukcji z punktu widzenia licytującego (ekran "Moje oferty"). Wartości odpowiadają query ?status=;
// "all" to brak filtra, więc nie jest wariantem.
enum MyBidStatus: string
{
    case Leading = 'leading';
    case Outbid = 'outbid';
    case Won = 'won';
    case Lost = 'lost';
}
