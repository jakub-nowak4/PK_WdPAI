<?php

declare(strict_types=1);

namespace App\Model;

// Status zatwierdzenia firmy (companies.status).
enum CompanyStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
