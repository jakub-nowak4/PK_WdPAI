<?php

declare(strict_types=1);

namespace App\Model;

// Role kont (users.role, CHECK w 01-schema.sql).
enum Role: string
{
    case Buyer = 'BUYER';
    case Dealer = 'DEALER';
    case Admin = 'ADMIN';
}
