<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Role;
use App\Repository\CompanyRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;

/*
Dane zalogowanego użytkownika dla layoutu (nagłówek, nawigacja, dzwonek). Sesja trzyma
tylko id i rolę (docs/api.md, 1.5), więc nazwę, status firmy i licznik powiadomień
czytamy z bazy przy każdym renderze: decyzja administratora działa od razu, bez
ponownego logowania. Połączenie z bazą otwiera się dopiero przy pierwszym zapytaniu,
więc żądanie gościa nie dotyka bazy.
*/
final class CurrentUserProvider
{
    private ?UserRepository $users = null;
    private ?CompanyRepository $companies = null;
    private ?NotificationRepository $notifications = null;

    /**
     * @param \Closure(): \PDO $connection fabryka połączenia (Database::getConnection(...)), wywoływana leniwie
     */
    public function __construct(private readonly \Closure $connection)
    {
    }

    /**
     * Tablica dla layoutu (`$currentUser`): id, name, initials, role, unreadCount, approved.
     * Dla firmy name to nazwa firmy, a approved to status zatwierdzenia; dla pozostałych
     * ról approved jest true. Zwraca null, gdy konta już nie ma w bazie (sesja jest nieaktualna).
     *
     * @return array{id: int, name: string, initials: string, role: string, unreadCount: int, approved: bool}|null
     */
    public function find(int $userId): ?array
    {
        $pdo = ($this->connection)();
        $this->users ??= new UserRepository($pdo);
        $this->companies ??= new CompanyRepository($pdo);
        $this->notifications ??= new NotificationRepository($pdo);

        $user = $this->users->findById($userId);

        if ($user === null) {
            return null;
        }

        $name = $user->getName();
        $approved = true;

        if ($user->getRole() === Role::Dealer) {
            $company = $this->companies->findByUserId($userId);
            $name = $company?->getName() ?? $name;
            $approved = $company?->isApproved() ?? false;
        }

        return [
            'id' => $userId,
            'name' => $name,
            'initials' => $this->initials($name),
            'role' => $user->getRole()->value,
            'unreadCount' => $this->notifications->countUnread($userId),
            'approved' => $approved,
        ];
    }

    // Pierwsze litery pierwszych dwóch słów nazwy ("Anna Przykładowa" -> "AP").
    private function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = array_map(static fn (string $word): string => mb_substr($word, 0, 1), array_slice($words, 0, 2));

        return mb_strtoupper(implode('', $letters));
    }
}
