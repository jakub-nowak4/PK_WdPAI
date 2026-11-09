<?php

declare(strict_types=1);

/**
 * MAKIETA (T-009): dane przykladowe firm do podgladu ekranu 17 (panel administratora).
 * To NIE sa dane z bazy. Kontroler docelowo przekaze $companies i $stats, a ten plik zostanie usuniety.
 * status: pending | approved | rejected.
 */

return [
    'stats' => ['pending' => 3, 'approved' => 142, 'auctions' => 328],
    'companies' => [
        ['id' => 11, 'name' => 'AutoImport Kraków', 'city' => 'Kraków', 'email' => 'kontakt@autoimport.example', 'registeredAtLabel' => '08.11.2026, 09:14', 'status' => 'pending'],
        ['id' => 12, 'name' => 'Moto Hurt Gdańsk', 'city' => 'Gdańsk', 'email' => 'biuro@motohurt.example', 'registeredAtLabel' => '07.11.2026, 16:40', 'status' => 'pending'],
        ['id' => 13, 'name' => 'Premium Cars Poznań', 'city' => 'Poznań', 'email' => 'info@premiumcars.example', 'registeredAtLabel' => '05.11.2026, 11:02', 'status' => 'approved'],
        ['id' => 14, 'name' => 'Szybki Transport Łódź', 'city' => 'Łódź', 'email' => 'zgloszenia@szybki.example', 'registeredAtLabel' => '02.11.2026, 13:25', 'status' => 'rejected'],
    ],
];
