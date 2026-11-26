<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\HttpException;
use App\Exception\ValidationException;
use App\Model\Auction;
use App\Model\Company;
use App\Repository\AuctionImageRepository;
use App\Repository\AuctionRepository;

/*
Dodanie aukcji przez firmę (docs/api.md 2.5, kryteria 22 i 34). Kolejność:
1. walidacja pól (AuctionFormValidator, 422 przy błędzie; pliki jeszcze nietknięte),
2. zapis plików przez UploadService (błąd pliku = 422, już zapisane pliki są usuwane),
3. jedna transakcja: wiersz aukcji i wiersze zdjęć (position 1..8 w kolejności przesłania, 1 = okładka).
Gdy zapis w bazie się nie uda, transakcja jest wycofywana, a zapisane pliki usuwane, więc nie zostają
osierocone pliki ani aukcja bez zdjęć. Właścicielem jest zawsze firma z sesji, nigdy wartość z formularza.
*/
final class AuctionCreationService
{
    // $pdo to to samo połączenie, na którym działają repozytoria: transakcję prowadzi serwis (docs/api.md 1.8).
    public function __construct(
        private readonly AuctionFormValidator $validator,
        private readonly AuctionRepository $auctions,
        private readonly AuctionImageRepository $images,
        private readonly UploadService $uploads,
        private readonly \PDO $pdo,
    ) {
    }

    /**
     * @param array<string, mixed> $input surowe pola tekstowe formularza
     * @param list<array<string, mixed>> $files pliki po UploadService::normalizeFiles()
     * @throws ValidationException 422 (pola albo zdjęcia)
     * @throws HttpException 403, gdy firma nie jest zatwierdzona
     */
    public function create(Company $company, array $input, array $files): Auction
    {
        if (!$company->isApproved()) {
            throw HttpException::forbidden();
        }

        // Czas serwera bazy (reguła 3), nie przeglądarki: do reguł "nie w przeszłości" i długości aukcji.
        $new = $this->validator->validate($input, $company->getId(), count($files), $this->auctions->currentTime());
        $paths = $this->uploads->storeAll($files);

        $this->pdo->beginTransaction();

        try {
            $auction = $this->auctions->create($new);
            foreach ($paths as $index => $path) {
                $this->images->create($auction->getId(), $path, $index + 1);
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->uploads->deleteAll($paths);

            throw $exception;
        }

        return $auction;
    }
}
