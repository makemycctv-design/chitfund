<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case Pending = 'pending';
    case Collecting = 'collecting';
    case Auctioned = 'auctioned';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
