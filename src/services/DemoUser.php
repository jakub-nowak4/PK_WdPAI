<?php

declare(strict_types=1);

namespace App\Service;

/**
 * MAKIETA: TYMCZASOWY zalogowany uzytkownik do prezentacji ekranow.
 *
 * Do czasu AuthService z karty auth widoki dostaja stale dane demo zamiast
 * uzytkownika z sesji. Klasa ma zniknac razem z wywolaniami
 * AppController::renderAsUser(), gdy kontrolery zaczna brac uzytkownika
 * z sesji. Zadnych danych z bazy ani prawdziwych danych osobowych.
 *
 * Klucze tablicy odpowiadaja temu, czego oczekuje public/views/layout.html.php:
 * id, name, initials, role (BUYER|DEALER|ADMIN), unreadCount, approved (DEALER).
 */
final class DemoUser
{
    private function __construct()
    {
    }

    /** @return array{id:int,name:string,initials:string,role:string,unreadCount:int,approved:bool} */
    public static function buyer(): array
    {
        return [
            'id' => 1,
            'name' => 'Jan Kowalski',
            'initials' => 'JK',
            'role' => 'BUYER',
            'unreadCount' => 2,
            'approved' => true,
        ];
    }

    /** @return array{id:int,name:string,initials:string,role:string,unreadCount:int,approved:bool} */
    public static function approvedCompany(): array
    {
        return [
            'id' => 2,
            'name' => 'AutoImport Kraków',
            'initials' => 'AK',
            'role' => 'DEALER',
            'unreadCount' => 0,
            'approved' => true,
        ];
    }

    /** @return array{id:int,name:string,initials:string,role:string,unreadCount:int,approved:bool} */
    public static function pendingCompany(): array
    {
        return [
            'id' => 3,
            'name' => 'AutoImport Kraków',
            'initials' => 'AK',
            'role' => 'DEALER',
            'unreadCount' => 0,
            'approved' => false,
        ];
    }
}
