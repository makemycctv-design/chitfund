<?php

namespace App\Enums;

/**
 * Discriminates back-office users from customer/subscriber users. Drives
 * post-login routing (admin dashboard vs customer portal) and gate checks.
 */
enum UserType: string
{
    case Staff = 'staff';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Customer => 'Customer',
        };
    }
}
