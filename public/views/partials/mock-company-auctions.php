<?php

declare(strict_types=1);

/**
 * MAKIETA (T-009): dane przykladowe aukcji firmy do podgladu ekranu 14.
 * To NIE sa dane z bazy. Kontroler docelowo przekaze $auctions, a ten plik zostanie usuniety.
 * Kwoty w pelnych zlotych. canDelete = brak ofert (bidCount === 0).
 */

return [
    'company' => ['name' => 'AutoImport Kraków', 'city' => 'Kraków'],
    'auctions' => [
        ['id' => 1, 'title' => '2018 Ford Mustang GT 5.0 V8 Premium', 'image' => null, 'status' => 'active',
            'price' => 89500, 'minIncrement' => 500, 'bidCount' => 14, 'endsAtLabel' => '12.12.2026, 18:00', 'canDelete' => false],
        ['id' => 2, 'title' => '2020 BMW M340i xDrive', 'image' => null, 'status' => 'active',
            'price' => 142000, 'minIncrement' => 1000, 'bidCount' => 0, 'endsAtLabel' => '15.12.2026, 20:30', 'canDelete' => true],
        ['id' => 3, 'title' => '2019 Audi Q5 Prestige', 'image' => null, 'status' => 'scheduled',
            'price' => 98000, 'minIncrement' => 500, 'bidCount' => 0, 'endsAtLabel' => '20.12.2026, 12:00', 'canDelete' => true],
        ['id' => 4, 'title' => '2017 Jeep Wrangler Rubicon', 'image' => null, 'status' => 'ended',
            'price' => 118500, 'minIncrement' => 500, 'bidCount' => 9, 'endsAtLabel' => '01.11.2026, 19:00', 'canDelete' => false],
    ],
];
