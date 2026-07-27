<?php

namespace App\Domain\Auctions;

use App\Enums\AuctionStatus;
use App\Events\AuctionStateChanged;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Officer-driven lifecycle transitions for an auction. Each transition is
 * row-locked, validated, audit-logged, and broadcast.
 */
class AuctionLifecycle
{
    /** Start a scheduled auction; sets a server-authoritative end time. */
    public function start(Auction $auction, User $officer, int $durationSeconds = 300): Auction
    {
        return $this->transition($auction, $officer, 'started', function (Auction $a) use ($durationSeconds) {
            if ($a->status !== AuctionStatus::Scheduled) {
                throw new RuntimeException('Only a scheduled auction can be started.');
            }
            $a->status = AuctionStatus::Live->value;
            $a->started_at = now();
            $a->ends_at = now()->addSeconds($durationSeconds);
        });
    }

    public function pause(Auction $auction, User $officer): Auction
    {
        return $this->transition($auction, $officer, 'paused', function (Auction $a) {
            if ($a->status !== AuctionStatus::Live) {
                throw new RuntimeException('Only a live auction can be paused.');
            }
            // Freeze remaining time so resume is fair.
            $remaining = $a->ends_at ? (int) max(0, now()->diffInSeconds($a->ends_at, false)) : 0;
            $a->status = AuctionStatus::Paused->value;
            $a->paused_at = now();
            $a->meta = array_merge($a->meta ?? [], ['remaining_seconds' => $remaining]);
        });
    }

    public function resume(Auction $auction, User $officer): Auction
    {
        return $this->transition($auction, $officer, 'resumed', function (Auction $a) {
            if ($a->status !== AuctionStatus::Paused) {
                throw new RuntimeException('Only a paused auction can be resumed.');
            }
            $remaining = (int) data_get($a->meta, 'remaining_seconds', 0);
            $a->status = AuctionStatus::Live->value;
            $a->paused_at = null;
            $a->ends_at = now()->addSeconds($remaining);
        });
    }

    public function cancel(Auction $auction, User $officer, string $reason): Auction
    {
        return $this->transition($auction, $officer, 'cancelled', function (Auction $a) use ($reason) {
            if ($a->status->isTerminal()) {
                throw new RuntimeException('Auction is already closed or cancelled.');
            }
            $a->status = AuctionStatus::Cancelled->value;
            $a->cancelled_reason = $reason;
        });
    }

    private function transition(Auction $auction, User $officer, string $event, callable $mutate): Auction
    {
        $auction = DB::transaction(function () use ($auction, $mutate) {
            /** @var Auction $locked */
            $locked = Auction::whereKey($auction->id)->lockForUpdate()->firstOrFail();
            $mutate($locked);
            $locked->save();

            return $locked;
        });

        activity('auction')->performedOn($auction)->causedBy($officer)
            ->withProperties(['event' => $event])
            ->log("Auction {$event}");

        broadcast(new AuctionStateChanged($auction, $event));

        return $auction;
    }
}
