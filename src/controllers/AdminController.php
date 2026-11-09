<?php

declare(strict_types=1);

namespace App\Controller;

use App\Request;
use App\Service\DemoUser;

final class AdminController extends AppController
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'all'];

    public function index(Request $request): void
    {
        // Zła wartość ?status= kończy się wyjątkiem 400 (docs/api.md, 2.6).
        $status = (string) $request->queryOneOf('status', self::STATUSES, 'pending');

        // MAKIETA: zalogowany administrator demo, do czasu AuthService (karta auth).
        $this->renderAsUser('admin/index', DemoUser::admin(), [
            'pageTitle' => 'Panel administratora',
            'status' => $status,
        ]);
    }
}
