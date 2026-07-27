<?php

namespace App\Enums;

enum InstallmentStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Waived = 'waived';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Statuses that still owe money. */
    public static function outstanding(): array
    {
        return [self::Pending->value, self::Partial->value, self::Overdue->value];
    }
}
