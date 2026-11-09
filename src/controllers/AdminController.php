<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DemoUser;

final class AdminController extends AppController
{
    public function index(): void
    {
        // MAKIETA: zalogowany administrator demo, do czasu AuthService (karta auth).
        $this->renderAsUser('admin/index', DemoUser::admin(), [
            'pageTitle' => 'Panel administratora',
        ]);
    }
}
