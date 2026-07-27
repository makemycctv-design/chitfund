<?php

namespace App\Domain\Auctions;

use App\Models\Auction;

/**
 * Builds the canonical, server-authoritative auction state used by both the
 * WebSocket clients (initial hydration) and the polling fallback. `serverTime`
 * + `endsAt` let the client render a countdown without trusting its own clock.
 */
class AuctionStatePresenter
{
    /** @return array<string, mixed> */
    public function present(Auction $auction, bool $withBids = true): array
    {
        $bestBid = $auction->validBids()
            ->orderBy('amount', $auction->method->higherWins() ? 'desc' : 'asc')
            ->orderBy('server_placed_at')
            ->first();

        $recentBids = $withBids
            ? $auction->validBids()
                ->with('membership:id,ticket_number')
                ->latest('server_placed_at')
                ->limit(15)
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->ulid,
                    'amount' => (float) $b->amount,
                    'ticketNumber' => $b->membership?->ticket_number,
                    'placedAt' => $b->server_placed_at?->toIso8601String(),
                ])->values()
            : [];

        return [
            'id' => $auction->ulid,
            'status' => $auction->status->value,
            'method' => $auction->method->value,
            'serverTime' => now()->toIso8601String(),
            'endsAt' => $auction->ends_at?->toIso8601String(),
            'secondsRemaining' => $auction->secondsRemaining(),
            'chitValue' => (float) $auction->chit_value,
            'minBid' => (float) $auction->min_bid_amount,
            'maxBid' => (float) $auction->max_bid_amount,
            'bidIncrement' => (float) $auction->bid_increment,
            'extendedCount' => $auction->extended_count,
            'bestBid' => $bestBid ? [
                'amount' => (float) $bestBid->amount,
                'ticketNumber' => $bestBid->membership?->ticket_number,
            ] : null,
            'presentCount' => $auction->participants()->where('is_present', true)->count(),
            'eligibleCount' => $auction->participants()->where('is_eligible', true)->count(),
            'recentBids' => $recentBids,
            'result' => $auction->relationLoaded('result') || $auction->result()->exists()
                ? $this->result($auction)
                : null,
        ];
    }

    private function result(Auction $auction): ?array
    {
        $r = $auction->result()->first();
        if (! $r) {
            return null;
        }

        return [
            'winnerMembershipId' => $r->winner_membership_id,
            'discountAmount' => (float) $r->discount_amount,
            'prizeAmount' => (float) $r->prize_amount,
            'foremanCommission' => (float) $r->foreman_commission,
            'dividendPerMember' => (float) $r->dividend_per_member,
        ];
    }
}
