<?php

declare(strict_types=1);

namespace App\Model;

// Biała lista sortowań listy aukcji: wartości to dokładnie parametr ?sort= z docs/api.md 2.2.
// Serwis listy zamienia tekst z URL przez AuctionSort::tryFrom() (null = zły format, 400); repozytorium zamienia
// wariant na stały fragment ORDER BY, więc wartość od użytkownika nigdy nie trafia do SQL.
enum AuctionSort: string
{
    case Ending = 'ending';
    case Newest = 'newest';
    case PriceAsc = 'price_asc';
    case PriceDesc = 'price_desc';
}
