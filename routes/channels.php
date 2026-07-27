<?php

use App\Models\Auction;
use Illuminate\Support\Facades\Broadcast;

/*
 * Presence channel for a live auction room. Authorization is SERVER-side: only
 * an eligible participant of the auction, or staff of the same company, may
 * join. This prevents users from watching or joining auctions they may not
 * access. The returned payload becomes the member's presence info.
 */
Broadcast::channel('auction.{ulid}', function ($user, string $ulid) {
    $auction = Auction::where('ulid', $ulid)->first();

    if (! $auction) {
        return false;
    }

    // Staff of the same company may observe/operate.
    if ($user->isStaff() && $user->company_id === $auction->company_id) {
        return ['id' => $user->ulid, 'name' => $user->name, 'role' => 'staff'];
    }

    // Customers must be an eligible participant.
    $participant = $auction->participants()
        ->where('customer_id', $user->id)
        ->where('is_eligible', true)
        ->first();

    if (! $participant) {
        return false;
    }

    return ['id' => $user->ulid, 'name' => $user->name, 'role' => 'bidder'];
});
