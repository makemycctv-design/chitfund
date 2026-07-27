<?php

namespace App\Enums;

/**
 * Lifecycle of a chitty. Transitions are enforced in the domain layer
 * (Phase 2); this enum is the single source of truth for the allowed states.
 */
enum ChittyStatus: string
{
    case Draft = 'draft';
    case OpenForEnrollment = 'open_for_enrollment';
    case Active = 'active';
    case AuctionScheduled = 'auction_scheduled';
    case AuctionRunning = 'auction_running';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::OpenForEnrollment => 'Open for Enrollment',
            self::Active => 'Active',
            self::AuctionScheduled => 'Auction Scheduled',
            self::AuctionRunning => 'Auction Running',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Statuses considered "live" for dashboard active-count metrics. */
    public static function liveStatuses(): array
    {
        return [
            self::Active->value,
            self::AuctionScheduled->value,
            self::AuctionRunning->value,
        ];
    }

    /**
     * Allowed forward/side transitions for the lifecycle state machine.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::OpenForEnrollment, self::Cancelled],
            self::OpenForEnrollment => [self::Active, self::Cancelled],
            self::Active => [self::AuctionScheduled, self::Closed, self::Cancelled],
            self::AuctionScheduled => [self::AuctionRunning, self::Active, self::Cancelled],
            self::AuctionRunning => [self::Active, self::Closed],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
