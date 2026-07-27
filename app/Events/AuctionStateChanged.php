<?php

namespace App\Events;

use App\Models\Auction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast on any lifecycle transition (started, paused, resumed, closed,
 * cancelled, winner announced) so rooms can react in real time.
 */
class AuctionStateChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Auction $auction, public string $event) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('auction.'.$this->auction->ulid);
    }

    public function broadcastAs(): string
    {
        return 'auction.'.$this->event;
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'event' => $this->event,
            'status' => $this->auction->status->value,
            'endsAt' => $this->auction->ends_at?->toIso8601String(),
            'winnerMembershipId' => $this->auction->winner_membership_id,
            'prizeAmount' => $this->auction->prize_amount !== null ? (float) $this->auction->prize_amount : null,
        ];
    }
}
