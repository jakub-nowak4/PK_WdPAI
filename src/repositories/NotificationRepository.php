<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Notification;
use App\Model\NotificationType;
use DateTimeImmutable;

// Powiadomienia w serwisie (tabela notifications). Cały SQL dla powiadomień jest tutaj; kto je tworzy i kiedy,
// ustalają serwisy: outbid BidService (w transakcji oferty), won i auction_ended CRON (docs/api.md 2.4).
final class NotificationRepository extends BaseRepository
{
    private const COLUMNS = 'id, user_id, auction_id, type, created_at, read_at';

    // Brak użytkownika lub aukcji kończy się PDOException z SQLSTATE 23503. Powtórzone 'won' / 'auction_ended'
    // dla tej samej osoby i aukcji daje 23505 (notifications_once_uq); CRON używa więc createOnce().
    public function create(int $userId, int $auctionId, NotificationType $type): Notification
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, auction_id, type)
             VALUES (:user_id, :auction_id, :type)
             RETURNING ' . self::COLUMNS
        );
        $statement->execute(['user_id' => $userId, 'auction_id' => $auctionId, 'type' => $type->value]);

        return $this->mapRow($statement->fetch());
    }

    // Wersja idempotentna dla CRON (docs/erd.md): ON CONFLICT DO NOTHING nie przerywa transakcji PostgreSQL
    // (23505 by ją przerwał) i zwraca null, gdy takie powiadomienie już istnieje (notifications_once_uq).
    public function createOnce(int $userId, int $auctionId, NotificationType $type): ?Notification
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, auction_id, type)
             VALUES (:user_id, :auction_id, :type)
             ON CONFLICT DO NOTHING
             RETURNING ' . self::COLUMNS
        );
        $statement->execute(['user_id' => $userId, 'auction_id' => $auctionId, 'type' => $type->value]);
        $row = $statement->fetch();

        return $row === false ? null : $this->mapRow($row);
    }

    // Lista użytkownika, najnowsze pierwsze (notifications_user_idx); id rozstrzyga równe created_at.
    // Kontrakt (api.md 2.4) nie przewiduje paginacji, więc bez limitu.
    /**
     * @return list<Notification>
     */
    public function findByUserId(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT ' . self::COLUMNS . ' FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return array_map($this->mapRow(...), $statement->fetchAll());
    }

    // Licznik w nawigacji i viewer.unreadNotifications (notifications_unread_idx).
    public function countUnread(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL'
        );
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    // POST /notifications/read: wszystkie nieprzeczytane tego użytkownika; cudzych nie dotyka (warunek user_id).
    // Zwraca liczbę zmienionych wierszy. Ponowne wywołanie nic nie zmienia (read_at IS NULL).
    public function markAllRead(int $userId): int
    {
        $statement = $this->pdo->prepare(
            'UPDATE notifications SET read_at = now() WHERE user_id = :user_id AND read_at IS NULL'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->rowCount();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRow(array $row): Notification
    {
        return new Notification(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['auction_id'],
            NotificationType::from((string) $row['type']),
            new DateTimeImmutable((string) $row['created_at']),
            $row['read_at'] === null ? null : new DateTimeImmutable((string) $row['read_at']),
        );
    }
}
