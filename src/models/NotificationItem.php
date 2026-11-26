<?php

declare(strict_types=1);

namespace App\Model;

/*
Wiersz ekranu "Powiadomienia" (docs/api.md 2.4): powiadomienie razem z podsumowaniem aukcji (tytuł, okładka, cena).
Niemutowalny, bez SQL.
*/
final class NotificationItem
{
    public function __construct(
        private readonly Notification $notification,
        private readonly AuctionSummary $summary,
    ) {
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }

    public function getSummary(): AuctionSummary
    {
        return $this->summary;
    }

    // Zwycięzca idzie na ekran wygranej, pozostałe typy na szczegóły aukcji.
    public function getLink(): string
    {
        $path = '/auctions/' . $this->notification->getAuctionId();

        return $this->notification->getType() === NotificationType::Won ? $path . '/won' : $path;
    }
}
