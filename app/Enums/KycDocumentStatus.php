<?php

namespace App\Enums;

enum KycDocumentStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
