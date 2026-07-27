<?php

namespace App\Enums;

enum AuctionStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Paused = 'paused';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function acceptsBids(): bool
    {
        return $this === self::Live;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Closed, self::Cancelled], true);
    }
}
