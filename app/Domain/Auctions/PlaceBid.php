<?php

namespace App\Domain\Auctions;

use App\Enums\AuctionStatus;
use App\Events\BidPlaced;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Accepts (or rejects) a bid with full server-side authority. NOTHING here
 * trusts the client: the auction row is locked, status/timer/eligibility/bounds
 * and the increment-over-best rule are all re-verified on the server, bids are
 * idempotent, and the close time is extended server-side for anti-sniping.
 */
class PlaceBid
{
    public function __construct(private readonly AuctionEligibility $eligibility) {}

    public function handle(Auction $auction, User $customer, string $amount, ?string $idempotencyKey = null): AuctionBid
    {
        $amount = Money::of($amount);

        return DB::transaction(function () use ($auction, $customer, $amount, $idempotencyKey) {
            /** @var Auction $auction */
            $auction = Auction::whereKey($auction->id)->lockForUpdate()->firstOrFail();

            // Idempotency: replay returns the original accepted bid.
            if ($idempotencyKey) {
                $existing = AuctionBid::where('auction_id', $auction->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            if ($auction->status !== AuctionStatus::Live) {
                throw new BidRejectedException('The auction is not accepting bids right now.');
            }
            if ($auction->ends_at && now()->greaterThanOrEqualTo($auction->ends_at)) {
                throw new BidRejectedException('The auction has ended.');
            }

            $participant = $auction->participants()
                ->where('customer_id', $customer->id)
                ->with('membership.customer.customerProfile')
                ->first();

            if (! $participant) {
                throw new BidRejectedException('You are not a participant in this auction.');
            }

            // Re-verify eligibility live (dues/KYC may have changed since scheduling).
            $verdict = $this->eligibility->check($participant->membership);
            if (! $verdict['eligible']) {
                throw new BidRejectedException($verdict['reason'] ?? 'You are not eligible to bid.');
            }

            // Bounds check.
            if (Money::compare($amount, (string) $auction->min_bid_amount) < 0
                || Money::compare($amount, (string) $auction->max_bid_amount) > 0) {
                throw new BidRejectedException('Bid is outside the allowed range.');
            }

            // Must beat the current best by at least the increment (max-discount).
            $best = $auction->validBids()->max('amount');
            if ($best !== null) {
                $minNext = Money::add((string) $best, (string) $auction->bid_increment);
                if (Money::compare($amount, $minNext) < 0) {
                    throw new BidRejectedException("Bid must be at least {$minNext} to beat the current best.");
                }
            }

            $bid = $auction->bids()->create([
                'company_id' => $auction->company_id,
                'auction_participant_id' => $participant->id,
                'chitty_membership_id' => $participant->chitty_membership_id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'is_valid' => true,
                'idempotency_key' => $idempotencyKey ?? (string) Str::ulid(),
                'server_placed_at' => now(),
            ]);

            // Anti-sniping: extend the close time if the bid lands in the window.
            if ($auction->auto_extend_seconds > 0 && $auction->ends_at) {
                $remaining = (int) now()->diffInSeconds($auction->ends_at, false);
                if ($remaining <= $auction->auto_extend_window_seconds) {
                    $auction->ends_at = now()->addSeconds($auction->auto_extend_seconds);
                    $auction->extended_count = $auction->extended_count + 1;
                    $auction->save();
                }
            }

            // Reload so the broadcast payload carries the (possibly extended) end time.
            $bid->setRelation('auction', $auction);
            broadcast(new BidPlaced($bid));

            return $bid;
        });
    }
}
