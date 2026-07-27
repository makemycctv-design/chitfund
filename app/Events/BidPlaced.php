<?php

namespace App\Events;

use App\Models\AuctionBid;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the auction's presence channel whenever a valid bid is accepted.
 * Clients using WebSockets update instantly; clients on the polling fallback
 * pick the same data up from the state endpoint.
 */
class BidPlaced implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AuctionBid $bid) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('auction.'.$this->bid->auction->ulid);
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'bidId' => $this->bid->ulid,
            'amount' => (float) $this->bid->amount,
            'membershipId' => $this->bid->chitty_membership_id,
            'placedAt' => $this->bid->server_placed_at?->toIso8601String(),
            'endsAt' => $this->bid->auction->ends_at?->toIso8601String(),
        ];
    }
}
