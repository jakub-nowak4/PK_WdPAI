<?php

declare(strict_types=1);

/**
 * MAKIETA (T-009): dane przykladowe ofert kupujacego do podgladu ekranu 09.
 * To NIE sa dane z bazy. Kontroler docelowo przekaze $bids, a ten plik zostanie usuniety.
 * Kwoty w pelnych zlotych. status: leading | outbid | won | lost (docs/api.md).
 */

return [
    ['auctionId' => 1, 'title' => '2018 Ford Mustang GT 5.0 V8 Premium', 'vin' => '1FA6P8CF5J5100482', 'origin' => 'USA',
        'image' => null, 'status' => 'outbid', 'myBid' => 90000, 'price' => 92000, 'minIncrement' => 500, 'endsAtLabel' => '12.12.2026, 18:00', 'timeLeft' => '2d 4h 12m'],
    ['auctionId' => 2, 'title' => '2020 BMW M340i xDrive', 'vin' => 'WBA5R7C50LFH12345', 'origin' => 'Niemcy',
        'image' => null, 'status' => 'leading', 'myBid' => 142000, 'price' => 142000, 'minIncrement' => 1000, 'endsAtLabel' => '15.12.2026, 20:30', 'timeLeft' => '5d 6h 40m'],
    ['auctionId' => 3, 'title' => '2019 Audi Q5 Prestige', 'vin' => 'WA1BNAFY5K2054321', 'origin' => 'USA',
        'image' => null, 'status' => 'leading', 'myBid' => 98000, 'price' => 98000, 'minIncrement' => 500, 'endsAtLabel' => '20.12.2026, 12:00', 'timeLeft' => '9d 22h 5m'],
    ['auctionId' => 5, 'title' => '2019 Toyota Camry SE', 'vin' => '4T1B11HK5KU712345', 'origin' => 'USA',
        'image' => null, 'status' => 'won', 'myBid' => 95500, 'price' => 95500, 'minIncrement' => 500, 'endsAtLabel' => '24.03.2026, 18:30', 'timeLeft' => ''],
    ['auctionId' => 6, 'title' => '2017 Jeep Wrangler Rubicon', 'vin' => '1C4HJXFN5HW612345', 'origin' => 'USA',
        'image' => null, 'status' => 'lost', 'myBid' => 112000, 'price' => 118500, 'minIncrement' => 500, 'endsAtLabel' => '01.11.2026, 19:00', 'timeLeft' => ''],
];
