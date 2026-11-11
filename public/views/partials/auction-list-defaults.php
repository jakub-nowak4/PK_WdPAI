<?php
/**
 * Domyslne dane listy aukcji. MAKIETA (T-009): gdy kontroler nie przekazal $auctions,
 * bierze przykladowe dane z mock-auctions.php; przekazane $filters (query) nadpisuja filtry makiety.
 * Ustawia: $auctions, $brands, $filters, $page, $pages, $total, $isMock.
 */
$isMock = false;
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
