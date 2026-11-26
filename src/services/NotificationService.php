<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Notification;
use App\Model\NotificationItem;
use App\Repository\AuctionRepository;
use App\Repository\NotificationRepository;

/*
Powiadomienia zalogowanego użytkownika (docs/api.md 2.4): dwa zapytania niezależnie od liczby pozycji,
LIMIT najnowszych powiadomień (NotificationRepository) i podsumowania ich aukcji jednym zapytaniem
(AuctionRepository::findSummariesByIds), bez N+1. Oznaczenie jako przeczytane obejmuje wszystkie
powiadomienia użytkownika, także starsze niż LIST_LIMIT, i nigdy nie dotyka cudzych (warunek user_id w SQL).
*/
final class NotificationService
{
    public const LIST_LIMIT = 50;

    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly AuctionRepository $auctions,
    ) {
    }

    /**
     * @return list<NotificationItem> najnowsze pierwsze
     */
    public function forUser(int $userId): array
    {
        $rows = $this->notifications->findByUserId($userId, self::LIST_LIMIT);
        $summaries = $this->auctions->findSummariesByIds(
            array_values(array_unique(array_map(
                static fn (Notification $notification): int => $notification->getAuctionId(),
                $rows,
            ))),
        );

        $items = [];
        foreach ($rows as $notification) {
            $summary = $summaries[$notification->getAuctionId()] ?? null;
            if ($summary === null) {
                continue;
            }
            $items[] = new NotificationItem($notification, $summary);
        }

        return $items;
    }

    // Zwraca liczbę zmienionych powiadomień; ponowne wywołanie zwraca 0.
    public function markAllRead(int $userId): int
    {
        return $this->notifications->markAllRead($userId);
    }
}
