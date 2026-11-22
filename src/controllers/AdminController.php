<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ValidationException;
use App\Model\CompanyApplication;
use App\Model\Role;
use App\Request;
use App\Service\CompanyReviewService;
use App\Service\CurrentUserProvider;
use App\Service\Translator;

final class AdminController extends AppController
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'all'];

    public function __construct(
        Translator $translator,
        CurrentUserProvider $currentUsers,
        private readonly CompanyReviewService $review,
    ) {
        parent::__construct($translator, $currentUsers);
    }

    /*
    GET /admin?status=pending|approved|rejected|all (docs/api.md, 2.6). Tylko ADMIN
    (gość 401 -> 303 /login, inna rola 403), zła wartość ?status= to 400.
    */
    public function index(Request $request): void
    {
        $this->requireRole(Role::Admin);

        $status = (string) $request->queryOneOf('status', self::STATUSES, 'pending');

        $this->renderPanel($status);
    }

    /*
    POST /admin/companies/{id}/approve. Kolejność: 401 -> 403 -> CSRF (403) -> 404 / 409 -> 303 /admin.
    */
    public function approve(int $id, Request $request): void
    {
        $this->decide($id, $request, true);
    }

    // POST /admin/companies/{id}/reject, jak approve().
    public function reject(int $id, Request $request): void
    {
        $this->decide($id, $request, false);
    }

    private function decide(int $id, Request $request, bool $approve): void
    {
        $adminId = $this->requireRole(Role::Admin);
        $this->verifyCsrf($request);

        try {
            if ($approve) {
                $this->review->approve($id, $adminId);
            } else {
                $this->review->reject($id, $adminId);
            }
        } catch (ValidationException $exception) {
            // 409: firma została już rozpatrzona. Panel wraca z komunikatem, bez zmiany w bazie.
            http_response_code($exception->statusCode());
            $this->renderPanel('pending', $exception->errors()['company'] ?? '');

            return;
        }

        $this->addFlash('success', $approve ? 'admin.flash_approved' : 'admin.flash_rejected');
        $this->redirect('/admin');
    }

    private function renderPanel(string $status, string $formError = ''): void
    {
        $this->render('admin/index', [
            'pageTitle' => 'Panel administratora',
            'status' => $status,
            'companies' => array_map($this->toRow(...), $this->review->applications($status)),
            'stats' => $this->review->stats(),
            'formError' => $formError,
        ]);
    }

    /*
    Kształt wiersza oczekiwany przez widok (admin/index.html.php). Datę formatujemy tutaj,
    w czasie polskim (baza trzyma TIMESTAMPTZ).
    @return array{id: int, name: string, city: string, email: string, registeredAtLabel: string, status: string}
    */
    private function toRow(CompanyApplication $application): array
    {
        $company = $application->getCompany();

        return [
            'id' => $company->getId(),
            'name' => $company->getName(),
            'city' => $company->getCity(),
            'email' => $application->getEmail(),
            'registeredAtLabel' => $company->getCreatedAt()
                ->setTimezone(new \DateTimeZone('Europe/Warsaw'))
                ->format('d.m.Y, H:i'),
            'status' => $company->getStatus()->value,
        ];
    }
}
