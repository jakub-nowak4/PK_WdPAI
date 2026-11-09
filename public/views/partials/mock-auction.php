<?php

declare(strict_types=1);

/**
 * MAKIETA (T-009): dane przykladowe jednej aukcji do podgladu ekranu 03.
 * To NIE sa dane z bazy. Kontroler docelowo przekaze $auction i $bids, a ten plik zostanie usuniety.
 * Kwoty w pelnych zlotych (kontrakt API podaje grosze: dzielenie po stronie kontrolera).
 */

return [
    'auction' => [
        'id' => 1, 'title' => '2018 Ford Mustang GT 5.0 V8 Premium', 'brand' => 'Ford', 'model' => 'Mustang GT',
        'vin' => '1FA6P8CF5J5100482', 'origin' => 'USA (Floryda)', 'condition' => 'Lekko uszkodzony',
        'status' => 'active', 'sellerId' => 900,
        'price' => 89500, 'minIncrement' => 500, 'minNextBid' => 90000, 'startPrice' => 62000, 'bidCount' => 14,
        'endsAt' => '2026-12-12T18:00:00+01:00', 'endsAtLabel' => '12.12.2026, 18:00',
        'serverTime' => '2026-12-10T13:41:28+01:00', 'secondsLeft' => 188912, 'timeLeft' => '2d 4h 28m 32s',
        'seller' => ['name' => 'AutoImport Kraków', 'initials' => 'AK', 'since' => '2026', 'finished' => 84],
        'specs' => [
            'year' => '2018', 'mileage' => '64 200 km', 'fuel' => 'Benzyna 5.0L',
            'gearbox' => 'Automat 10-biegowy', 'origin' => 'USA (Floryda)',
        ],
        'description' => [
            'Mustang GT w wersji Premium, sprowadzony z Florydy. Auto po lekkim uszkodzeniu przedniego zderzaka, bez szkody całkowitej.',
            'Silnik 5.0 V8 pracuje równo, skrzynia zmienia biegi bez szarpnięć. Dokument własności dostępny przy odbiorze.',
        ],
        'images' => [
            ['src' => null, 'alt' => 'Ford Mustang GT, widok z przodu', 'caption' => 'Przód'],
            ['src' => null, 'alt' => 'Ford Mustang GT, widok z tyłu', 'caption' => 'Tył'],
            ['src' => null, 'alt' => 'Ford Mustang GT, wnętrze', 'caption' => 'Wnętrze'],
        ],
        'viewerStatus' => 'none',
    ],
    'bids' => [
        ['bidder' => 'Licytujący 3', 'isYou' => false, 'amount' => 89500, 'diff' => 500, 'when' => '2 min temu'],
        ['bidder' => 'Licytujący 1', 'isYou' => false, 'amount' => 89000, 'diff' => 1000, 'when' => '14 min temu'],
        ['bidder' => 'Licytujący 2', 'isYou' => false, 'amount' => 88000, 'diff' => 500, 'when' => '1 godz. temu'],
    ],
];
