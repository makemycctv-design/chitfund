<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Prized = 'prized';
    case Defaulted = 'defaulted';
    case Terminated = 'terminated';
    case Replaced = 'replaced';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
