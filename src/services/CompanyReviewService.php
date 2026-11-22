<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\HttpException;
use App\Exception\ValidationException;
use App\Model\Company;
use App\Model\CompanyApplication;
use App\Model\CompanyStatus;
use App\Repository\AuctionRepository;
use App\Repository\CompanyRepository;

/*
Panel administratora (docs/api.md 2.6): lista firm z filtrem statusu, kafelki statystyk oraz
zatwierdzenie i odrzucenie firmy. Decyzja jest jednym UPDATE w transakcji z warunkiem status = 'pending'
(w CompanyRepository), więc dwóch administratorów nie rozpatrzy tej samej firmy dwa razy.
*/
final class CompanyReviewService
{
    // $pdo to to samo połączenie, na którym działają repozytoria: transakcję prowadzi serwis (docs/api.md, 1.8).
    public function __construct(
        private readonly CompanyRepository $companies,
        private readonly AuctionRepository $auctions,
        private readonly \PDO $pdo,
        private readonly Translator $translator,
    ) {
    }

    /**
     * @param string $status pending|approved|rejected|all (walidację query robi Request::queryOneOf)
     * @return list<CompanyApplication>
     */
    public function applications(string $status): array
    {
        return $this->companies->findApplications($status === 'all' ? null : CompanyStatus::from($status));
    }

    /**
     * @return array{pending: int, approved: int, auctions: int}
     */
    public function stats(): array
    {
        $counts = $this->companies->countByStatus();

        return [
            'pending' => $counts['pending'],
            'approved' => $counts['approved'],
            'auctions' => $this->auctions->countAll(),
        ];
    }

    /**
     * @throws HttpException 404, gdy firma nie istnieje
     * @throws ValidationException 409 (błąd przy polu `company`), gdy firma nie jest już pending
     */
    public function approve(int $companyId, int $adminId): Company
    {
        return $this->decide($companyId, CompanyStatus::Approved, $adminId);
    }

    /**
     * @throws HttpException 404, gdy firma nie istnieje
     * @throws ValidationException 409 (błąd przy polu `company`), gdy firma nie jest już pending
     */
    public function reject(int $companyId, int $adminId): Company
    {
        return $this->decide($companyId, CompanyStatus::Rejected, $adminId);
    }

    private function decide(int $companyId, CompanyStatus $status, int $adminId): Company
    {
        $this->pdo->beginTransaction();

        try {
            $company = $this->companies->updateStatus($companyId, $status, $adminId);

            if ($company === null) {
                // UPDATE nic nie zmienił: albo firmy nie ma (404), albo nie była pending (409).
                $exists = $this->companies->findById($companyId) !== null;
                $this->pdo->rollBack();

                if (!$exists) {
                    throw HttpException::notFound('Company not found');
                }

                throw new ValidationException(
                    ['company' => $this->translator->translate('admin.error_not_pending')],
                    409
                );
            }

            $this->pdo->commit();

            return $company;
        } catch (HttpException | ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
