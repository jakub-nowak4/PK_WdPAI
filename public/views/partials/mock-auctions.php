<?php

declare(strict_types=1);

/**
 * MAKIETA (T-009): statyczne dane przykladowe do podgladu widokow.
 * To NIE sa dane z bazy. Kontroler docelowo przekaze prawdziwe dane
 * w zmiennych $auctions, $brands, $filters, $page, $pages, $total, a ten plik zostanie usuniety.
 */

return [
    'brands' => [
        'audi' => 'Audi',
        'bmw' => 'BMW',
        'dodge' => 'Dodge',
        'ford' => 'Ford',
        'jeep' => 'Jeep',
        'porsche' => 'Porsche',
    ],
    'total' => 328,
    'page' => 1,
    'pages' => 28,
    'filters' => ['q' => '', 'brand' => '', 'priceMax' => '', 'sort' => 'ending'],
    'auctions' => [
        [
            'id' => 1, 'title' => '2018 Ford Mustang GT 5.0 V8', 'seller' => 'AutoImport Kraków',
            'origin' => 'USA (Floryda)', 'condition' => 'Lekko uszkodzony', 'image' => null,
            'mileage' => '64 200 km', 'gearbox' => 'Automat 10-bieg.', 'fuel' => 'Benzyna 5.0L', 'drive' => 'RWD (tylny)',
            'price' => 89500, 'bids' => 14, 'timeLeft' => '2d 4h', 'urgent' => false, 'status' => 'active',
        ],
        [
            'id' => 2, 'title' => '2020 BMW 530d xDrive M Sport', 'seller' => 'Bavaria Motors Poznań',
            'origin' => 'Niemcy (Monachium)', 'condition' => 'Bez uszkodzeń', 'image' => null,
            'mileage' => '112 000 km', 'gearbox' => 'Automat Steptronic', 'fuel' => 'Diesel 3.0L', 'drive' => 'xDrive (AWD)',
            'price' => 134000, 'bids' => 21, 'timeLeft' => '0h 48m', 'urgent' => true, 'status' => 'active',
        ],
        [
            'id' => 3, 'title' => '2019 Dodge Challenger R/T Scat Pack', 'seller' => 'GlobalCars Warszawa',
            'origin' => 'USA (Teksas)', 'condition' => 'Lekko uszkodzony', 'image' => null,
            'mileage' => '48 900 km', 'gearbox' => 'TorqueFlite', 'fuel' => '6.4L HEMI V8', 'drive' => 'RWD',
            'price' => 115800, 'bids' => 9, 'timeLeft' => '1d 12h', 'urgent' => false, 'status' => 'active',
        ],
        [
            'id' => 4, 'title' => '2021 Audi A6 Avant 40 TDI S line', 'seller' => 'EuroTrans Wrocław',
            'origin' => 'Belgia (Antwerpia)', 'condition' => 'Bez uszkodzeń', 'image' => null,
            'mileage' => '89 400 km', 'gearbox' => 'S tronic 7-bieg.', 'fuel' => 'Diesel MHEV', 'drive' => 'FWD / Quattro',
            'price' => 142500, 'bids' => 17, 'timeLeft' => '3d 7h', 'urgent' => false, 'status' => 'active',
        ],
        [
            'id' => 5, 'title' => '2019 Porsche Macan GTS 2.9 Biturbo', 'seller' => 'WestCoast Auto Gdańsk',
            'origin' => 'Niemcy (Frankfurt)', 'condition' => 'Bez uszkodzeń', 'image' => null,
            'mileage' => '73 000 km', 'gearbox' => 'PDK 7-biegowy', 'fuel' => 'Benzyna 2.9 Biturbo', 'drive' => 'AWD',
            'price' => 219000, 'bids' => 28, 'timeLeft' => '5h 42m', 'urgent' => false, 'status' => 'active',
        ],
        [
            'id' => 6, 'title' => '2020 Jeep Grand Cherokee 5.7 V8', 'seller' => 'V8 Classic Katowice',
            'origin' => 'USA (Ohio)', 'condition' => 'Uszkodzenie gradowe', 'image' => null,
            'mileage' => '81 300 km', 'gearbox' => 'Automat 8HP70', 'fuel' => '5.7L HEMI V8', 'drive' => '4x4 Quadra-Drive',
            'price' => 96000, 'bids' => 11, 'timeLeft' => '2d 19h', 'urgent' => false, 'status' => 'active',
        ],
    ],
];
