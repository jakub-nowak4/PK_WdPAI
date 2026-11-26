<?php
/**
 * Zmienne bloku listy aukcji (docs/api.md 1.8 i 2.2).
 * Z kontrolera (T-028): $auctionCards (list<AuctionSummary>), $brandOptions (list<Brand>),
 * $filters (q, brand, priceMax, sort), $page, $pages, $total; opcjonalnie $perPage (domyslnie 12) i $now.
 * Gdy kontroler nie przekazal $auctionCards, uzywana jest MAKIETA z mock-auctions.php (T-009)
 * i ustawiane jest $isMock.
 * Ustawia: $auctions, $brands, $filters, $page, $pages, $total, $perPage, $isMock.
 */
$isMock = false;
// Nazwy zmiennych z AuctionController/DefaultController (T-028): $auctionCards i $brandOptions.
if (isset($auctionCards)) {
    $auctions = $auctionCards;
    $brands = $brandOptions ?? [];
}
if (!isset($auctions)) {
    $mock = require __DIR__ . '/mock-auctions.php';
    $auctions = $mock['auctions'];
    $brands = $mock['brands'];
    $filters = array_merge($mock['filters'], $filters ?? []);
    $page = $page ?? $mock['page'];
    $pages = $mock['pages'];
    $total = $mock['total'];
    $isMock = true;
}

// Marki jako slug => nazwa (kontroler moze podac tez liste obiektow Brand).
$brandOptions = [];
foreach ($brands ?? [] as $slug => $brand) {
    if ($brand instanceof \App\Model\Brand) {
        $brandOptions[$brand->getSlug()] = $brand->getName();
    } else {
        $brandOptions[(string) $slug] = (string) $brand;
    }
}
$brands = $brandOptions;

$filters = array_merge(['q' => '', 'brand' => '', 'priceMax' => '', 'sort' => 'ending'], $filters ?? []);
$total = (int) ($total ?? count($auctions));
$perPage = (int) ($perPage ?? 12);
$pages = max(1, (int) ($pages ?? ceil($total / $perPage)));
$page = max(1, (int) ($page ?? 1));
